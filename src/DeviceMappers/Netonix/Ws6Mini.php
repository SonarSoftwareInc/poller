<?php

namespace Poller\DeviceMappers\Netonix;

use phpseclib\Net\SSH2;
use Poller\DeviceMappers\BaseDeviceMapper;
use Poller\Models\SnmpResult;
use Poller\Services\Formatter;

class Ws6Mini extends BaseDeviceMapper
{
    public function map(SnmpResult $snmpResult)
    {
        return $this->getInterfaceMacAddresses(parent::map($snmpResult));
    }

    private function getInterfaceMacAddresses(SnmpResult $snmpResult):SnmpResult
    {
        $ssh = new SSH2($snmpResult->getIp());
        $ssh->setTimeout(5);
        if (!$ssh->login('username', 'password')) {
            return $snmpResult;
        }

        $readInterfaces = [];

        $ssh->read('[#]', SSH2::READ_REGEX);
        $ssh->write("cmdline\n");
        $ssh->read('(BusyBox)', SSH2::READ_REGEX);
        $interfaceMacs = $ssh->exec("ip -o link | awk '$2 != \"lo:\" {print $2, $(NF-2)}' | grep -v @\n");
        $separator = "\r\n";
        $line = strtok($interfaceMacs, $separator);
        while ($line !== false) {
            $boom = explode(' ', $interfaceMacs);
            $interface = str_replace(':', '', $boom[0]);
            $mac = Formatter::formatMac($boom[1]);
            $readInterfaces[strtolower($interface)] = $mac;
            $line = strtok($separator);
        }

        $interfaces = $snmpResult->getInterfaces();
        foreach ($interfaces as $key => $interface) {
            if (isset($readInterfaces[strtolower($interface->getName())])) {
                $interfaces[$key]->setMacAddress($readInterfaces[strtolower($interface->getName())]);
            }
        }

        $snmpResult->setInterfaces($interfaces);
        return $snmpResult;
    }
}
