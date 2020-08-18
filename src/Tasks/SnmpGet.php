<?php

namespace Poller\Tasks;

use Amp\Parallel\Worker\Environment;
use Amp\Parallel\Worker\Task;
use Poller\DeviceIdentifiers\IdentifierInterface;
use Poller\Exceptions\SnmpException;
use Poller\Models\Device;
use Poller\Models\MonitoringTemplate;
use Poller\Models\SnmpError;
use Poller\Models\SnmpResult;
use Poller\Services\SysObjectIDMatcher;
use Throwable;

class SnmpGet implements Task
{
    private Device $device;
    private SysObjectIDMatcher $matcher;

    /**
     * SnmpGet constructor.
     * @param Device $device
     * @param SysObjectIDMatcher $matcher
     */
    public function __construct(Device $device, SysObjectIDMatcher $matcher)
    {
        $this->device = $device;
        $this->matcher = $matcher;
    }

    /**
     * @param Environment $environment
     * @return SnmpError|SnmpResult
     */
    public function run(Environment $environment)
    {
        $snmp = $this->device->getSnmpClient();
        try {
            $snmpResult = new SnmpResult(
                $snmp->get($this->device->getMonitoringTemplate()->getOids()),
                $this->device->getIp()
            );

            $className = $this->matcher->getClass($snmpResult->getResults()->get(MonitoringTemplate::SYSTEM_SYSOBJECT_ID));
            if ($className !== null) {
                $mapper = new $className($this->device);
                if ($mapper instanceof IdentifierInterface) {
                    $mapper = $mapper->getMapper();
                }
                $snmpResult = $mapper->map($snmpResult);
            }

            return $snmpResult;
        } catch (SnmpException $e) {
            return new SnmpError(true, $e->getMessage(), $this->device->getIp());
        } catch (Throwable $e) {
            return new SnmpError(false, $e->getMessage(), $this->device->getIp());
        } finally {
            unset($snmp);
        }
    }
}
