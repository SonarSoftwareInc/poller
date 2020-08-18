<?php

require(__DIR__ . '/../../vendor/autoload.php');

use League\BooBoo\BooBoo;
use League\BooBoo\Formatter\CommandLineFormatter;

$booboo = new BooBoo([
    new CommandLineFormatter()
]);
$booboo->setErrorPageFormatter(new CommandLineFormatter());
$booboo->register();

echo 'poop';
$client = new SnmpClient([
    'host' => '192.168.1.1',
    'version' => 2,
    'community' => 'foobar',
    'timeout_connect' => 2,
    'timeout_read' => 2,
]);

try {
    print_r($client->get('1.2.3.4'));
} catch (Throwable $e) {
    print_r($e);
    print_r($e->getMessage());
}

