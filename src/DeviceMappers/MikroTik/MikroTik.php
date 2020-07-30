<?php

namespace Poller\DeviceMappers\MikroTik;

use Exception;
use Poller\DeviceMappers\BaseDeviceMapper;
use Poller\Models\SnmpResult;
use Poller\Services\Formatter;

class MikroTik extends BaseDeviceMapper
{
    public function map(SnmpResult $snmpResult)
    {
        return $this->getWirelessClients(parent::map($snmpResult));
    }

    /**
     * @param SnmpResult $snmpResult
     * @return array|mixed
     */
    private function getWirelessClients(SnmpResult $snmpResult):SnmpResult
    {
        try {
            $result = $this->walk("1.3.6.1.4.1.14988.1.1.1.2.1.1");
            foreach ($result as $datum)
            {
                $boom = explode(".", $datum->getOid());
                $interfaceIndex = $boom[count($boom)-1];
                try {
                    $mac = Formatter::formatMac($datum->getValue()->__toString());

                    if(isset($this->interfaces[$interfaceIndex])) {
                        $existingMacs = $this->interfaces[$interfaceIndex]->setConnectedLayer1Macs();
                        $existingMacs[] = $mac;
                        $this->interfaces[$interfaceIndex]->setConnectedLayer1Macs($existingMacs);
                    }
                }
                catch (Exception $e)
                {
                    continue;
                }
            }
        }
        catch (Exception $e)
        {
            //
        }

        $snmpResult->setInterfaces($this->interfaces);
        return $snmpResult;
    }
}
