<?php

namespace Poller\Models;

use FreeDSx\Snmp\OidList;
use Poller\Models\Device\Metadata;

class SnmpResult
{
    private OidList $results;
    private ?Metadata $metadata;
    private array $interfaces = [];
    private string $ip;

    public function __construct(OidList $results, string $ip)
    {
        $this->results = $results;
        $this->ip = $ip;
    }

    public function getIp():string
    {
        return $this->ip;
    }

    public function getResults():OidList
    {
        return $this->results;
    }

    /**
     * @return Metadata|null
     */
    public function getMetadata(): ?Metadata
    {
        return $this->metadata;
    }

    /**
     * @param Metadata|null $metadata
     */
    public function setMetadata(?Metadata $metadata): void
    {
        $this->metadata = $metadata;
    }

    /**
     * @return array
     */
    public function getInterfaces(): array
    {
        return $this->interfaces;
    }

    /**
     * @param array $interfaces
     */
    public function setInterfaces(array $interfaces): void
    {
        $this->interfaces = array_values($interfaces);
    }

    public function toArray()
    {
        return [
            'metadata' => [
                'contact' => $this->metadata->getContact(),
                'name' => $this->metadata->getName(),
                'location' => $this->metadata->getLocation(),
                'uptime' => $this->metadata->getUptime(),
                'description' => $this->metadata->getDescription(),
            ],
            'interfaces' => array_map(function ($interface) {
                return $interface->toArray();
            }, $this->interfaces),
            'results' => array_map(function ($oid) {
                return [
                    'oid' => $oid->getOid(),
                    'value' => $oid->getValue()->__toString()
                ];
            }, $this->getResults()->toArray()),
        ];
    }
}
