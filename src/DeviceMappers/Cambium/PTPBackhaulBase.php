<?php

namespace Poller\DeviceMappers\Cambium;

use Poller\DeviceMappers\BaseDeviceMapper;
use Poller\Log;
use Poller\Models\SnmpResult;
use Poller\Services\Formatter;
use Throwable;

abstract class PTPBackhaulBase extends BaseDeviceMapper
{
    protected function getRemoteBackhaul(SnmpResult $snmpResult, $oid):SnmpResult
    {
        $interfaces = $snmpResult->getInterfaces();
        foreach ($interfaces as $id => $deviceInterface)
        {
            if (strpos($deviceInterface->getName(),"wireless") !== false)
            {
                $macs = $deviceInterface->getConnectedLayer1Macs();
                try {
                    $result = $this->device->getSnmpClient()->getValue($oid);
                    $existingMacs[] = Formatter::formatMac($result);
                } catch (Throwable $e) {
                    $log = new Log();
                    $log->exception($e, [
                        'ip' => $this->device->getIp(),
                        'oid' => $oid
                    ]);
                }

                $interfaces[$id]->setConnectedLayer1Macs($macs);
                break;
            }
        }

        $snmpResult->setInterfaces($interfaces);
        return $snmpResult;
    }
}
