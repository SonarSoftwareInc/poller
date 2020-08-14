<?php

namespace Poller;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class Log
{
    private Logger $logger;

    public function __construct()
    {
        $this->logger = new Logger('sonar_poller');
        $dateFormat = "Y-m-d H:i:s";
        $output = "[%datetime%] %level_name%: %message% %context% %extra%\n";
        $formatter = new LineFormatter($output, $dateFormat);
        $stream = new StreamHandler(__DIR__ . '/../logs/sonar_poller.log', Logger::DEBUG);
        $stream->setFormatter($formatter);
        $this->logger->pushHandler($stream);
    }

    public function info(string $message)
    {
        $this->logger->info($message);
    }

    public function error(string $message)
    {
        $this->logger->error($message);
    }
}
