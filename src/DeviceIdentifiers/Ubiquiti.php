<?php

namespace Poller\DeviceIdentifiers;

use Exception;
use FreeDSx\Snmp\Exception\SnmpRequestException;
use FreeDSx\Snmp\OidList;
use Poller\DeviceMappers\Ubiquiti\AirFiberBackhaul;
use Poller\DeviceMappers\Ubiquiti\AirMaxAccessPoint;
use Poller\DeviceMappers\Ubiquiti\ToughSwitch;
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
        $interfaces = [];
        try {
            $oids = $this->device->getSnmpClient()->walk('1.3.6.1.2.1.2.2.1.2');
            foreach ($oids as $oid) {
                $interfaces[strtolower($oid->getValue()->__toString())] = true;
            }
            if (isset($interfaces['air0'])) {
                return AirFiberBackhaul::class;
            } else if (isset($interfaces['wifi0'])) {
                return AirMaxAccessPoint::class;
            } else {
                return ToughSwitch::class;
            }
        } catch (Exception $e) {
            return AirMaxAccessPoint::class;
        }
    }

    /**
     * @param string $oid
     * @return OidList
     */
    protected function walk(string $oid): OidList
    {
        $oids = [];
        try {
            $walk = $this->device->getSnmpClient()->walk($oid);
            while ($walk->hasOids()) {
                $oids[] = $walk->next();
            }
        } catch (Exception $e) {
            //TODO: log exception
        }

        return new OidList(... $oids);
    }
}
