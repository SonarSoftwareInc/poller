<?php

namespace Poller\Models;

class SnmpError implements CoroutineResultInterface
{
    private bool $down;
    private string $message;
    private int $id;

    public function __construct(bool $down, string $message, int $id)
    {
        $this->down = $down;
        $this->message = $message;
        $this->id = $id;
    }

    public function down():bool
    {
        return $this->down === true;
    }

    public function message():string
    {
        return $this->message;
    }

    public function getID(): int
    {
        return $this->id;
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
