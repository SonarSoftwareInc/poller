<?php

namespace Poller\Models;

use Poller\Exceptions\SnmpException;
use Poller\Log;

class SnmpResponse
{
    private array $results = [];
    private array $counters = [];

    public function __construct(array $results)
    {
        $this->formatResults($results);
    }

    public function merge(SnmpResponse $snmpResponse)
    {
        $this->results = array_merge($this->results, $snmpResponse->getAll());
    }

    public function get(string $oid):string
    {
        $oid = trim(ltrim(trim($oid), '.'));
        if (isset($this->results[$oid])) {
            return $this->results[$oid];
        } elseif (isset($this->counters[$oid])) {
            return $this->counters[$oid];
        }

        throw new SnmpException("$oid is not found.");
    }

    public function getAll():array
    {
        return [
            'counters' => $this->counters,
            'others' => $this->results,
        ];
    }

    private function formatResults(array $results)
    {
        $log = new Log();
        foreach ($results as $line) {
            if (trim($line) === 'End of MIB') {
                return;
            }

            $boom = explode('=', $line, 2);
            if (count($boom) !== 2) {
                //Multiline response, needs to be appended to previous response
                if (isset($oid)) {
                    $this->results[$oid] .= ' ' . $this->cleanValue($line);
                    continue;
                }
                $log->error("Unable to split response '$line' and no previous OID set.");
                continue;
            }
            $oid = trim(ltrim(trim($boom[0]), '.'));
            if (!str_contains($oid, '.')) {
                $log->error("'$oid' is not a valid OID, received it in line $line.");
                continue;
            }

            $this->storeValue($oid, $boom[1]);
        }
    }

    private function storeValue(string $oid, string $result)
    {
        $boom = explode(':', $result);
        $value = $result[1];
        $value = trim($value);
        if (strlen($value) === 0) {
            return $value;
        }

        if ($value[0] === '"') {
            $value = substr($value, 1, -1);
        }
        $value = trim($value);

        if (strpos($value, 'No Such Object available on this agent at this OID') === false) {
            if (trim($boom[0]) === 'COUNTER') {
                $this->results[$oid] = $value;
            } else {
                $this->counters[$oid] = $value;
            }
        }
        return $value;
    }
}
