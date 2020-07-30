<?php

require __DIR__ . '/../vendor/autoload.php';

use Amp\Loop;
use League\BooBoo\BooBoo;
use League\BooBoo\Formatter\CommandLineFormatter;
use League\CLImate\CLImate;
use Poller\Services\Poller;
use function Amp\Promise\all;

$booboo = new BooBoo([
    new CommandLineFormatter()
]);
$booboo->setErrorPageFormatter(new CommandLineFormatter());
$booboo->register();


Loop::run(function () {
    $poller = new Poller();
    $data = json_decode(file_get_contents('test_data_DELETE/data.json'));

    stdOutput("Starting polling cycle...");
    $running = false;
    Loop::repeat($msInterval = 60000, function ($watcherId) use (&$running, $poller, $data) {
        if ($running === false) {
            $running = true;
            $start = time();
            $results = yield all($poller->buildCoroutines($data->data));
            $timeTaken = time() - $start;
            stdOutput("Cycle completed in $timeTaken seconds, got " . count($results) . " results.");
            $running = false;
        }
    });
});

/**
 * @param string $message
 * @param bool $error
 */
function stdOutput(string $message, bool $error = false)
{
    $climate = new CLImate;
    if ($error === false) {
        $climate->lightGreen($message);
    } else {
        $climate->red($message);
    }
}
