<?php

namespace Poller\DeviceMappers;

use Exception;
use FreeDSx\Snmp\OidList;
use Leth\IPAddress\IP\Address;
use Poller\Log;
use Poller\Models\Device;
use Poller\Models\Device\NetworkInterface;
use Poller\Models\SnmpResult;
use Throwable;

abstract class BaseDeviceMapper
{
    protected Device $device;
    protected ?OidList $baseData = null;

    //Set to true if this device has an ARP table at the standard SNMP location
    protected bool $hasArp = true;

    //Set this to true if this device has a bridging table at the standard SNMP location
    protected bool $hasBridgingTable = false;

    //Set this to true if this device exposes IPv4/IPv6 address assignments
    protected bool $exposesIpAddresses = true;

    protected array $interfaces = [];

    /**
     * BaseDeviceMapper constructor.
     * @param Device $device
     */
    public function __construct(Device $device)
    {
        $this->device = $device;
    }

    public function map(SnmpResult $snmpResult)
    {
        try {
            $snmpResult->setMetadata($this->populateSystemMetadata());
        } catch (Throwable $e) {
            $log = new Log();
            $log->exception($e);
        }

        try {
            $this->mapInterfaces();
            $snmpResult->setInterfaces($this->interfaces);
        } catch (Throwable $e) {
            $log = new Log();
            $log->exception($e);
        }

        if ($this->device->getType() === Device::NETWORKSITE) {
            try {
                if ($this->hasArp === true) {
                    $this->setArp();
                }
            } catch (Throwable $e) {
                $log = new Log();
                $log->exception($e);
            }

            try {
                if ($this->hasBridgingTable === true) {
                    $this->setBridgingTable();
                }
            } catch (Throwable $e) {
                $log = new Log();
                $log->exception($e);
            }

            try {
                if ($this->exposesIpAddresses === true) {
                    $this->setIpAddresses();
                }
            } catch (Throwable $e) {
                $log = new Log();
                $log->exception($e);
            }
        }

        return $snmpResult;
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
        } catch (Throwable $e) {
            $log = new Log();
            $log->exception($e);
        }

        return new OidList(... $oids);
    }

    /**
     * Set system metadata on the device object
     */
    private function populateSystemMetadata():Device\Metadata
    {
        try {
            $oidList = $this->walk("1.3.6.1.2.1.1");
            $metadata = new Device\Metadata();
            foreach ($oidList as $oid) {
                switch (ltrim($oid->getOid(), ".")) {
                    case "1.3.6.1.2.1.1.1.0":
                        $metadata->setDescription($oid->getValue());
                        break;
                    case "1.3.6.1.2.1.1.3.0":
                        $metadata->setUptime($oid->getValue());
                        break;
                    case "1.3.6.1.2.1.1.4.0":
                        $metadata->setContact($oid->getValue());
                        break;
                    case "1.3.6.1.2.1.1.5.0":
                        $metadata->setName($oid->getValue());
                        break;
                    case "1.3.6.1.2.1.1.6.0":
                        $metadata->setLocation($oid->getValue());
                        break;
                    default:
                        break;
                }
            }
            return $metadata;
        } catch (Exception $e) {
            return new Device\Metadata();
        }
    }

    /**
     * Map up the interfaces and set basic data
     */
    private function mapInterfaces()
    {
        $oidList = $this->getBaseData();
        foreach ($oidList as $oid) {
            if (strpos($oid->getOid(), '1.3.6.1.2.1.2.2.1.2.') !== false) {
                $boom = explode(".", $oid->getOid());
                $interfaceID = $boom[count($boom) - 1];
                if (!isset($this->interfaces[$interfaceID])) {
                    $this->interfaces[$interfaceID] = new NetworkInterface($interfaceID);
                }

                $this->interfaces[$interfaceID]->setName($oid->getValue()->__toString());

                $macOid = $oidList->get('1.3.6.1.2.1.2.2.1.6.' . $interfaceID);
                if ($macOid) {
                    $this->interfaces[$interfaceID]->setMacAddress($macOid->getValue()->__toString());
                }

                $statusOid = $oidList->get('1.3.6.1.2.1.2.2.1.8.' . $interfaceID);
                if ($statusOid) {
                    $this->interfaces[$interfaceID]->setStatus((bool)$statusOid->getValue()->__toString());
                }

                $speedOid = $oidList->get('1.3.6.1.2.1.2.2.1.5.' . $interfaceID);
                if ($speedOid) {
                    $speed = $speedOid->getValue()->__toString();
                    if (is_numeric($speed) && $speed > 0) {
                        $this->interfaces[$interfaceID]->setSpeedIn((int)ceil($speed/1000**2));
                        $this->interfaces[$interfaceID]->setSpeedOut((int)ceil($speed/1000**2));
                    }
                }

                $typeOid = $oidList->get('1.3.6.1.2.1.2.2.1.3.' . $interfaceID);
                if ($typeOid) {
                    $this->interfaces[$interfaceID]->setType($typeOid->getValue()->__toString());
                }
            }
        }
    }

    /**
     * @return OidList
     */
    private function getBaseData(): OidList
    {
        if ($this->baseData === null) {
            $this->baseData = $this->walk('1.3.6.1.2.1.2.2.1');
        }
        return $this->baseData;
    }

    private function setArp()
    {
        $result = $this->walk("1.3.6.1.2.1.4.22.1.2");
        $arp = [];
        foreach ($result as $oid) {
            $key = ltrim($oid->getOid(), ".");
            $boom = explode(".", $key);
            $interfaceID = $boom[count($boom) - 5];
            if (isset($this->interfaces[$interfaceID])) {
                if (!isset($arp[$interfaceID])) {
                    $arp[$interfaceID] = [];
                }
                $arp[$interfaceID][] = $oid->getValue()->__toString();
            }
        }

        foreach ($arp as $interfaceID => $macs) {
            $this->interfaces[$interfaceID]->setConnectedLayer3Macs($macs);
        }
    }

    private function setBridgingTable()
    {
        $result = $this->walk('1.3.6.1.2.1.17.4.3.1');
        $mappings = [];
        foreach ($result as $oid) {
            if (strpos($oid->getOid(), '1.3.6.1.2.1.17.4.3.1.2.') !== false) {
                $key = ltrim($oid->getOid(), ".");
                $boom = explode(".", $key, 12);
                $mappings[$boom[11]] = $oid->getValue()->__toString();
            }
        }

        $bridgingTables = [];
        foreach ($result as $oid) {
            if (strpos($oid->getOid(), '1.3.6.1.2.1.17.4.3.1.1.') !== false) {
                $key = ltrim($oid->getOid(), ".");
                $boom = explode(".", $key, 12);
                if (isset($mappings[$boom[11]]) && isset($this->interfaces[$mappings[$boom[11]]])) {
                    if (!isset($bridgingTables[$mappings[$boom[11]]])) {
                        $bridgingTables[$mappings[$boom[11]]] = [];
                    }
                    $bridgingTables[$mappings[$boom[11]]][] = $oid->getValue()->__toString();
                }
            }
        }

        foreach ($bridgingTables as $interfaceID => $macs) {
            $this->interfaces[$interfaceID]->setConnectedLayer2Macs($macs);
        }
    }

    private function setIpAddresses()
    {
        //IPv4
        $result = $this->walk('1.3.6.1.2.1.4.20.1');
        $ipv4Results = [];
        foreach ($result as $oid) {
            $key = ltrim($oid->getOid(), ".");
            $key = str_replace("1.3.6.1.2.1.4.20.1.", "", $key);
            $boom = explode(".", $key, 2);

            if (!isset($ipv4Results[$boom[1]])) {
                $ipv4Results[$boom[1]] = [
                    'ip' => null,
                    'index' => null,
                    'subnet' => null,
                ];
            }

            switch ($boom[0]) {
                //If 1, it's the IP. If 2, it's the interface index. If 3, it's the subnet mask.
                case 1:
                    $ipv4Results[$boom[1]]['ip'] = $oid->getValue()->__toString();
                    break;
                case 2:
                    $ipv4Results[$boom[1]]['index'] = $oid->getValue()->__toString();
                    break;
                case 3:
                    $ipv4Results[$boom[1]]['subnet'] = $this->maskToCidr($oid->getValue()->__toString());
                    break;
                default:
                    continue 2;
            }
        }

        //IPv6
        $ipv6Results = [];
        $result = $this->walk("1.3.6.1.2.1.55.1.8");
        foreach ($result as $oid) {
            $key = ltrim($oid->getOid(), ".");
            $key = str_replace("1.3.6.1.2.1.55.1.8.1.", "", $key);
            $boom = explode(".", $key, 3);

            if (!isset($resultsToBeInserted[$boom[1]])) {
                $resultsToBeInserted[$boom[1]] = [
                    'ip' => null,
                    'index' => $boom[1],
                    'subnet' => null,
                ];
            }

            switch ($boom[0]) {
                //If  1, it's the IP. If 2, it's the prefix.
                case 1:
                    $address = Address::factory($oid->getValue()->__toString());
                    $resultsToBeInserted[$boom[1]]['ip'] = $address->__toString();
                    break;
                case 2:
                    $resultsToBeInserted[$boom[1]]['subnet'] = preg_replace(
                        "/[^0-9]/",
                        "",
                        $oid->getValue()->__toString()
                    );
                    break;
                default:
                    continue 2;
            }
        }

        $ips = [];
        foreach (array_merge(array_values($ipv6Results), array_values($ipv4Results)) as $resultToBeInserted) {
            if (isset($this->interfaces[$resultToBeInserted['index']])) {
                if (!isset($ips[$resultToBeInserted['index']])) {
                    $ips[$resultToBeInserted['index']] = [];
                }
                $ips[$resultToBeInserted['index']][] = $resultToBeInserted['ip'] . '/' . $resultToBeInserted['subnet'];
            }
        }

        foreach ($ips as $id => $ipAddresses) {
            $this->interfaces[$id]->setIpAddresses($ipAddresses);
        }
    }

    /**
     * @param string $mask
     * @return int
     */
    private function maskToCidr(string $mask): int
    {
        $long = ip2long($mask);
        $base = ip2long('255.255.255.255');
        return 32 - log(($long ^ $base) + 1, 2);
    }
}
