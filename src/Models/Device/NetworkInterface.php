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
    private ?string $type = null;
    private ?int $ppsIn = null;

    private ?int $ppsOut = null;
    private ?int $octetsIn = null;
    private ?int $octetsOut = null;
    private int $id;

    public function __construct(int $id)
    {
        $this->id = $id;
    }

    /**
     * @return int|null
     */
    public function getPpsIn(): ?int
    {
        return $this->ppsIn;
    }

    /**
     * @param int|null $ppsIn
     */
    public function setPpsIn(int $ppsIn): void
    {
        $this->ppsIn = $ppsIn;
    }

    /**
     * @return int|null
     */
    public function getPpsOut(): ?int
    {
        return $this->ppsOut;
    }

    /**
     * @param int|null $ppsOut
     */
    public function setPpsOut(int $ppsOut): void
    {
        $this->ppsOut = $ppsOut;
    }

    /**
     * @return int|null
     */
    public function getOctetsIn(): ?int
    {
        return $this->octetsIn;
    }

    /**
     * @param int|null $octetsIn
     */
    public function setOctetsIn(int $octetsIn): void
    {
        $this->octetsIn = $octetsIn;
    }

    /**
     * @return int|null
     */
    public function getOctetsOut(): ?int
    {
        return $this->octetsOut;
    }

    /**
     * @param int|null $octetsOut
     */
    public function setOctetsOut(int $octetsOut): void
    {
        $this->octetsOut = $octetsOut;
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
        $this->connectedLayer1Macs = array_map(
            function ($mac) {
                return Formatter::formatMac($mac);
            },
            array_filter($macs, function ($value) {
                return Formatter::validateMac($value);
            })
        );
    }

    public function getConnectedLayer1Macs():array
    {
        return $this->connectedLayer1Macs;
    }

    public function setConnectedLayer2Macs(array $macs)
    {
        $this->connectedLayer2Macs = array_map(
            function ($mac) {
                return Formatter::formatMac($mac);
            },
            array_filter($macs, function ($value) {
                return Formatter::validateMac($value);
            })
        );
    }

    public function getConnectedLayer2Macs():array
    {
        return $this->connectedLayer2Macs;
    }

    public function setConnectedLayer3Macs(array $macs)
    {
        $this->connectedLayer3Macs = array_map(
            function ($mac) {
                return Formatter::formatMac($mac);
            },
            array_filter($macs, function ($value) {
                return Formatter::validateMac($value);
            })
        );
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
            'description' => $this->name,
            'up' => $this->status,
            'connected_l1' => $this->connectedLayer1Macs,
            'connected_l2' => $this->connectedLayer2Macs,
            'connected_l3' => $this->connectedLayer3Macs,
            'ip_addresses '=> $this->ipAddresses,
            'mac_address' => $this->macAddress,
            'speed_mbps_in' => $this->speedIn,
            'speed_mbps_out' => $this->speedOut,
            'type' => $this->type,
            'pps_in' => $this->ppsIn,
            'pps_out' => $this->ppsOut,
            'octets_in' => $this->octetsIn,
            'octets_out' => $this->octetsOut,
            'metadata' => null, //TODO: check what this was in v1
        ];
    }
}
