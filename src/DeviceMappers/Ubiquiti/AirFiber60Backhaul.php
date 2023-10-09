<?php

namespace Poller\DeviceMappers\Ubiquiti;

use Exception;
use Poller\DeviceMappers\BaseDeviceMapper;
use Poller\Exceptions\SnmpException;
use Poller\Models\Device;
use Poller\Services\Log;
use Poller\Models\SnmpResult;
use Poller\Services\Formatter;
use Throwable;

class AirFiber60Backhaul extends BaseDeviceMapper
{
    const BASE_OID_REMOTE_MAC = '1.3.6.1.4.1.41112.1.11.1.3.1.1';
    const BASE_OID_RXCAPACITY = '1.3.6.1.4.1.41112.1.11.1.3.1.8';
    const BASE_OID_TXCAPACITY = '1.3.6.1.4.1.41112.1.11.1.3.1.7';

    public function map(SnmpResult $snmpResult): SnmpResult
    {
        $snmpResult = parent::map($snmpResult);

        if ($ubond0 = $snmpResult->getInterfaceByName('ubond0')) {
            if ($remoteMac = $this->getRemoteBackhaulMac()) {
                $ubond0->setConnectedLayer1Macs([$remoteMac]);
            }

            if ($wirelessCapacities = $this->getWirelessCapacities()) {
                $ubond0->setSpeedOut($wirelessCapacities['tx']);
                $ubond0->setSpeedIn($wirelessCapacities['rx']);
            }

            return $snmpResult;
        }

        throw new \RuntimeException(
            "ubond0 interface is non-existent on device ".$this->device->getIp().". Either not an AirFiber 60 device or there was an SNMP error."
        );
    }

    /**
     * @return array{tx: int, rx: int}|null
     */
    private function getWirelessCapacities(): ?array
    {
        try {
            $txCapacity = $this->device
                ->getSnmpClient()
                ->walk(self::BASE_OID_TXCAPACITY)
                ->getAll();

            $rxCapacity = $this->device
                ->getSnmpClient()
                ->walk(self::BASE_OID_RXCAPACITY)
                ->getAll();

            if ($txCapacity && $rxCapacity) {
                return [
                    'tx' => $txCapacity[\array_key_first($txCapacity)] / 1000,
                    'rx' => $rxCapacity[\array_key_first($rxCapacity)] / 1000,
                ];
            }
        } catch (SnmpException $e) {
            //
        }

        return null;
    }

    private function getRemoteBackhaulMac(): ?string
    {
        try {
            $remoteMac = $this->device
                ->getSnmpClient()
                ->walk(self::BASE_OID_REMOTE_MAC)
                ->getAll();

            if ($remoteMac) {
                return Formatter::formatMac($remoteMac[\array_key_first($remoteMac)]);
            }
        } catch (SnmpException $e) {
            //
        }
        return null;
    }
}
