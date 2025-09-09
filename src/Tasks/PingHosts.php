<?php

namespace Poller\Tasks;

use Amp\Parallel\Worker\Environment;
use Amp\Parallel\Worker\Task;
use Carbon\Carbon;
use Poller\Services\Log;
use Poller\Models\PingResult;

class PingHosts implements Task
{
    private array $devices;
    private int $timeout;
    private int $repeats;
    private array $ips;

    /**
     * PingHost constructor.
     * @param array $devices
     * @param int $timeout (seconds)
     * @param int $repeats
     */
    public function __construct(array $devices, int $timeout = 2, int $repeats = 10)
    {
        $this->devices = $devices;
        $this->timeout = $timeout*1000;
        $this->repeats = $repeats;
    }

    /**
     * @inheritDoc
     */
    public function run(Environment $environment)
    {
        $interval = 500 + (100*rand(0, 5));
        $flags = [
            '-b12', //12 byte packet
            "-p$interval", //interval between ping packets
            '-r0', //No retries
            '-B1.5', //Backoff multiplier
            '-q', //Quiet - don't spam out results
            '-R', //Use random bytes instead of all zeroes
        ];

        foreach ($this->devices as $device) {
            $this->ips[$device->getIp()] = $device->getInventoryItemID();
        }

        $command = '/usr/local/sbin/fping '
            . escapeshellcmd("-C {$this->repeats} ")
            . escapeshellcmd("-t {$this->timeout} ")
            . implode(' ', $flags)
            . ' '
            . implode(' ', array_keys($this->ips))
            . ' 2>&1';

        exec(
            $command,
            $results
        );

        $results = $this->formatResults($results);
        return $results;
    }

    /**
     * @param array $results
     * @return array
     */
    private function formatResults(array $results):array
    {
        $log = new Log();
        $formattedResults = [];
        if (count($results) > 0) {
            foreach ($results as $result) {
                $boom = preg_split('/\s+/', $result);
                $ip = $boom[0];
                if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
                    //Issue with some versions of fping 4.x
                    if (str_contains($result, "timeout (-t) value larger than period (-p) produces unexpected results")) {
                        continue;
                    }
                    $log->error("$ip is not a valid IP address, skipping line '$result'");
                    continue;
                }

                //Strip "<ip> :" and reindex: series has RTT (Round Trip Time) strings and "-" losses
                //-2 here because we don't care about the first two results which are the host and a colon
                $series = array_values(array_slice($boom, 2));
                $rtts = array_values(array_filter($series, static fn($v) => is_numeric($v)));
                $total = count($series);
                $lossCount = $total - count($rtts);
                $lossPct = $total > 0 ? round(($lossCount / $total) * 100, 2) : 100.0;

                $min = $max = $median = 0.0;

                if (!empty($rtts)) {
                    $min = (float) round((float) min($rtts), 2);
                    $max = (float) round((float) max($rtts), 2);
                    $median = $this->calculateMedian($rtts);
                }

                $formattedResults[] = new PingResult(
                    $this->ips[trim($ip)] ?? null,
                    $lossPct,
                    $min,
                    $max,
                    $median,
                );
            }
        }

        return $formattedResults;
    }

    /**
     * @param array $data
     * @return float
     */
    private function calculateMedian(array $data): float
    {
        $responses = array_values(array_filter($data, static fn($v) => is_numeric($v)));
        $n = count($responses);
        if ($n === 0) return 0.0;

        sort($responses, SORT_NUMERIC);

        $m = intdiv($n, 2);
        if ($n % 2 === 1) {
            return (float) round((float) $responses[$m], 2);
        }

        $median = ((float) $responses[$m] + (float) $responses[$m - 1]) / 2;
        return (float) round($median, 2);
    }
}
