<?php

namespace Poller\Models\Device;

class Metadata
{
    private ?String $description = null;
    private ?String $uptime = null;
    private ?String $contact = null;
    private ?String $name = null;
    private ?String $location = null;

    /**
     * @return String|null
     */
    public function getContact(): ?string
    {
        return $this->contact;
    }

    /**
     * @param String $contact
     */
    public function setContact(string $contact): void
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
     * @param String $name
     */
    public function setName(string $name): void
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
     * @param String $location
     */
    public function setLocation(string $location): void
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
     * @param String $uptime
     */
    public function setUptime(string $uptime): void
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
     * @param String $description
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }
}
