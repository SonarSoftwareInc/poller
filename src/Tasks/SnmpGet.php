<?php

namespace Poller\Tasks;

use Amp\Parallel\Worker\Environment;
use Amp\Parallel\Worker\Task;
use Exception;
use FreeDSx\Snmp\Exception\ConnectionException;
use Poller\DeviceIdentifiers\IdentifierInterface;
use Poller\Models\Device;
use Poller\Models\MonitoringTemplate;
use Poller\Models\SnmpError;
use Poller\Models\SnmpResult;
use Poller\Services\SysObjectIDMatcher;

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
                $snmp->get(...$this->device->getMonitoringTemplate()->getOids()),
                $this->device->getIp()
            );

            $oid = $snmpResult->getResults()->get(MonitoringTemplate::SYSTEM_SYSOBJECT_ID);
            if ($oid && $oid->getValue()) {
                $className = $this->matcher->getClass($oid->getValue()->__toString());
                if ($className !== null) {
                    $mapper = new $className($this->device);
                    if ($mapper instanceof IdentifierInterface) {
                        $mapper = $mapper->getMapper();
                    }
                    $snmpResult = $mapper->map($snmpResult);
                }
            }

            return $snmpResult;
        } catch (ConnectionException $e) {
            return new SnmpError(true, $e->getMessage());
        } catch (Exception $e) {
            return new SnmpError(false, $e->getMessage());
        } finally {
            unset($snmp);
        }
    }
}
