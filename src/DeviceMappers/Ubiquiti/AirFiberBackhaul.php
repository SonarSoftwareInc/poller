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

/**
 * af11, af24, af5x
 */

class AirFiberBackhaul extends BaseDeviceMapper
{
    const OID_REMOTE_MAC = '1.3.6.1.4.1.41112.1.3.2.1.45.1';
    const OID_RXCAPACITY = '1.3.6.1.4.1.41112.1.3.2.1.5.1';
    const OID_TXCAPACITY = '1.3.6.1.4.1.41112.1.3.2.1.6.1';

    public function map(SnmpResult $snmpResult): SnmpResult
    {
        $snmpResult = parent::map($snmpResult);

        if ($air0 = $snmpResult->getInterfaceByName('air0')) {
            if ($remoteMac = $this->getRemoteBackhaulMac()) {
                $air0->setConnectedLayer1Macs([$remoteMac]);
            }

            if ($wirelessCapacities = $this->getWirelessCapacities()) {
                $air0->setSpeedOut($wirelessCapacities['tx']);
                $air0->setSpeedIn($wirelessCapacities['rx']);
            }

            return $snmpResult;
        }

        throw new \RuntimeException(
            "air0 interface is non-existent on device ".$this->device->getIp().". Either not an AirFiber device or there was an SNMP error."
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
                ->get(self::OID_TXCAPACITY)
                ->get(self::OID_TXCAPACITY);

            $rxCapacity = $this->device
                ->getSnmpClient()
                ->get(self::OID_RXCAPACITY)
                ->get(self::OID_RXCAPACITY);

            return [
                'tx' => $txCapacity/1000**2,
                'rx' => $rxCapacity/1000**2,
            ];
        } catch (SnmpException $e) {
            return null;
        }

    }

    private function getRemoteBackhaulMac(): ?string
    {
        try {
            $remoteMac = $this->device
                ->getSnmpClient()
                ->get(self::OID_REMOTE_MAC)
                ->get(self::OID_REMOTE_MAC);

            return $remoteMac ? Formatter::formatMac($remoteMac) : null;
        } catch (SnmpException $e) {
            return null;
        }
    }
}
