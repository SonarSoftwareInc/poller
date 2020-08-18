<?php

namespace Poller\DeviceMappers\Netonix;

use phpseclib\Net\SSH2;
use Poller\DeviceMappers\BaseDeviceMapper;
use Poller\Log;
use Poller\Models\SnmpResult;
use Poller\Services\Formatter;
use Throwable;

class Ws6Mini extends BaseDeviceMapper
{
    public function map(SnmpResult $snmpResult)
    {
        return $this->getInterfaceMacAddresses(parent::map($snmpResult));
    }

    private function getInterfaceMacAddresses(SnmpResult $snmpResult):SnmpResult
    {
        if ($this->device->getSshUsername() === null || $this->device->getSshPassword() === null) {
            return $snmpResult;
        }

        $ssh = new SSH2($snmpResult->getIp(), $this->device->getPort(), 5);
        $ssh->setTimeout(5);
        if (!$ssh->login($this->device->getSshUsername(), $this->device->getSshPassword())) {
            return $snmpResult;
        }

        $interfaces = $snmpResult->getInterfaces();
        $readInterfaces = [];

        try {
            $ssh->read('[#]', SSH2::READ_REGEX);
            $ssh->write("cmdline\n");
            $ssh->read('(BusyBox)', SSH2::READ_REGEX);
            $interfaceMacs = $ssh->exec("ip -o link | awk '$2 != \"lo:\" {print $2, $(NF-2)}' | grep -v @\n");
            $separator = "\r\n";
            $line = strtok($interfaceMacs, $separator);
            while ($line !== false) {
                $boom = explode(' ', $interfaceMacs);
                $interface = str_replace(':', '', $boom[0]);
                $interface = str_replace('eth', '', $interface);
                $interface = 'Port ' . (string)((int)$interface+1);
                $mac = Formatter::formatMac($boom[1]);
                $readInterfaces[strtolower($interface)] = $mac;
                $line = strtok($separator);
            }

            foreach ($interfaces as $key => $interface) {
                if (isset($readInterfaces[strtolower($interface->getName())])) {
                    $interfaces[$key]->setMacAddress($readInterfaces[strtolower($interface->getName())]);
                }
            }
        } catch (Throwable $e) {
            $log = new Log();
            $log->exception($e, [
                'ip' => $this->device->getIp(),
            ]);
        }


        $snmpResult->setInterfaces($interfaces);
        return $snmpResult;
    }
}
