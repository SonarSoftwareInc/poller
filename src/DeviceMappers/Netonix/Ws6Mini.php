<?php

namespace Poller\DeviceMappers\Netonix;

use InvalidArgumentException;
use phpseclib\Net\SSH2;
use Poller\DeviceMappers\BaseDeviceMapper;
use Poller\Services\Log;
use Poller\Models\SnmpResult;
use Poller\Services\Formatter;
use Poller\Web\Services\Database;
use Throwable;

class Ws6Mini extends BaseDeviceMapper
{
    public function map(SnmpResult $snmpResult)
    {
        return $this->getInterfaceMacAddresses(parent::map($snmpResult));
    }

    private function getInterfaceMacAddresses(SnmpResult $snmpResult):SnmpResult
    {
        $database = new Database();
        $credentials = $database->getCredential(Database::NETONIX_SSH);

        if ($credentials === null) {
            return $snmpResult;
        }

        $ssh = new SSH2($this->device->getIp(), $credentials['port'], 3);
        $ssh->setTimeout(3);
        if (!$ssh->login($credentials['username'], $credentials['password'])) {
            return $snmpResult;
        }

        $interfaces = $snmpResult->getInterfaces();
        $readInterfaces = [];

        try {
            $separator = "\r\n";

            $ssh->read('[#]', SSH2::READ_REGEX);
            $ssh->write("show mac table\n");
            $bridgingTable = $ssh->read();

            $line = strtok($bridgingTable, $separator);
            $bridgingMacs = [];
            while ($line !== false) {
                $boom = preg_split('/\s+/', $line);
                if (!is_numeric($boom[1])) {
                    $line = strtok($separator);
                    continue;
                }
                try {
                    if (!isset($bridgingMacs["port {$boom[1]}"])) {
                        $bridgingMacs["port {$boom[1]}"] = [];
                    }
                    $bridgingMacs["port {$boom[1]}"][] = Formatter::formatMac($boom[0]);
                } catch (InvalidArgumentException $e) {
                    continue;
                } finally {
                    $line = strtok($separator);
                }
            }

            $ssh->write("cmdline\n");
            $ssh->read('(BusyBox)', SSH2::READ_REGEX);
            $interfaceMacs = $ssh->exec("ip -o link | awk '$2 != \"lo:\" {print $2, $(NF-2)}' | grep -v @\n");
            $line = strtok($interfaceMacs, $separator);
            //Netonix uses the same MAC for every interface, they are bound to eth0 internally
            $eth0Mac = null;
            while ($line !== false) {
                if (str_contains($line, 'eth0:')) {
                    $boom = explode(' ', $line);
                    $interface = str_replace(':', '', $boom[0]);
                    $interface = str_replace('eth', '', $interface);
                    $eth0Mac = Formatter::formatMac($boom[1]);
                    break;
                }
                $line = strtok($separator);
            }

            foreach ($interfaces as $key => $interface) {
                $interfaces[$key]->setMacAddress($eth0Mac);
                if (isset($bridgingMacs[strtolower($interface->getName())])) {
                    $interfaces[$key]->setConnectedLayer2Macs(array_merge($bridgingMacs[strtolower($interface->getName())], $interfaces[$key]->getConnectedLayer2Macs()));
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
