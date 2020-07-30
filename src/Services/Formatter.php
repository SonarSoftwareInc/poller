<?php

namespace Poller\Services;

use InvalidArgumentException;
use Poller\Models\SnmpResult;
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
        return implode(":",$macSplit);
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

    public static function formatMonitoringData(array $coroutines):string
    {
        $data = [];
        foreach ($coroutines as $coroutine) {
            if (is_array($coroutine)) {
                foreach ($coroutine as $pingResult) {
                    if (!isset($data[$pingResult->getIp()])) {
                        $data[$pingResult->getIp()] = [
                            'icmp' => null,
                            'snmp' => null,
                        ];
                    }
                    $data[$pingResult->getIp()]['icmp'] = $pingResult->toArray();
                }
            } elseif ($coroutine instanceof SnmpResult) {
                if (!isset($data[$coroutine->getIp()])) {
                    $data[$coroutine->getIp()] = [
                        'icmp' => null,
                        'snmp' => null,
                    ];
                }
                $data[$coroutine->getIp()]['snmp'] = $coroutine->toArray();
            }
        }

        return gzcompress(json_encode($data), 6, ZLIB_ENCODING_GZIP);
    }
}
