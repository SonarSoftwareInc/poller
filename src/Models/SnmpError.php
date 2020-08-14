<?php

namespace Poller\Models;

class SnmpError implements CoroutineResultInterface
{
    private bool $down;
    private string $message;
    private string $ip;

    public function __construct(bool $down, string $message, string $ip)
    {
        $this->down = $down;
        $this->message = $message;
        $this->ip = $ip;
    }

    public function down():bool
    {
        return $this->down === true;
    }

    public function message():string
    {
        return $this->message;
    }

    public function getIp(): string
    {
        return $this->ip;
    }

    public function toArray():array
    {
        return [
            'metadata' => [
                'contact' => null,
                'name' => null,
                'location' => null,
                'uptime' => null,
                'description' => null,
            ],
            'interfaces' => [],
            'results' => [],
            'up' => $this->down === false,
            'message' => $this->message,
        ];
    }
}
