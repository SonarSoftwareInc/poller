<?php

namespace Poller\Tasks;

use Amp\Parallel\Worker\DefaultPool;
use Amp\Parallel\Worker\Environment;
use Amp\Parallel\Worker\Task;
use Exception;
use Poller\Exceptions\IcmpPingException;

class PingHost implements Task
{
    private $ipAddress;
    private $timeout;
    private $repeats;

    /**
     * PingHost constructor.
     * @param string $ipAddress
     * @param int $timeout (seconds)
     * @param int $repeats
     */
    public function __construct(string $ipAddress, int $timeout = 1, int $repeats = 10)
    {
        $this->ipAddress = $ipAddress;
        $this->timeout = $timeout*1000;
        $this->repeats = $repeats;
    }

    /**
     * @inheritDoc
     */
    public function run(Environment $environment)
    {
        $flags = [
            '-b12', //12 byte packet
            '-p10', //10ms between ping packets
            '-r0', //No retries
            '-B1', //Backoff multiplier
            '-q', //Quiet - don't spam out results
        ];

        $command = '/usr/bin/fping '
            . escapeshellcmd("-C {$this->repeats} ")
            . escapeshellcmd("-t {$this->timeout} ")
            . implode(' ', $flags)
            . ' '
            . escapeshellcmd($this->ipAddress)
            . ' 2>&1';

        exec(
            $command,
            $results
        );

        return $this->formatResults($results);
    }

    /**
     * @param array $results
     * @return array
     */
    private function formatResults(array $results):array
    {
        $formattedResult = [
            'host' => $this->ipAddress,
            'loss_percentage' => 100,
            'low' => 0,
            'high' => 0,
            'median' => 0,
        ];

        if (count($results) > 0) {
            $boom = explode(" ",$results[0]);
            //-2 here because we don't care about the first two results which are the host and a colon
            $middleIndex = floor((count($boom)-2)/2);
            foreach ($results as $result) {
                $boom = preg_split('/\s+/', $result);
                unset($boom[0]);
                unset($boom[1]);
                sort($boom);
                $lossCount = count(array_filter($boom, function($val) {
                    return strpos($val, '-') === 0;
                }));

                $formattedResult = [
                    'loss_percentage' => round(($lossCount / (count($boom)))*100,2),
                    'low' => (float)round($boom[0],2),
                    'high' => (float)round($boom[count($boom)-1],2),
                    'median' => $this->calculateMedian($boom, $middleIndex),
                ];
            }
        }

        return $formattedResult;
    }

    /**
     * @param array $data
     * @param int $middleIndex
     * @return float
     */
    private function calculateMedian(array $data, int $middleIndex):float
    {
        $median = $data[$middleIndex];
        if ($median === '-') {
            return 0;
        }

        if (count($data) % 2 === 0) {
            $median = ($median + $data[$middleIndex - 1]) / 2;
        }
        return (float)round($median,2);
    }
}
