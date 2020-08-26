<?php

namespace Poller\Models;

use Poller\Models\Device\Metadata;

class SnmpResult implements CoroutineResultInterface
{
    private SnmpResponse $results;
    private ?Metadata $metadata = null;
    private array $interfaces = [];
    private string $ip;
    private int $timeTaken = 0;

    public function __construct(SnmpResponse $results, string $ip)
    {
        $this->results = $results;
        $this->ip = $ip;
    }

    public function getIp():string
    {
        return $this->ip;
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

    /**
     * @param array $interfaces
     */
    public function setInterfaces(array $interfaces): void
    {
        $this->interfaces = $interfaces;
    }

    public function setTimeTaken(int $timeTaken)
    {
        $this->timeTaken = $timeTaken;
    }

    public function getTimeTaken():int
    {
        return $this->timeTaken;
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
            'results' => $this->results->getAll(),
        ];
    }
}
