<?php

namespace Poller\Exceptions;

use RuntimeException;

class ValidationException extends RuntimeException
{
    private string $field;

    public function setField(string $field)
    {
        $this->field = $field;
    }

    public function getField():string
    {
        return $this->field;
    }
}
