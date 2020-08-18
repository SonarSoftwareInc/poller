<?php

namespace Poller\DeviceMappers\Cambium;

use Poller\Models\SnmpResult;

class PTP700Backhaul extends PTPBackhaulBase
{
    public function map(SnmpResult $snmpResult)
    {
        return $this->getRemoteBackhaul(parent::map($snmpResult), "1.3.6.1.4.1.17713.9.5.4.0");
    }
}
