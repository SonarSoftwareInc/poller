<?php

namespace Poller\Tasks;

use Amp\Parallel\Worker\DefaultPool;
use Amp\Parallel\Worker\Environment;
use Amp\Parallel\Worker\Task;
use SNMP;
use function Amp\call;
use function Amp\Parallel\Worker\pool;
use function Amp\Promise\all;

class SnmpProbeHost implements Task
{
    private $ipAddress;

    /**
     * SnmpProbeHost constructor.
     * @param string $ipAddress
     */
    public function __construct(string $ipAddress)
    {
        $this->ipAddress = $ipAddress;
    }

    /**
     * @inheritDoc
     */
    public function run(Environment $environment)
    {
        $pool = new DefaultPool(3);
        $coroutines = [];
        $coroutines['gets'] = call(function () use($pool) {
            $snmpVersion = 2;
            switch ($snmpVersion) {
                case 2:
                    $version = SNMP::VERSION_2C;
                    break;
                case 3:
                    $version = SNMP::VERSION_3;
                    break;
                default:
                    $version = SNMP::VERSION_1;
                    break;
            }

            return yield $pool->enqueue(
                new SnmpGet(
                    $this->ipAddress,
                    $version,
                    'public',
                    [
                        '1.3.6.1.2.1.2.2.1.2.1.1',
                        '1.3.6.1.2.1.2.2.1.2.1.2',
                        '1.3.6.1.2.1.2.2.1.2.1.3',
                        '1.3.6.1.2.1.2.2.1.2.1.4',
                    ]
                )
            );
        });

        $result = yield all($coroutines);
        yield $pool->shutdown();
        return $result;
    }
}
