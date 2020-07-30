<?php

use Amp\Loop;
use Amp\Socket\ClientTlsContext;
use Amp\Socket\ConnectContext;
use function Amp\Promise\any;
use function Amp\Promise\some;
use function Amp\Socket\connect;

require(__DIR__ . '/../../vendor/autoload.php');

Loop::run(static function () {
    $host = '192.168.1.1';
    $ports = range(1024, 65000);
    $connectContext = (new ConnectContext)->withConnectTimeout(2000)
        ->withMaxAttempts(1)
        ->withTcpNoDelay();

    $promises = [];
    foreach ($ports as $port) {
        $promises[] = connect($host . ':' . $port, $connectContext);
    }

    yield $results = any($promises);
    echo "Got some results back\n";
    Loop::stop();
});