<?php

namespace Poller\Pipelines;

use Poller\Models\Device;
use Poller\Models\MonitoringTemplate;

class DeviceFactory
{
    private array $devices;

    /**
     * DeviceFactory constructor.
     * @param object $data
     * @throws \Jawira\CaseConverter\CaseConverterException
     */
    public function __construct(object $data)
    {
        $monitoringTemplates = [];
        foreach ($data->monitoring_templates as $id => $monitoringTemplateJson) {
            $monitoringTemplates[$id] = new MonitoringTemplate($monitoringTemplateJson);
        }

        $devices = [];
        foreach ($data->hosts as $id => $host) {
            $devices[$host->ip] = new Device($id, $host, clone $monitoringTemplates[$host->monitoring_template_id]);
        }

        $this->devices = $devices;
    }

    /**
     * @return array
     */
    public function getAllDevices():array
    {
        return $this->devices;
    }

    /**
     * @return array
     */
    public function getIcmpDevices():array
    {
        return array_filter($this->devices, function ($device) {
            return $device->getMonitoringTemplate()->getIcmp() === true;
        });
    }

    public function getSnmpDevices():array
    {
        return array_filter($this->devices, function ($device) {
            $template = $device->getMonitoringTemplate();
            return !empty($template->getSnmpCommunity());
        });
    }
}
