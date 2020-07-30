<?php

namespace Poller\Services;

use InvalidArgumentException;

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
}
