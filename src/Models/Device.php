<?php

namespace Poller\Models;

use Poller\Services\SnmpClient;

class Device
{
    const NETWORKSITE = 'network_sites';
    const ACCOUNT = 'accounts';

    private $inventoryItemID;
    private $ip;
    private $snmpOverrides = [];
    private $type;
    private $pollingPriority;
    private $inventoryModelID;
    private $monitoringTemplate;
    private ?SnmpClient $snmpClient = null;
    //TODO: need to set these from somewhere
    private ?string $sshUsername = null;
    private ?string $sshPassword = null;
    private ?int $port = null;

    /**
     * Device constructor.
     * @param int $inventoryItemID
     * @param $hostData
     * @param MonitoringTemplate $monitoringTemplate
     */
    public function __construct(
        int $inventoryItemID,
        $hostData,
        MonitoringTemplate $monitoringTemplate
    ) {
        $this->inventoryItemID = $inventoryItemID;
        $this->ip = $hostData->ip;
        $this->monitoringTemplate = $monitoringTemplate;
        $this->snmpOverrides = $hostData->snmp_overrides;
        $this->type = $hostData->type;
        $this->pollingPriority = (int)$hostData->polling_priority;
        $this->inventoryModelID = (int)$hostData->inventory_model_id;
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
     * @return array
     */
    public function getSnmpOverrides():array
    {
        return $this->snmpOverrides;
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
     * @return int
     */
    public function getInventoryModelID():int
    {
        return $this->inventoryModelID;
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
        //TODO: Deal with SNMP overrides
        if (!$this->snmpClient) {
            if ($this->monitoringTemplate->getSnmpVersion() !== 3) {
                $this->snmpClient = new SnmpClient([
                    'host' => $this->getIp(),
                    'version' => $this->monitoringTemplate->getSnmpVersion(),
                    'timeout_connect' => 2,
                    'timeout_read' => 2,
                    'community' => $this->monitoringTemplate->getSnmpCommunity(),
                ]);
            } else {
                $this->snmpClient = new SnmpClient([
                    'host' => $this->getIp(),
                    'version' => $this->monitoringTemplate->getSnmpVersion(),
                    'timeout_connect' => 2,
                    'timeout_read' => 2,
                    'user' => $this->monitoringTemplate->getSnmpCommunity(),
                    'sec_level' => $this->monitoringTemplate->getSnmp3SecLevel(),
                    'auth_mech' => $this->monitoringTemplate->getSnmp3AuthProtocol() === 'SHA' ? 'sha' : 'md5',
                    'auth_pwd' => $this->monitoringTemplate->getSnmp3AuthProtocol(),
                    'priv_mech' => $this->monitoringTemplate->getSnmp3PrivProtocol() === 'AES' ? 'aes' : 'des',
                    'priv_pwd' => $this->monitoringTemplate->getSnmp3PrivPassphrase(),
                    'engine_id' => $this->monitoringTemplate->getSnmp3ContextEngineID(),
                    'context_name' => $this->monitoringTemplate->getSnmp3ContextName(),
                ]);
            }
        }
        return $this->snmpClient;
    }

    /**
     * @return string|null
     */
    public function getSshPassword(): ?string
    {
        return $this->sshPassword;
    }

    /**
     * @return string|null
     */
    public function getSshUsername(): ?string
    {
        return $this->sshUsername;
    }

    /**
     * @return int|null
     */
    public function getPort(): ?int
    {
        return $this->port;
    }
}
