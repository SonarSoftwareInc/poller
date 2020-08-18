<?php

namespace Poller\DeviceIdentifiers;

use Exception;
use FreeDSx\Snmp\Exception\SnmpRequestException;
use FreeDSx\Snmp\OidList;
use Poller\DeviceMappers\Ubiquiti\AirFiberBackhaul;
use Poller\DeviceMappers\Ubiquiti\AirMaxAccessPoint;
use Poller\DeviceMappers\Ubiquiti\ToughSwitch;
use Poller\Log;
use Poller\Models\Device;

class Ubiquiti implements IdentifierInterface
{
    private Device $device;
    public function __construct(Device $device)
    {
        $this->device = $device;
    }

    public function getMapper()
    {
        $interfaces = [];
        try {
            $oids = $this->walk('1.3.6.1.2.1.2.2.1.2');
            foreach ($oids as $oid) {
                $interfaces[strtolower($oid->getValue()->__toString())] = true;
            }
            if (isset($interfaces['air0'])) {
                return new AirFiberBackhaul($this->device);
            } else if (isset($interfaces['wifi0'])) {
                return new AirMaxAccessPoint($this->device);
            } else {
                return new ToughSwitch($this->device);
            }
        } catch (Exception $e) {
            return new AirMaxAccessPoint($this->device);
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
            while (!$walk->isComplete()) {
                $oids[] = $walk->next();
            }
        } catch (Exception $e) {
            $log = new Log();
            $log->exception($e, [
                'ip' => $this->device->getIp(),
                'oid' => $oid
            ]);
        }

        return new OidList(... $oids);
    }
}
