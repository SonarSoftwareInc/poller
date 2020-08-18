<?php

namespace Poller;

use League\CLImate\CLImate;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Throwable;

class Log
{
    private Logger $logger;

    public function __construct()
    {
        $this->climate = new CLImate();
        $this->logger = new Logger('sonar_poller');
        $dateFormat = "Y-m-d H:i:s";
        $output = "[%datetime%] %level_name%: %message% %context%\n";
        $formatter = new LineFormatter($output, $dateFormat);
        $stream = new StreamHandler(__DIR__ . '/../logs/sonar_poller.log', Logger::DEBUG);
        $stream->setFormatter($formatter);
        $this->logger->pushHandler($stream);
    }

    public function getLogger():Logger
    {
        return $this->logger;
    }

    public function info(string $message)
    {
        $this->logger->info($message);
    }

    public function error(string $message, array $context = [])
    {
        $this->logger->error($message, []);
    }

    public function exception(Throwable $e, array $context = [])
    {
        $this->error("\n----------------");
        $this->error("EXCEPTION CAUGHT:");
        $this->error($e->getMessage());
        $this->error("----------------");
        foreach ($e->getTrace() as $counter => $line) {
            $line['position'] = $counter;
            $this->error(json_encode($line), $context);
        }
    }
}
