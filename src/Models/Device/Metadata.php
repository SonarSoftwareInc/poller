<?php

namespace Poller\Models\Device;

class Metadata
{
    private ?String $description;
    private ?String $uptime;
    private ?String $contact;
    private ?String $name;
    private ?String $location;

    /**
     * @return String|null
     */
    public function getContact(): ?string
    {
        return $this->contact;
    }

    /**
     * @param String|null $contact
     */
    public function setContact(?string $contact): void
    {
        $this->contact = $contact;
    }

    /**
     * @return String|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param String|null $name
     */
    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return String|null
     */
    public function getLocation(): ?string
    {
        return $this->location;
    }

    /**
     * @param String|null $location
     */
    public function setLocation(?string $location): void
    {
        $this->location = $location;
    }

    /**
     * @return String|null
     */
    public function getUptime(): ?string
    {
        return $this->uptime;
    }

    /**
     * @param String|null $uptime
     */
    public function setUptime(?string $uptime): void
    {
        $this->uptime = $uptime;
    }

    /**
     * @return String|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param String|null $description
     */
    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }
}
