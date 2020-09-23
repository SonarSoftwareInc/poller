<?php

namespace Poller\DeviceMappers\Ubiquiti;

use phpseclib\Net\SSH2;
use Poller\DeviceMappers\BaseDeviceMapper;
use Poller\Log;
use Poller\Models\SnmpResult;
use Poller\Services\Formatter;
use Poller\Web\Services\Database;
use Throwable;

class ToughSwitch extends BaseDeviceMapper
{
    public function map(SnmpResult $snmpResult)
    {

        return $this->getBridgingTable(parent::map($snmpResult));
    }

    private function getBridgingTable(SnmpResult $snmpResult):SnmpResult
    {
        $database = new Database();
        $credentials = $database->getCredential(Database::UBIQUITI_TOUGHSWITCH_SSH);
        if ($credentials === null) {
            return $snmpResult;
        }

        $ssh = new SSH2($this->device->getIp(), $credentials['port'], 5);
        $ssh->setTimeout(5);
        if (!$ssh->login($credentials['username'], $credentials['password'])) {
            return $snmpResult;
        }

        $bridgingTableArray = [];

        try {
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
        } catch (Throwable $e) {
            $log = new Log();
            $log->exception($e, [
                'ip' => $this->device->getIp(),
            ]);
        }

        return $snmpResult;
    }
}
