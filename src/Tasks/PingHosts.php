<?php

namespace Poller\Tasks;

use Amp\Parallel\Worker\Environment;
use Amp\Parallel\Worker\Task;
use Carbon\Carbon;
use Poller\Log;
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

        $chunks = array_chunk($this->ips, 500, true);
        $mergedResults = [];
        foreach ($chunks as $chunk) {
            $command = '/usr/local/sbin/fping '
                . escapeshellcmd("-C {$this->repeats} ")
                . escapeshellcmd("-t {$this->timeout} ")
                . implode(' ', $flags)
                . ' '
                . implode(' ', array_keys($chunk))
                . ' 2>&1';

            exec(
                $command,
                $results
            );

            $mergedResults = array_merge($mergedResults, $results);
        }



        $results = $this->formatResults($mergedResults);
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
                //-2 here because we don't care about the first two results which are the host and a colon
                unset($boom[0]);
                unset($boom[1]);
                sort($boom);
                $lossCount = count(array_filter($boom, function($val) {
                    return strpos($val, '-') === 0;
                }));

                $formattedResults[] = new PingResult(
                    $this->ips[trim($ip)],
                    round(($lossCount / (count($boom)))*100,2),
                    (float)round($boom[0],2),
                    (float)round($boom[count($boom)-1],2),
                    $this->calculateMedian($boom),
                );
            }
        }

        return $formattedResults;
    }

    /**
     * @param array $data
     * @return float
     */
    private function calculateMedian(array $data):float
    {
        $responses = array_values(array_filter($data, function ($value) {
            return is_numeric($value);
        }));

        if (count($responses) === 0){
            return (float)0;
        }

        if (count($responses) === 1) {
            return (float)$responses[0];
        }

        $middleIndex = floor(count($responses)/2);

        $median = $responses[$middleIndex];
        if (count($responses) % 2 === 0) {
            $median = ($median + $responses[$middleIndex - 1]) / 2;
        }
        return (float)round($median,2);
    }
}
