<?php

namespace Poller\DeviceMappers\Cambium;

use Poller\DeviceMappers\BaseDeviceMapper;
use Poller\Log;
use Poller\Models\SnmpResult;
use Throwable;

class CanopyPMPAccessPoint extends BaseDeviceMapper
{
    public function map(SnmpResult $snmpResult)
    {
        return $this->setConnectedRadios(parent::map($snmpResult));
    }

    private function setConnectedRadios($snmpResult):SnmpResult
    {
        $interfaces = $snmpResult->getInterfaces();
        foreach ($interfaces as $key => $interface) {
            if (strpos($interface->getName(), "MultiPoint") !== false) {
                $existingMacs = $interfaces[$key]->getConnectedLayer1Macs();
                $registeredStates = [];

                try {
                    $result = $this->walk("1.3.6.1.4.1.161.19.3.1.4.1.3");
                    $states = $this->walk("1.3.6.1.4.1.161.19.3.1.4.1.19");

                    foreach ($states->getAll() as $oid => $value) {
                        if ($value === '1') {
                            $boom = explode(".", $oid);
                            $registeredStates[] = $boom[count($boom) - 1];
                        }
                    }

                    foreach ($result->getAll() as $oid => $value) {
                        $boom = explode(".", $oid);
                        if (in_array($boom[count($boom) - 1], $registeredStates) && $value) {
                            $existingMacs[] = $value;
                            //Manipulate this to be the 2A and 3A as well, in case the SM itself isn't being monitored
                            $existingMacs[] =  "2" . substr($value, 1);
                            $existingMacs[] = "3" . substr($value, 1);
                        }
                    }

                    $interface->setConnectedLayer1Macs($existingMacs);
                    $interfaces[$key] = $interface;
                    break;
                } catch (Throwable $e) {
                    $log = new Log();
                    $log->exception($e, [
                        'ip' => $this->device->getIp()
                    ]);
                }
            }
        }

        $snmpResult->setInterfaces($interfaces);
        return $snmpResult;
    }
}
