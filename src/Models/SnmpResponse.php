<?php

namespace Poller\Models;

use Poller\Exceptions\SnmpException;
use Poller\Services\Log;

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
        $this->results = array_merge($this->results, $snmpResponse->getOthers());
        $this->counters = array_merge($this->counters, $snmpResponse->getCounters());
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

    public function getCounters():array {
        return $this->counters;
    }

    public function getOthers():array {
        return $this->results;
    }

    public function getAll():array
    {
        return array_merge($this->counters, $this->results);
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
                if (isset($oid)) {
                    if (isset($this->results[$oid])) {
                        $this->results[$oid] .= $line;
                        continue;
                    }
                }
                $log->error("Unable to explode '$line' on '=' and the OID is not set.");
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

    private function storeValue(string $oid, string $result):void
    {
        if (!str_contains($result, ':')) {
            $type = 'Timeticks';
            $value = $result;
        } else {
            $boom = explode(':', $result, 2);
            if (count($boom) !== 2) {
                $log = new Log();
                $log->error("Failed to explode $result");
                return;
            }
            $type = trim($boom[0]);
            $value = $boom[1];
            $value = trim($value);
            if (strlen($value) === 0) {
                return;
            }
        }

        if ($value[0] === '"') {
            $value = substr($value, 1, -1);
        }
        $value = trim($value);

        if (strpos($value, 'No Such Object available on this agent at this OID') === false) {
            if (str_contains(trim($type), 'Counter')) {
                $this->counters[$oid] = $value;
            } else {
                $this->results[$oid] = $value;
            }
        }
    }
}
