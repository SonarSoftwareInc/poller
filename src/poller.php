<?php

require __DIR__ . '/../vendor/autoload.php';

use Amp\Loop;
use Amp\Parallel\Worker\DefaultPool;
use Poller\Tasks\PingHost;
use Poller\Tasks\SnmpProbeHost;
use function Amp\call;
use function Amp\Promise\all;

$poolSize = 32;

$icmpTasks = [];
$snmpTasks = [];
$start = ip2long('192.168.1.1');
for ($i = 0; $i < 50; $i++) {
    $icmpTasks[long2ip($start)] = new PingHost(long2ip($start));
    $snmpTasks[long2ip($start)] = new SnmpProbeHost(long2ip($start));
    $start++;
}

$results = [];
$pool = new DefaultPool($poolSize);

Loop::run(function () use ($icmpTasks, $snmpTasks, &$results, $pool) {
    $start = time();
    stdOutput('Starting loop execution');

    try {
        $coroutines = [];
        foreach ($icmpTasks as $ip => $icmpTask) {
            $coroutines[] = call(function () use ($pool, $icmpTask) {
                return yield $pool->enqueue($icmpTask);
            });
        }

        foreach ($snmpTasks as $ip => $snmpTask) {
            $coroutines[] = call(function () use ($pool, $snmpTask) {
                return yield $pool->enqueue($snmpTask);
            });
        }

        Loop::repeat($msInterval = 1000, function ($watcherId) use ($pool) {
            $idleWorkers = $pool->getIdleWorkerCount();
            if ($idleWorkers < $pool->getMaxSize()) {
                stdOutput("$idleWorkers workers idle...");
            } else {
                Loop::cancel($watcherId);
            }
        });

        $results = yield all($coroutines);

        $timeTaken = time() - $start . "s";
        stdOutput("Execution complete (took $timeTaken)");
    } catch (Throwable $e) {
        stdOutput("Received an error: {$e->getMessage()}");
    }

    //TODO: this needs to be split out into a way to repeatedly run this loop
    yield $pool->shutdown();
    Loop::stop();
});

function stdOutput(string $message)
{
    echo "[" . time() . "] $message\n";
}