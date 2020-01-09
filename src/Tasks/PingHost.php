<?php

namespace Poller\Tasks;

use Amp\Parallel\Worker\Environment;
use Amp\Parallel\Worker\Task;
use Exception;
use Poller\Exceptions\IcmpPingException;

class PingHost implements Task
{
    private $ipAddress;
    private $timeout;

    /**
     * PingHost constructor.
     * @param string $ipAddress
     * @param int $timeout
     */
    public function __construct(string $ipAddress, int $timeout)
    {
        $this->ipAddress = $ipAddress;
        $this->timeout = $timeout;
    }

    /**
     * @inheritDoc
     */
    public function run(Environment $environment)
    {
        /**
         * Many devices can't handle being flooded with multiple pings at the same time.
         * This imposes a random delay between pings so that we try to alleviate this.
         */
        usleep(rand(10000, 500000));
        $key = rand(1,10000000000000);
        try {
            $socket = socket_create(AF_INET, SOCK_RAW, getprotobyname('icmp'));
            socket_set_option(
                $socket,
                SOL_SOCKET,
                SO_RCVTIMEO,
                [
                    'sec' => $this->timeout,
                    'usec' => 0
                ]
            );

            $result = socket_connect($socket, $this->ipAddress, 0);
            if ($result === false) {
                throw new IcmpPingException("Failed to connect to {$this->ipAddress}: " . socket_strerror(socket_last_error($socket)));
            }

            $startTime = microtime(true);
            $package  = "\x08\x00\x19\x2f\x00\x00\x00\x00\x70\x69\x6e\x67";
            socket_send($socket, $package, strlen($package), 0);
            if (socket_read($socket, 255)) {
                $result = $this->calculateTime($startTime);
            } else {
                $result = null;
            }
            socket_close($socket);
            return $result;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * @param $startTime
     * @return float
     */
    private function calculateTime($startTime):float
    {
        return sprintf(
            '%.3f',
            round(
                (microtime(true) - $startTime)*1000,
                3
            )
        );
    }
}
