<?php

namespace Poller\Models;

use FreeDSx\Snmp\OidList;
use Poller\Models\Device\Metadata;

class SnmpResult
{
    private OidList $results;
    private ?Metadata $metadata;
    private array $interfaces = [];

    public function __construct(OidList $results)
    {
        $this->results = $results;
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
        $this->interfaces = $interfaces;
    }
}
