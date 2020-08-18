<?php

namespace Poller\DeviceMappers\Ubiquiti;

use Exception;
use Poller\DeviceMappers\BaseDeviceMapper;
use Poller\Log;
use Poller\Models\SnmpResult;
use Poller\Services\Formatter;
use Throwable;

class AirMaxAccessPoint extends BaseDeviceMapper
{
    public function map(SnmpResult $snmpResult)
    {
        return $this->setConnectedRadios(parent::map($snmpResult));
    }

    /**
     * @param SnmpResult $snmpResult
     * @return array|mixed
     */
    private function setConnectedRadios(SnmpResult $snmpResult):SnmpResult
    {
        $interfaces = $snmpResult->getInterfaces();
        foreach ($interfaces as $key => $deviceInterface) {
            if (strpos($deviceInterface->getName(),"wifi") !== false) {
                $existingMacs = $interfaces[$key]->getConnectedLayer1Macs();

                try {
                    $result = $this->walk("1.3.6.1.4.1.41112.1.4.7.1.1");
                    foreach ($result as $oid) {
                        try {
                            $existingMacs[] = Formatter::formatMac($oid->getValue()->__toString());
                        } catch (Exception $e) {
                            $log = new Log();
                            $log->exception($e, [
                                'ip' => $this->device->getIp(),
                            ]);
                            continue;
                        }
                    }
                } catch (Throwable $e) {
                    $log = new Log();
                    $log->exception($e, [
                        'ip' => $this->device->getIp(),
                    ]);
                }

                $interfaces[$key]->setConnectedLayer1Macs($existingMacs);
                $snmpResult->setInterfaces($interfaces);
                break;
            }
        }

        return $snmpResult;
    }
}
