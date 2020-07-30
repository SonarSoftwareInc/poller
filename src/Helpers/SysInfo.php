<?php

namespace Poller\Helpers;

class SysInfo
{
    private $numberOfCpus;

    /**
     * Get the number of CPUs in the system
     * @return int
     */
    public function numberOfCpus():int
    {
        if (!$this->numberOfCpus) {
            $this->numberOfCpus = (int)shell_exec("/usr/bin/nproc");
        }
        return $this->numberOfCpus;
    }

    public function optimalSnmpQueueSize():int
    {
        $maxSize = 220;
        $coreMultiplier = 30;
        return $this->numberOfCpus() * $coreMultiplier > $maxSize
            ? $maxSize
            : $this->numberOfCpus() * $coreMultiplier;
    }

    public function optimalIcmpQueueSize():int
    {
        $maxSize = 32;
        $coreMultiplier = 8;
        return $this->numberOfCpus() * $coreMultiplier > $maxSize
            ? $maxSize
            : $this->numberOfCpus() * $coreMultiplier;
    }
}
