<?php

namespace Poller\Models;

use Poller\Exceptions\SnmpException;

class SnmpResponse
{
    private array $results = [];

    public function __construct(array $results)
    {
        $this->formatResults($results);
    }

    public function get(string $oid):string
    {
        $oid = trim(ltrim($oid, '.'));
        if (isset($this->results[$oid])) {
            return $this->results[$oid];
        }

        throw new SnmpException("$oid is not found.");
    }

    public function getAll():array
    {
        return $this->results;
    }

    private function formatResults(array $results)
    {
        foreach ($results as $line) {
            $boom = explode('=', $line, 2);
            $oid = trim(ltrim(trim($boom[0]), '.'));
            $value = trim($boom[1]);
            if ($value[0] === '"') {
                $value = substr($value, 1, -1);
            }
            $value = trim($value);
            $this->results[$oid] = $value;
        }
    }
}
