<?php

namespace Poller\Models;

class SnmpError
{
    private bool $down;
    private string $message;

    public function __construct(bool $down, string $message)
    {
        $this->down = $down;
        $this->message = $message;
    }

    public function down():bool
    {
        return $this->down === true;
    }

    public function message():string
    {
        return $this->message;
    }
}
