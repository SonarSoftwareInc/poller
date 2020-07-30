<?php

namespace Poller\Tasks;

use Amp\Loop;
use Amp\Parallel\Worker\Environment;
use Amp\Parallel\Worker\Task;
use Blackfire\Probe;

class PingHosts implements Task
{
    private array $ipAddress = [];
    private int $timeout;
    private int $repeats;

    /**
     * PingHost constructor.
     * @param array $ipAddresses
     * @param int $timeout (seconds)
     * @param int $repeats
     */
    public function __construct(array $ipAddresses, int $timeout = 2, int $repeats = 10)
    {
        $ips = [];
        foreach ($ipAddresses as $ipAddress) {
            $ips[] = $ipAddress->getIp();
        }
        $this->ipAddress = $ips;
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

        $command = '/usr/bin/fping '
            . escapeshellcmd("-C {$this->repeats} ")
            . escapeshellcmd("-t {$this->timeout} ")
            . implode(' ', $flags)
            . ' '
            . implode(' ', $this->ipAddress)
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
        $formattedResults = [];
        if (count($results) > 0) {
            foreach ($results as $result) {
                $boom = preg_split('/\s+/', $result);
                //-2 here because we don't care about the first two results which are the host and a colon
                $ip = $boom[0];
                unset($boom[0]);
                unset($boom[1]);
                sort($boom);
                $lossCount = count(array_filter($boom, function($val) {
                    return strpos($val, '-') === 0;
                }));

                $formattedResults[$ip] = [
                    'loss_percentage' => round(($lossCount / (count($boom)))*100,2),
                    'low' => (float)round($boom[0],2),
                    'high' => (float)round($boom[count($boom)-1],2),
                    'median' => $this->calculateMedian($boom),
                ];;
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
