<?php

namespace Poller\Services;

use InvalidArgumentException;
use Poller\Log;
use Poller\Models\SnmpError;
use Poller\Models\SnmpResult;
use const FILTER_VALIDATE_MAC;
use const ZLIB_ENCODING_GZIP;

class Formatter
{
    /**
     * Format a MAC in a standard format
     * @param string $mac
     * @return string
     */
    public static function formatMac(string $mac):string
    {
        $mac = self::addMacLeadingZeroes($mac);
        $cleanMac = strtoupper(preg_replace("/[^A-Fa-f0-9]/", '', $mac));
        if (strlen($cleanMac) !== 12) {
            throw new InvalidArgumentException("$mac cannot be converted to a 12 character MAC address.");
        }
        $macSplit = str_split($cleanMac,2);
        return strtoupper(implode(":",$macSplit));
    }

    public static function validateMac(string $mac):bool
    {
        $mac = self::addMacLeadingZeroes($mac);
        $cleanMac = strtoupper(preg_replace("/[^A-Fa-f0-9]/", '', $mac));
        if (strlen($cleanMac) !== 12) {
            return false;
        }
        $macSplit = str_split($cleanMac,2);
        $mac = strtoupper(implode(":",$macSplit));
        return filter_var($mac, FILTER_VALIDATE_MAC) !== false;
    }

    /**
     * @param string $mac
     * @return string
     */
    public static function addMacLeadingZeroes(string $mac):string
    {
        //Sometimes, MACs are provided in a format where they are colon separated, but missing leading zeroes.
        if (strpos($mac,":") !== false) {
            $fixedMac = null;
            $boom = explode(":",$mac);
            foreach ($boom as $shard) {
                if (strlen($shard) == 1) {
                    $shard = "0" . $shard;
                }
                $fixedMac .= $shard;
            }
            $mac = $fixedMac;
        }
        return $mac;
    }

    public static function formatMonitoringData(array $coroutines, int $timeTaken, bool $gzCompress = true):string
    {
        $data = [];
        $log = new Log();
        foreach ($coroutines as $coroutine) {
            if (is_array($coroutine)) {
                foreach ($coroutine as $pingResult) {
                    if (!isset($data[$pingResult->getIp()])) {
                        $data[$pingResult->getIp()] = [
                            'icmp' => null,
                            'snmp' => null,
                        ];
                    }
                    $result = $pingResult->toArray();
                    if (json_encode($result) === false) {
                        $log->error("Failed to JSON encode " . serialize($result));
                        continue;
                    }
                    $data[$pingResult->getIp()]['icmp'] = $result;
                }
            } elseif ($coroutine instanceof SnmpResult || $coroutine instanceof SnmpError) {
                if (!isset($data[$coroutine->getIp()])) {
                    $data[$coroutine->getIp()] = [
                        'icmp' => null,
                        'snmp' => null,
                    ];
                }
                $result = $coroutine->toArray();
                if (json_encode($result) === false) {
                    $log->error("Failed to JSON encode " . serialize($result));
                    continue;
                }
                $data[$coroutine->getIp()]['snmp'] = $result;
            }
        }

        $results = [
            'api_key' => getenv('SONAR_POLLER_API_KEY'),
            'version' => getenv('SONAR_POLLER_VERSION'),
            'time_taken' => 0,
            'results' => $data,
        ];

        return $gzCompress === true
            ? gzcompress(json_encode($results), 6, ZLIB_ENCODING_GZIP)
            : json_encode($results, JSON_PRETTY_PRINT);
    }
}
