<?php

require __DIR__ . '/../vendor/autoload.php';

use Amp\Loop;
use League\BooBoo\BooBoo;
use League\BooBoo\Formatter\CommandLineFormatter;
use League\CLImate\CLImate;
use Poller\Log;
use Poller\Services\Formatter;
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
    $client = new \GuzzleHttp\Client();

    output("Starting polling cycle...");
    $running = false;
    Loop::repeat($msInterval = 60000, function ($watcherId) use (&$running, $poller, $data, $client, $log) {
        if ($running === false) {
            $running = true;
            $start = time();
            $results = yield all($poller->buildCoroutines($data->data));
            $timeTaken = time() - $start;
            output("Cycle completed in $timeTaken seconds, got " . count($results) . " results.");

            try {
                $response = $client->request('POST', '/sonar', [
                    'headers' => [
                        'User-Agent' => "SonarPoller/2.0", //inject real poller version here
                        'Accept'     => 'application/json',
                        'Content-Encoding' => 'gzip',
                    ],
                    'body' => Formatter::formatMonitoringData($results),
                ]);
            } catch (Exception $e) {
                output($e->getMessage(), true);
            }
            //todo: post to Sonar instance, where it it configured?
            //todo: log output to file system, setup logrotate and display using goaccess or something similar
            //or get docker working and just dump the output there?
            //goaccess access.log -o /var/www/html/report.html --log-format=COMBINED --real-time-html
            $running = false;
        }
    });
});

/**
 * @param string $message
 * @param bool $error
 */
function output(string $message, bool $error = false)
{
    $log = new Log();
    $climate = new CLImate;
    if ($error === false) {
        $log->info($message);
        $climate->lightGreen($message);
    } else {
        $log->error($message);
        $climate->red($message);
    }
}
