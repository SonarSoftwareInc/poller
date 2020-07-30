<?php

namespace Poller\Services;

class SysObjectIDMatcher
{
    private $data;
    public function __construct()
    {
        $devices = json_decode(file_get_contents(__DIR__ . '/../../config/devices.json'));
        $this->data = $devices->devices;
    }

    public function getClass(string $oid)
    {
        $matchingCount = 0;
        $bestMatch = null;
        foreach ($this->data as $datum) {
            if (strpos($oid, $datum->response) === 0) {
                $length = strlen($datum->response);
                if ($length > $matchingCount) {
                    $matchingCount = $length;
                    $bestMatch = $datum->device;
                }
            }
        }

        return $bestMatch;
    }
}
