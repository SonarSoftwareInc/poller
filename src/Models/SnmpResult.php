<?php

namespace Poller\Models;

use FreeDSx\Snmp\OidList;
use Poller\Models\Device\Metadata;

class SnmpResult implements CoroutineResultInterface
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
     * @param Metadata $metadata
     */
    public function setMetadata(Metadata $metadata): void
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

    public function toArray():array
    {
        return [
            'up' => true,
            'message' => null,
            'metadata' => [
                'contact' => $this->metadata ? $this->metadata->getContact() : null,
                'name' => $this->metadata ? $this->metadata->getName() : null,
                'location' => $this->metadata ? $this->metadata->getLocation() : null,
                'uptime' => $this->metadata ? $this->metadata->getUptime() : null,
                'description' => $this->metadata ? $this->metadata->getDescription() : null,
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
