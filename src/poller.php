<?php

require __DIR__ . '/../vendor/autoload.php';

use Amp\Loop;
use Amp\Parallel\Worker\DefaultPool;
use Poller\Tasks\PingHost;
use Poller\Tasks\SnmpProbeHost;
use function Amp\call;
use function Amp\Promise\all;

$icmpTasks = [];
$snmpTasks = [];
$start = ip2long('192.168.1.1');
for ($i = 0; $i < 500; $i++) {
    $icmpTasks[long2ip($start)] = new PingHost(long2ip($start));
    $snmpTasks[long2ip($start)] = new SnmpProbeHost(long2ip($start));
    $start++;
}

$results = [];

Loop::run(function () use ($icmpTasks, $snmpTasks, &$results) {
    $pool = new DefaultPool(36);

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

    $results = yield all($coroutines);
    yield $pool->shutdown();
    Loop::stop();
});

print_r($results) . PHP_EOL;