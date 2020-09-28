<?php

namespace Poller\DeviceIdentifiers;

use Exception;
use Poller\DeviceMappers\Ubiquiti\AirFiberBackhaul;
use Poller\DeviceMappers\Ubiquiti\AirMaxAccessPoint;
use Poller\DeviceMappers\Ubiquiti\ToughSwitch;
use Poller\Services\Log;
use Poller\Models\Device;
use Poller\Models\SnmpResponse;
use Throwable;

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
            foreach ($oids->getAll() as $oid => $value) {
                $interfaces[strtolower($value)] = true;
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
     * @return SnmpResponse
     */
    protected function walk(string $oid):SnmpResponse
    {
        try {
            return $this->device->getSnmpClient()->walk($oid);
        } catch (Throwable $e) {
            $log = new Log();
            $log->exception($e, [
                'ip' => $this->device->getIp(),
                'oid' => $oid
            ]);
            return new SnmpResponse([]);
        }
    }
}
