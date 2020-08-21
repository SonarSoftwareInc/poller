<?php

namespace Poller\Models\Device;

use Poller\Services\Formatter;
use const FILTER_VALIDATE_IP;

class NetworkInterface
{
    private ?string $name = null;
    private bool $status = false;
    private array $connectedLayer1Macs = [];
    private array $connectedLayer2Macs = [];
    private array $connectedLayer3Macs = [];
    private array $ipAddresses = [];
    private ?string $macAddress = null;
    private ?int $speedIn = null;
    private ?int $speedOut = null;
    private ?int $interfaceSpeed = null;
    private ?string $type = null;
    private int $id;

    public function __construct(int $id)
    {
        $this->id = $id;
    }

    public function setName(string $name)
    {
        $this->name = $name;
    }

    public function getName():?string
    {
        return $this->name;
    }

    public function setStatus(bool $status)
    {
        $this->status = $status;
    }

    public function getStatus():bool
    {
        return $this->status;
    }

    public function setConnectedLayer1Macs(array $macs)
    {
        $this->connectedLayer1Macs = array_map(function ($mac) {
            return Formatter::formatMac($mac);
        }, $macs);
    }

    public function getConnectedLayer1Macs():array
    {
        return $this->connectedLayer1Macs;
    }

    public function setConnectedLayer2Macs(array $macs)
    {
        $this->connectedLayer2Macs = array_map(function ($mac) {
            return Formatter::formatMac($mac);
        }, $macs);
    }

    public function getConnectedLayer2Macs():array
    {
        return $this->connectedLayer2Macs;
    }

    public function setConnectedLayer3Macs(array $macs)
    {
        $this->connectedLayer3Macs = array_map(function ($mac) {
            return Formatter::formatMac($mac);
        }, $macs);
    }

    public function getConnectedLayer3Macs():array
    {
        return $this->connectedLayer3Macs;
    }

    public function setIpAddresses(array $ips)
    {
        $this->ipAddresses = $ips;
    }

    public function getIpAddresses():array
    {
        return $this->ipAddresses;
    }

    public function setMacAddress(string $macAddress)
    {
        $this->macAddress = Formatter::formatMac($macAddress);
    }

    public function getMacAddress():?string
    {
        return $this->macAddress;
    }

    public function setSpeedIn(int $mbps)
    {
        $this->speedIn = $mbps;
    }

    public function getSpeedIn():?int
    {
        return $this->speedIn;
    }

    public function setSpeedOut(int $mbps)
    {
        $this->speedOut = $mbps;
    }

    public function getSpeedOut():?int
    {
        return $this->speedOut;
    }

    public function setType(string $type)
    {
        $this->type = $type;
    }

    public function getType():?string
    {
        return $this->type;
    }

    public function getID():int
    {
        return $this->id;
    }

    public function toArray():array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'status' => $this->status,
            'connected_layer1' => $this->connectedLayer1Macs,
            'connected_layer2' => $this->connectedLayer2Macs,
            'connected_layer3' => $this->connectedLayer3Macs,
            'ip_addresses '=> $this->ipAddresses,
            'mac_address' => $this->macAddress,
            'speed_in' => $this->speedIn,
            'speed_out' => $this->speedOut,
            'type' => $this->type,
        ];
    }

    private function validateMac(string $mac)
    {
        return filter_var(Formatter::addMacLeadingZeroes($mac), FILTER_VALIDATE_MAC) !== false;
    }

    /**
     * @return int|null
     */
    public function getInterfaceSpeed(): ?int
    {
        return $this->interfaceSpeed;
    }

    /**
     * @param int|null $interfaceSpeed
     */
    public function setInterfaceSpeed(?int $interfaceSpeed): void
    {
        $this->interfaceSpeed = $interfaceSpeed;
    }
}
