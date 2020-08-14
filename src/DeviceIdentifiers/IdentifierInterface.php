<?php

namespace Poller\DeviceIdentifiers;

use Poller\Models\Device;

interface IdentifierInterface
{
    public function __construct(Device $device);
    public function getMapper();
}
