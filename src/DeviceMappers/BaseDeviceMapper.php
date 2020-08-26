<?php

namespace Poller\DeviceMappers;

use Exception;
use Leth\IPAddress\IP\Address;
use Poller\Exceptions\SnmpException;
use Poller\Log;
use Poller\Models\Device;
use Poller\Models\Device\Metadata;
use Poller\Models\Device\NetworkInterface;
use Poller\Models\SnmpResponse;
use Poller\Models\SnmpResult;
use Poller\Services\Formatter;
use Throwable;

abstract class BaseDeviceMapper
{
    protected Device $device;
    protected ?SnmpResponse $baseData = null;

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
            $log->exception($e, [
                'ip' => $this->device->getIp()
            ]);
        }

        try {
            $this->mapInterfaces();
        } catch (Throwable $e) {
            $log = new Log();
            $log->exception($e, [
                'ip' => $this->device->getIp()
            ]);
        }

        if ($this->device->getType() === Device::NETWORKSITE) {
            try {
                if ($this->hasArp === true) {
                    $this->setArp();
                }
            } catch (Throwable $e) {
                $log = new Log();
                $log->exception($e, [
                    'ip' => $this->device->getIp()
                ]);
            }

            try {
                if ($this->hasBridgingTable === true) {
                    $this->setBridgingTable();
                }
            } catch (Throwable $e) {
                $log = new Log();
                $log->exception($e, [
                    'ip' => $this->device->getIp()
                ]);
            }

            try {
                if ($this->exposesIpAddresses === true) {
                    $this->setIpAddresses();
                }
            } catch (Throwable $e) {
                $log = new Log();
                $log->exception($e, [
                    'ip' => $this->device->getIp()
                ]);
            }
        }

        $snmpResult->setInterfaces($this->interfaces);
        return $snmpResult;
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

    /**
     * Set system metadata on the device object
     */
    private function populateSystemMetadata():Metadata
    {
        $metadata = new Metadata();
        try {
            $oidList = $this->walk("1.3.6.1.2.1.1");
            foreach ($oidList->getAll() as $oid => $value) {
                switch ($oid) {
                    case "1.3.6.1.2.1.1.1.0":
                        $metadata->setDescription($value);
                        break;
                    case "1.3.6.1.2.1.1.3.0":
                        $metadata->setUptime($value);
                        break;
                    case "1.3.6.1.2.1.1.4.0":
                        $metadata->setContact($value);
                        break;
                    case "1.3.6.1.2.1.1.5.0":
                        $metadata->setName($value);
                        break;
                    case "1.3.6.1.2.1.1.6.0":
                        $metadata->setLocation($value);
                        break;
                    default:
                        break;
                }
            }
            return $metadata;
        } catch (Exception $e) {
            $log = new Log();
            $log->exception($e, [
                'ip' => $this->device->getIp()
            ]);
        } finally {
            return $metadata;
        }
    }

    /**
     * Map up the interfaces and set basic data
     */
    private function mapInterfaces()
    {
        $log = new Log();
        $oidList = $this->getBaseData();
        foreach ($oidList->getAll() as $oid => $value) {
            if (strpos($oid, '1.3.6.1.2.1.2.2.1.2.') !== false) {
                try {
                    $boom = explode(".", $oid);
                    $interfaceID = $boom[count($boom) - 1];
                    if (!isset($this->interfaces[$interfaceID])) {
                        $this->interfaces[$interfaceID] = new NetworkInterface($interfaceID);
                    }

                    $this->interfaces[$interfaceID]->setName($value);

                    try {
                        if (Formatter::validateMac($oidList->get('1.3.6.1.2.1.2.2.1.6.' . $interfaceID)) === true) {
                            $this->interfaces[$interfaceID]->setMacAddress($oidList->get('1.3.6.1.2.1.2.2.1.6.' . $interfaceID));
                        }
                    } catch (SnmpException $e) {
                        $log->exception($e, [
                            'ip' => $this->device->getIp()
                        ]);
                    }

                    try {
                        $this->interfaces[$interfaceID]->setStatus((bool)$oidList->get('1.3.6.1.2.1.2.2.1.8.' . $interfaceID));
                    } catch (SnmpException $e) {
                        $log->exception($e, [
                            'ip' => $this->device->getIp()
                        ]);
                    }

                    try {
                        $speed = $oidList->get('1.3.6.1.2.1.2.2.1.5.' . $interfaceID);
                        if (is_numeric($speed) && $speed > 0) {
                            $this->interfaces[$interfaceID]->setSpeedIn((int)ceil($speed/1000**2));
                            $this->interfaces[$interfaceID]->setSpeedOut((int)ceil($speed/1000**2));
                        }
                    } catch (SnmpException $e) {
                        $log->exception($e, [
                            'ip' => $this->device->getIp()
                        ]);
                    }

                    try {
                        $this->interfaces[$interfaceID]->setType($oidList->get('1.3.6.1.2.1.2.2.1.3.' . $interfaceID));
                    } catch (SnmpException $e) {
                        $log->exception($e, [
                            'ip' => $this->device->getIp()
                        ]);
                    }

                    //Try 64bit counters first, then fall back to 32bit
                    try {
                        $this->interfaces[$interfaceID]->setOctetsIn($oidList->get('1.3.6.1.2.1.31.1.1.1.6.' . $interfaceID));
                    } catch (Throwable $e) {
                        try {
                            $this->interfaces[$interfaceID]->setOctetsIn($oidList->get('1.3.6.1.2.1.2.2.1.10.' . $interfaceID));
                        } catch (Throwable $e) {
                            $log->exception($e, [
                                'ip' => $this->device->getIp()
                            ]);
                        }
                    }

                    try {
                        $this->interfaces[$interfaceID]->setOctetsOut($oidList->get('1.3.6.1.2.1.31.1.1.1.10.' . $interfaceID));
                    } catch (Throwable $e) {
                        try {
                            $this->interfaces[$interfaceID]->setOctetsOut($oidList->get('1.3.6.1.2.1.2.2.1.16.' . $interfaceID));
                        } catch (Throwable $e) {
                            $log->exception($e, [
                                'ip' => $this->device->getIp()
                            ]);
                        }
                    }

                    try {
                        $this->interfaces[$interfaceID]->setPpsIn($oidList->get('1.3.6.1.2.1.31.1.1.1.7.' . $interfaceID));
                    } catch (Throwable $e) {
                        try {
                            $this->interfaces[$interfaceID]->setPpsIn($oidList->get('1.3.6.1.2.1.2.2.1.11.' . $interfaceID));
                        } catch (Throwable $e) {
                            $log->exception($e, [
                                'ip' => $this->device->getIp()
                            ]);
                        }
                    }

                    try {
                        $this->interfaces[$interfaceID]->setPpsOut($oidList->get('1.3.6.1.2.1.31.1.1.1.11.' . $interfaceID));
                    } catch (Throwable $e) {
                        try {
                            $this->interfaces[$interfaceID]->setPpsOut($oidList->get('1.3.6.1.2.1.2.2.1.17.' . $interfaceID));
                        } catch (Throwable $e) {
                            $log->exception($e, [
                                'ip' => $this->device->getIp()
                            ]);
                        }
                    }
                } catch (Throwable $e) {
                    $log->exception($e, [
                        'ip' => $this->device->getIp()
                    ]);
                }
            }
        }
    }

    /**
     * @return SnmpResponse
     */
    private function getBaseData(): SnmpResponse
    {
        if ($this->baseData === null) {
            $this->baseData = $this->walk('1.3.6.1.2.1.2.2.1');
            try {
                $hc = $this->walk('1.3.6.1.2.1.31.1.1.1');
                $this->baseData->merge($hc);
            } catch (Throwable $e) {
                //
            }
        }
        return $this->baseData;
    }

    private function setArp()
    {
        $result = $this->walk("1.3.6.1.2.1.4.22.1.2");
        $arp = [];
        foreach ($result->getAll() as $oid => $value) {
            $key = ltrim($oid, ".");
            $boom = explode(".", $key);
            if (count($boom) < 5) {
                $log = new Log();
                $log->error("Could not determine the interface from ARP result $key");
                continue;
            }
            $interfaceID = $boom[count($boom) - 5];
            if (isset($this->interfaces[$interfaceID])) {
                if (!isset($arp[$interfaceID])) {
                    $arp[$interfaceID] = [];
                }
                $arp[$interfaceID][] = $value;
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
        foreach ($result->getAll() as $oid => $value) {
            if (strpos($oid, '1.3.6.1.2.1.17.4.3.1.2.') !== false) {
                $key = ltrim($oid, ".");
                $boom = explode(".", $key, 12);
                $mappings[$boom[11]] = $value;
            }
        }

        $bridgingTables = [];
        foreach ($result->getAll() as $oid => $value) {
            if (strpos($oid, '1.3.6.1.2.1.17.4.3.1.1.') !== false) {
                $key = ltrim($oid, ".");
                $boom = explode(".", $key, 12);
                if (isset($mappings[$boom[11]]) && isset($this->interfaces[$mappings[$boom[11]]])) {
                    if (!isset($bridgingTables[$mappings[$boom[11]]])) {
                        $bridgingTables[$mappings[$boom[11]]] = [];
                    }
                    $bridgingTables[$mappings[$boom[11]]][] = $value;
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
        foreach ($result->getAll() as $oid => $value) {
            $key = ltrim($oid, ".");
            $key = str_replace("1.3.6.1.2.1.4.20.1.", "", $key);
            $boom = explode(".", $key, 2);
            if (!isset($boom[1])) {
                continue;
            }

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
                    $ipv4Results[$boom[1]]['ip'] = $value;
                    break;
                case 2:
                    $ipv4Results[$boom[1]]['index'] = $value;
                    break;
                case 3:
                    $ipv4Results[$boom[1]]['subnet'] = $this->maskToCidr($value);
                    break;
                default:
                    continue 2;
            }
        }

        //IPv6
        $ipv6Results = [];
        $result = $this->walk("1.3.6.1.2.1.55.1.8");
        foreach ($result->getAll() as $oid => $value) {
            $key = ltrim($oid, ".");
            $key = str_replace("1.3.6.1.2.1.55.1.8.1.", "", $key);
            $boom = explode(".", $key, 3);

            if (!isset($boom[1])) {
                continue;
            }

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
                    $address = Address::factory($value);
                    $resultsToBeInserted[$boom[1]]['ip'] = $address->__toString();
                    break;
                case 2:
                    $resultsToBeInserted[$boom[1]]['subnet'] = preg_replace(
                        "/[^0-9]/",
                        "",
                        $value
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
