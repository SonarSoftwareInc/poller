<?php

namespace Poller;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class Log
{
    private $logger;

    public function __construct()
    {
        $this->logger = new Logger('sonar_poller');
        $this->logger->pushHandler(new StreamHandler('/var/log/sonar_poller.log'));
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
