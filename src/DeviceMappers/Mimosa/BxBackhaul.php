<?php

namespace Poller\DeviceMappers\Mimosa;

use Exception;
use InvalidArgumentException;
use Poller\DeviceMappers\BaseDeviceMapper;
use Poller\Log;
use Poller\Models\SnmpResult;
use Poller\Services\Formatter;

class BxBackhaul extends BaseDeviceMapper
{
    public function map(SnmpResult $snmpResult)
    {
        return $this->getRemoteBackhaul(parent::map($snmpResult));
    }

    /**
     * @param SnmpResult $snmpResult
     * @return SnmpResult
     */
    private function getRemoteBackhaul(SnmpResult $snmpResult):SnmpResult
    {
        $keyToUse = 5;
        $interfaces = $snmpResult->getInterfaces();
        foreach ($interfaces as $key => $interface) {
            if (strpos($interface->getName(), "wlan") !== false) {
                $keyToUse = $key;
                break;
            }
        }
        $existingMacs = $interfaces[$keyToUse]->getConnectedLayer1Macs();
        try {
            //This is a pretty lame workaround, but it's the only way we can try to get the far end until firmware upgrades are provided.
            $result = $this->walk("1.3.6.1.2.1.4.22.1.2.5");
            foreach ($result as $oid) {
                try {
                    $mac = Formatter::formatMac($oid->getValue()->__toString());
                } catch (InvalidArgumentException $e) {
                    continue;
                }

                array_push($existingMacs,$mac);
                break;
            }
        } catch (Exception $e) {
            $log = new Log();
            $log->error($e->getTraceAsString());
        }

        $interfaces[$keyToUse]->setConnectedLayer1Macs(array_unique($existingMacs));
        $snmpResult->setInterfaces($interfaces);

        return $snmpResult;
    }
}
