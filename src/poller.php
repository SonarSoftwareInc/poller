<?php

require __DIR__ . '/../vendor/autoload.php';

use Amp\Loop;
use Carbon\Carbon;
use GuzzleHttp\Client;
use League\BooBoo\BooBoo;
use League\BooBoo\Handler\LogHandler;
use League\CLImate\CLImate;
use Poller\Log;
use Poller\Overrides\CommandLineFormatter;
use Poller\Services\Formatter;
use Poller\Services\Poller;
use function Amp\Promise\all;

bootstrap();

Loop::run(function () {
    $poller = new Poller();
    //TODO: Fetch this data from Sonar
    $data = json_decode(file_get_contents(__DIR__ . '/../test_data_DELETE/data.json'));
    $client = new Client();

    output("Starting polling loop...");
    $running = false;
    $lastRun = 0;
    Loop::repeat($msInterval = 1000, function ($watcherId) use (&$running, &$lastRun, $poller, $data, $client) {
        if ($running === false && time() - $lastRun >= 60) {
            $debug = getenv('SONAR_DEBUG_MODE') == 1;
            output("Starting polling cycle.");
            $running = true;
            $lastRun = time();
            $start = time();
            $results = yield all($poller->buildCoroutines($data->data));
            $timeTaken = time() - $start;
            output("Cycle completed in $timeTaken seconds, got " . count($results) . " results.");

            if ($debug === true) {
                writeDebugLog($results);
                Loop::disable($watcherId);
            } else {
                try {
                    $response = $client->request('POST', getenv('SONAR_INSTANCE_URL') . '/api/batch_poller', [
                        'headers' => [
                            'User-Agent' => "SonarPoller/" . getenv('SONAR_POLLER_VERSION') ?? 'Unknown',
                            'Accept'     => 'application/json',
                            'Content-Encoding' => 'gzip',
                        ],
                        'body' => Formatter::formatMonitoringData($results, true),
                    ]);
                    output($response->getStatusCode() . ' - ' . $response->getBody()->getContents());
                } catch (Exception $e) {
                    output($e->getMessage(), true);
                }
            }

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
    $now = Carbon::now();
    if ($error === false) {
        $log->info($message);
        $climate->lightGreen("[{$now->toIso8601String()}] $message");
    } else {
        $log->error($message);
        $climate->red("[{$now->toIso8601String()}] $message");
    }
}

function bootstrap()
{
    $booboo = new BooBoo([
        new CommandLineFormatter()
    ]);
    $log = new Log();
    $booboo->pushHandler(new LogHandler($log->getLogger()));
    $booboo->setErrorPageFormatter(new CommandLineFormatter());
    $booboo->register();
}

function writeDebugLog($results)
{
    output("Writing results to sonar_debug.log.");
    $handle = fopen(__DIR__ . '/sonar_debug.log', 'w');
    fwrite($handle, "Results written at " . Carbon::now()->toIso8601String());
    fwrite($handle, '--------------------------------');
    fwrite($handle, Formatter::formatMonitoringData($results, false));
    fclose($handle);
}
