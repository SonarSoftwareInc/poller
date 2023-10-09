<?php

namespace Poller\Models;

use Poller\Models\Device\Metadata;
use Poller\Models\Device\NetworkInterface;

class SnmpResult implements CoroutineResultInterface
{
    private SnmpResponse $results;
    private ?Metadata $metadata = null;
    /**
     * @var NetworkInterface[]
     */
    private array $interfaces = [];
    private int $id;

    public function __construct(SnmpResponse $results, int $id)
    {
        $this->results = $results;
        $this->id = $id;
    }

    public function getID(): int
    {
        return $this->id;
    }

    public function getResults():SnmpResponse
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

    public function getInterfaceByName(string $name): ?NetworkInterface
    {
        foreach ($this->interfaces as $interface) {
            if ($interface->getName() == $name) {
                return $interface;
            }
        }

        return null;
    }

    /**
     * @param array $interfaces
     */
    public function setInterfaces(array $interfaces): void
    {
        $this->interfaces = $interfaces;
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
            'results' => [
                'counters' => $this->results->getCounters(),
                'others' => $this->results->getOthers(),
            ]
        ];
    }
}
