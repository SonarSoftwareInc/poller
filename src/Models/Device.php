<?php

namespace Poller\Models;

use FreeDSx\Snmp\SnmpClient;
use Poller\Models\Device\Metadata;

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
            if ($this->monitoringTemplate->getSnmpVersion() === 3) {
                $this->snmpClient =  new SnmpClient([
                    'host' => $this->getIp(),
                    'version' => $this->monitoringTemplate->getSnmpVersion(),
                    'timeout_connect' => 2,
                    'timeout_read' => 2,
                ]);
            } else {
                $this->snmpClient = new SnmpClient([
                    'host' => $this->getIp(),
                    'version' => $this->monitoringTemplate->getSnmpVersion(),
                    'timeout_connect' => 2,
                    'timeout_read' => 2,
                    # The SNMP user to connect with
                    'user' => $this->monitoringTemplate->getSnmpCommunity(),
                    # Specify to use authentication
                    'use_auth' => $this->monitoringTemplate->getSnmp3SecLevel() !== 'NO_AUTH_NO_PRIV',
                    # Specify the authentication mechanism for the user
                    'auth_mech' => $this->monitoringTemplate->getSnmp3AuthProtocol() === 'SHA' ? 'sha' : 'md5',
                    # Specify the user's password
                    'auth_pwd' => $this->monitoringTemplate->getSnmp3AuthProtocol(),
                    # Specify to use privacy
                    'use_priv' => $this->monitoringTemplate->getSnmp3SecLevel() === 'AUTH_PRIV',
                    # Specify the privacy mechanism for the user
                    'priv_mech' => $this->monitoringTemplate->getSnmp3PrivProtocol() === 'AES' ? 'aes' : 'des',
                    # Specify the privacy password for the user (different from the authentication password)
                    'priv_pwd' => $this->monitoringTemplate->getSnmp3PrivPassphrase(),
                    # Specify the engine ID
                    'engine_id' => $this->monitoringTemplate->getSnmp3ContextEngineID(),
                    # Specify the context name
                    'context_name' => $this->monitoringTemplate->getSnmp3ContextName(),
                ]);
            }
        }
        return $this->snmpClient;
    }
}
