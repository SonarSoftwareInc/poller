<?php

namespace Poller\Models;

use Poller\Services\SnmpClient;

class Device
{
    const NETWORKSITE = 'network_sites';
    const ACCOUNT = 'accounts';

    private int $inventoryItemID;
    private string $ip;
    private string $type;
    private int $pollingPriority;
    private MonitoringTemplate $monitoringTemplate;
    private ?SnmpClient $snmpClient = null;

    /**
     * Device constructor.
     * @param int $inventoryItemID
     * @param $hostData
     * @param MonitoringTemplate $monitoringTemplate
     * @throws \Jawira\CaseConverter\CaseConverterException
     */
    public function __construct(
        int $inventoryItemID,
        $hostData,
        MonitoringTemplate $monitoringTemplate
    ) {
        $this->inventoryItemID = $inventoryItemID;
        $this->ip = $hostData->ip;
        $this->monitoringTemplate = $monitoringTemplate;
        $this->type = $hostData->type;
        $this->pollingPriority = (int)$hostData->polling_priority;
        $this->monitoringTemplate->applySnmpOverrides($hostData->snmp_overrides);
    }

    /**
     * @return int
     */
    public function getInventoryItemID():int
    {
        return $this->inventoryItemID;
    }

    /**
     * @return string
     */
    public function getIp():string
    {
        return $this->ip;
    }

    /**
     * @return string
     */
    public function getType():string
    {
        return $this->type;
    }

    /**
     * @return int
     */
    public function getPollingPriority():int
    {
        return $this->pollingPriority;
    }

    /**
     * @return MonitoringTemplate
     */
    public function getMonitoringTemplate():MonitoringTemplate
    {
        return $this->monitoringTemplate;
    }

    public function getSnmpClient():SnmpClient
    {
        if (!$this->snmpClient) {
            if ($this->monitoringTemplate->getSnmpVersion() !== 3) {
                $this->snmpClient = new SnmpClient([
                    'host' => $this->getIp(),
                    'version' => $this->monitoringTemplate->getSnmpVersion(),
                    'community' => $this->monitoringTemplate->getSnmpCommunity(),
                ]);
            } else {
                $this->snmpClient = new SnmpClient([
                    'host' => $this->getIp(),
                    'version' => $this->monitoringTemplate->getSnmpVersion(),
                    'user' => $this->monitoringTemplate->getSnmpCommunity(),
                    'sec_level' => $this->monitoringTemplate->getSnmp3SecLevel(),
                    'auth_mech' => $this->monitoringTemplate->getSnmp3AuthProtocol() === 'SHA' ? 'sha' : 'md5',
                    'auth_pwd' => $this->monitoringTemplate->getSnmp3AuthProtocol(),
                    'priv_mech' => $this->monitoringTemplate->getSnmp3PrivProtocol() === 'AES' ? 'aes' : 'des',
                    'priv_pwd' => $this->monitoringTemplate->getSnmp3PrivPassphrase(),
                    'engine_id' => $this->monitoringTemplate->getSnmp3ContextEngineId(),
                    'context_name' => $this->monitoringTemplate->getSnmp3ContextName(),
                ]);
            }
        }
        return $this->snmpClient;
    }
}
