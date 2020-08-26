<?php

namespace Poller;

use League\CLImate\CLImate;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Throwable;
use const JSON_PRETTY_PRINT;

class Log
{
    private Logger $logger;
    private bool $logExceptions = false;

    public function __construct()
    {
        $this->climate = new CLImate();
        $this->logger = new Logger('sonar_poller');
        $dateFormat = "Y-m-d H:i:s";
        $output = "[%datetime%] %level_name%: %message%\n";
        $formatter = new LineFormatter($output, $dateFormat);
        $stream = new StreamHandler('php://stdout', Logger::DEBUG);
        $stream->setFormatter($formatter);
        $this->logger->pushHandler($stream);
        $this->logExceptions = getenv('SONAR_LOG_EXCEPTIONS');
    }

    public function getLogger():Logger
    {
        return $this->logger;
    }

    public function info(string $message)
    {
        $this->logger->info($message);
    }

    public function error(string $message)
    {
        $this->logger->error($message);
    }

    public function exception(Throwable $e, array $context = [])
    {
        if ($this->logExceptions === true) {
            $log = null;

            $log .= "\n----------------\n";
            $log .= "EXCEPTION CAUGHT:\n";
            $log .= get_class($e) . " | " . $e->getMessage() . "\n";
            if (count($context) > 0) {
                $log .= json_encode($context, JSON_PRETTY_PRINT) . "\n";
            }
            $log .= "----------------\n";
            foreach ($e->getTrace() as $counter => $line) {
                $line['position'] = $counter;
                $log .= json_encode($line, JSON_PRETTY_PRINT) . "\n";
            }
            $this->error($log);
        }
    }
}
