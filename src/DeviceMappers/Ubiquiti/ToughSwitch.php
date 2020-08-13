<?php

namespace Poller\DeviceMappers\Ubiquiti;

use phpseclib\Net\SSH2;
use Poller\DeviceMappers\BaseDeviceMapper;
use Poller\Models\SnmpResult;
use Poller\Services\Formatter;

class ToughSwitch extends BaseDeviceMapper
{
    public function map(SnmpResult $snmpResult)
    {
        return $this->getBridgingTable(parent::map($snmpResult));
    }

    private function getBridgingTable(SnmpResult $snmpResult):SnmpResult
    {
        $ssh = new SSH2($snmpResult->getIp());
        $ssh->setTimeout(5);
        if (!$ssh->login('username', 'password')) {
            return $snmpResult;
        }

        $bridgingTableArray = [];

        $ssh->read('[#]', SSH2::READ_REGEX);
        $bridges = $ssh->exec("brctl show | awk 'NF>1 && NR>1 {print $1}'");
        $separator = "\r\n";
        $line = strtok($bridges, $separator);
        while ($line !== false) {
            $bridgingTable = $ssh->exec("brctl showmacs $line");
            $bridgingLine = $line = strtok($bridgingTable, $separator);
            while ($bridgingLine !== false) {
                $bridgingLine = strtok($separator);
                $parts = preg_split('/\s+/', trim($bridgingLine));
                if (count($parts) !== 4) {
                    continue;
                }
                if (trim($parts[2]) === 'yes') {
                    continue;
                }
                if (!isset($bridgingTableArray[$parts[0]])) {
                    $bridgingTableArray[$parts[0]] = [];
                }
                $bridgingTableArray[$parts[0]][] = Formatter::formatMac($parts[1]);
            }
            $line = strtok($separator);
        }

        $interfaces = $snmpResult->getInterfaces();
        foreach ($interfaces as $key => $interface) {
            if ($interface->getName() === 'eth0' && isset($bridgingTableArray[1])) {
                $interfaces[$key]->setConnectedLayer1Macs($bridgingTableArray[1]);
            } elseif ($interface->getName() === 'eth1' && isset($bridgingTableArray[2])) {
                $interfaces[$key]->setConnectedLayer1Macs($bridgingTableArray[2]);
            }
        }

        $snmpResult->setInterfaces($interfaces);
        return $snmpResult;
    }
}
