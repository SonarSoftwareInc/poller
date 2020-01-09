<?php

namespace Poller\Tasks;

use Amp\Parallel\Worker\DefaultPool;
use Amp\Parallel\Worker\Environment;
use Amp\Parallel\Worker\Task;
use Amp\Loop;
use Amp\Parallel\Worker\CallableTask;
use Exception;
use Poller\Exceptions\IcmpPingException;
use function Amp\call;
use function Amp\Parallel\Worker\pool;
use function \Amp\Promise\all;
use function Amp\Socket\connect;

class PingHostMultipleTimes implements Task
{
    private $ipAddress;
    private $timeout;
    private $repeats;

    /**
     * PingHostMultipleTimes constructor.
     * @param string $ipAddress
     * @param int $timeout
     * @param int $repeats
     */
    public function __construct(string $ipAddress, int $timeout = 3, int $repeats = 10)
    {
        $this->ipAddress = $ipAddress;
        $this->timeout = $timeout;
        $this->repeats = $repeats;
    }

    /**
     * @inheritDoc
     */
    public function run(Environment $environment)
    {
        $coroutines = [];
        $pool = new DefaultPool();
        for ($i = 0; $i < $this->repeats; $i++) {
            $coroutines[] = call(function () use ($pool) {
                return yield $pool->enqueue(new PingHost($this->ipAddress, $this->timeout));
            });
        }
        $results = yield all($coroutines);
        $pool->shutdown();
        return $results;
    }


}
