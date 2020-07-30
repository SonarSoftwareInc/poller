<?php

namespace Poller\DeviceIdentifiers;

use FreeDSx\Snmp\Exception\SnmpRequestException;
use Poller\DeviceMappers\Ubiquiti\AirFiberBackhaul;
use Poller\DeviceMappers\Ubiquiti\AirMaxAccessPoint;
use Poller\Models\Device;

class Ubiquiti implements IdentifierInterface
{
    private Device $device;
    public function __construct(Device $device)
    {
        $this->device = $device;
    }

    public function getMapper():string
    {
        try {
            $oids = $this->device->getSnmpClient()->get('1.3.6.1.4.1.41112.1.3.1.1.2');
        } catch (SnmpRequestException $e) {
            return AirMaxAccessPoint::class;
        }
        return $oids->count() > 0 ? new AirFiberBackhaul($this->device) : new AirMaxAccessPoint($this->device);
    }
}
