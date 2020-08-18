<?php

namespace Poller\DeviceMappers\Cambium;

use Poller\DeviceMappers\BaseDeviceMapper;
use Poller\Log;
use Poller\Models\SnmpResult;
use Poller\Services\Formatter;
use Throwable;

class PTP250Backhaul extends BaseDeviceMapper
{
    public function map(SnmpResult $snmpResult)
    {
        return $this->getRemoteBackhaul(parent::map($snmpResult));
    }

    private function getRemoteBackhaul(SnmpResult $snmpResult):SnmpResult
    {
        $interfaces = $this->interfaces;
        foreach ($interfaces as $id => $deviceInterface)
        {
            if (strpos($deviceInterface->getName(),"wireless") !== false)
            {
                try {
                    $macs = $deviceInterface->getConnectedLayer1Macs();
                    $result = $this->device->getSnmpClient()->getValue("1.3.6.1.4.1.17713.250.1.4.0");
                    $macs[] = Formatter::formatMac($result);
                    $interfaces[$id]->setConnectedLayer1Macs($macs);
                    break;
                } catch (Throwable $e) {
                    $log = new Log();
                    $log->exception($e);
                }
            }
        }

        $snmpResult->setInterfaces($interfaces);
        return $snmpResult;
    }
}
