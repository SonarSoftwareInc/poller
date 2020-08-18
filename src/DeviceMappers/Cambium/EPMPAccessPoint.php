<?php

namespace Poller\DeviceMappers\Cambium;

use Exception;
use Poller\DeviceMappers\BaseDeviceMapper;
use Poller\Log;
use Poller\Models\SnmpResult;
use Poller\Services\Formatter;
use Throwable;

class EPMPAccessPoint extends BaseDeviceMapper
{
    public function map(SnmpResult $snmpResult)
    {
        return $this->setConnectedRadios(parent::map($snmpResult));
    }

    private function setConnectedRadios($snmpResult):SnmpResult
    {
        $interfaces = $this->interfaces;
        foreach ($interfaces as $id => $deviceInterface) {
            if (strpos($deviceInterface->getName(),"WLAN") !== false) {
                $existingMacs = $deviceInterface->getConnectedLayer1Macs();

                try {
                    $result = $this->walk("1.3.6.1.4.1.17713.21.1.2.30.1.1");
                    foreach ($result as $datum) {
                        $existingMacs[] = Formatter::formatMac($datum->getValue()->__toString());
                    }
                } catch (Exception $e) {
                    $log = new Log();
                    $log->exception($e);
                }

                //If this is a station (slave end of a backhaul, for example) we need to query this OID as well
                try {
                    $result = $this->device->getSnmpClient()->getValue("1.3.6.1.4.1.17713.21.1.2.19.0");
                    $existingMacs[] = Formatter::formatMac($result);
                } catch (Throwable $e) {
                    $log = new Log();
                    $log->exception($e);
                }

                $interfaces[$id]->setConnectedLayer1Macs($existingMacs);
                break;
            }
        }

        $snmpResult->setInterfaces($interfaces);
        return $snmpResult;
    }
}
