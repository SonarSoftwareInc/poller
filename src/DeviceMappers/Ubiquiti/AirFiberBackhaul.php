<?php

namespace Poller\DeviceMappers\Ubiquiti;

use Exception;
use Poller\DeviceMappers\BaseDeviceMapper;
use Poller\Services\Log;
use Poller\Models\SnmpResult;
use Poller\Services\Formatter;
use Throwable;

class AirFiberBackhaul extends BaseDeviceMapper
{
    private int $air0 = 1;

    public function map(SnmpResult $snmpResult)
    {
        $interfaces = $snmpResult->getInterfaces();
        foreach ($interfaces as $key => $interface) {
            if (strpos($interface->getName(), "air0") !== false) {
                $this->air0 = (int)$key;
                break;
            }
        }
        $snmpResult = $this->getWirelessCapacity(parent::map($snmpResult));
        return $this->getRemoteBackhaulMac($snmpResult);
    }

    /**
     * Get the capacity of the wireless interface
     * @param SnmpResult $snmpResult
     * @return SnmpResult
     */
    private function getWirelessCapacity(SnmpResult $snmpResult):SnmpResult
    {
        if (isset($interfaces[$this->air0])) {
            $interfaces = $snmpResult->getInterfaces();
            try {
                $capacity = $this->device->getSnmpClient()->get("1.3.6.1.4.1.41112.1.3.2.1.5");
                $interfaces[$this->air0]->setSpeedIn(($capacity->get("1.3.6.1.4.1.41112.1.3.2.1.5"))/1000**2);
                $interfaces[$this->air0]->setSpeedOut($interfaces[$this->air0]->getSpeedIn());
            } catch (Throwable $e) {
                $log = new Log();
                $log->exception($e, [
                    'ip' => $this->device->getIp(),
                    'oid' => '1.3.6.1.4.1.41112.1.3.2.1.5',
                ]);
            }

            $snmpResult->setInterfaces($interfaces);
        }

        return $snmpResult;
    }

    /**
     * @param SnmpResult $snmpResult
     * @return SnmpResult
     */
    private function getRemoteBackhaulMac(SnmpResult $snmpResult):SnmpResult
    {
        $interfaces = $snmpResult->getInterfaces();
        if (isset($interfaces[$this->air0])) {
            $existingMacs = $interfaces[$this->air0]->getConnectedLayer1Macs();

            try {
                $result = $this->walk("1.3.6.1.4.1.41112.1.3.2.1.45");
                foreach ($result->getAll() as $oid => $value)
                {
                    try {
                        $existingMacs[] = Formatter::formatMac($value);
                    } catch (Exception $e) {
                        $log = new Log();
                        $log->exception($e);
                    }
                }

            }
            catch (Exception $e) {
                $log = new Log();
                $log->exception($e);
            }

            $interfaces[$this->air0]->setConnectedLayer1Macs($existingMacs);
            $snmpResult->setInterfaces($interfaces);
        }

        return $snmpResult;
    }
}
