<?php

namespace Poller\DeviceIdentifiers;

use Poller\DeviceMappers\Ubiquiti\AirFiber60Backhaul;
use Poller\DeviceMappers\Ubiquiti\AirFiberBackhaul;
use Poller\DeviceMappers\Ubiquiti\AccessPoint;
use Poller\DeviceMappers\Ubiquiti\ToughSwitch;
use Poller\Exceptions\SnmpException;
use Poller\Models\Device;

class Ubiquiti implements IdentifierInterface
{
    const OID_IFDESCR         = '1.3.6.1.2.1.2.2.1.2';
    const OID_AFLTU_DEV_MODEL = '1.3.6.1.4.1.41112.1.10.1.3.2.0';
    const OID_AF60_DEV_MODEL  = '1.3.6.1.4.1.41112.1.11.1.2.2.1';

    private Device $device;

    private array $interfaces;

    /**
     * @throws SnmpException
     */
    public function __construct(Device $device)
    {
        $this->device = $device;

        $this->interfaces = $this->getInterfaceNamesAsKeys();
        print_r($this->interfaces);
    }

    public function getMapper()
    {
        if ($this->isAirFiber()) {
            return new AirFiberBackhaul($this->device);
        } else if ($this->isAirMax()) {
            return new AccessPoint($this->device);
        } else if ($this->isLtu()) {
            return new AccessPoint($this->device, AccessPoint::OID_AFLTU_STA_REMOTE_MAC);
        } else if ($this->isAirFiber60()) {
            return new AirFiber60Backhaul($this->device);
        } else {
            return new ToughSwitch($this->device);
        }
    }

    private function isAirFiber()
    {
        return isset($this->interfaces['air0']);
    }

    private function isAirFiber60(): bool
    {
        try {
            $af60Model = $this->device
                ->getSnmpClient()
                ->get(self::OID_AF60_DEV_MODEL)
                ->get(self::OID_AF60_DEV_MODEL);

            return !empty($af60Model);
        } catch (SnmpException $e) {
            //
        }

        return false;
    }

    private function isAirMax()
    {
        return isset($this->interfaces['wifi0']);
    }

    private function isLtu(): bool
    {
        try {
            $ltuModel = $this->device
                ->getSnmpClient()
                ->get(self::OID_AFLTU_DEV_MODEL)
                ->get(self::OID_AFLTU_DEV_MODEL);

            return !empty($ltuModel);
        } catch (SnmpException $e) {
            //
        }

        return false;
    }

    /**
     * @throws SnmpException
     */
    private function getInterfaceNamesAsKeys(): array
    {
        return \array_fill_keys(
            $this->device->getSnmpClient()->walk(self::OID_IFDESCR)->getAll(),
            true
        );
    }
}
