<?php

namespace Poller\DeviceMappers\Ubiquiti;

use Exception;
use Poller\DeviceMappers\BaseDeviceMapper;
use Poller\Log;
use Poller\Models\SnmpResult;
use Poller\Services\Formatter;

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
        $interfaces = $snmpResult->getInterfaces();
        try {
            $capacity = $this->device->getSnmpClient()->get("1.3.6.1.4.1.41112.1.3.2.1.5");
            if ($capacity->has('1.3.6.1.4.1.41112.1.3.2.1.5')) {
                $interfaces[$this->air0]->setInterfaceSpeed(
                    ($capacity->get('1.3.6.1.4.1.41112.1.3.2.1.5')->getValue()->__toString())/1000**2
                );
            }
        } catch (Exception $e)
        {
            //
        }

        $snmpResult->setInterfaces($interfaces);
        return $snmpResult;
    }

    /**
     * @param SnmpResult $snmpResult
     * @return SnmpResult
     */
    private function getRemoteBackhaulMac(SnmpResult $snmpResult):SnmpResult
    {
        $interfaces = $snmpResult->getInterfaces();
        $existingMacs = $interfaces[$this->air0]->getConnectedLayer1Macs();

        try {
            $result = $this->walk("1.3.6.1.4.1.41112.1.3.2.1.45");
            foreach ($result as $oid)
            {
                try {
                    $existingMacs[] = Formatter::formatMac($oid->getValue()->__toString());
                } catch (Exception $e) {
                    $log = new Log();
                    $log->error($e->getTraceAsString());
                }
            }

        }
        catch (Exception $e) {
            $log = new Log();
            $log->error($e->getTraceAsString());
        }

        $interfaces[$this->air0]->setConnectedLayer1Macs($existingMacs);
        $snmpResult->setInterfaces($interfaces);

        return $snmpResult;
    }
}
