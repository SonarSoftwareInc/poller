<?php

namespace Poller\Tasks;

use Amp\Parallel\Worker\Environment;
use Amp\Parallel\Worker\Task;
use Exception;
use SNMP;
use SNMPException;

class SnmpGet implements Task
{
    private $ipAddress;
    private $version;
    private $community;
    private $oids;

    public function __construct(string $ipAddress, string $version, string $community, array $oids)
    {
        $this->ipAddress = $ipAddress;
        $this->version = $version;
        $this->community = $community;
        $this->oids = $oids;
    }

    /**
     * @inheritDoc
     */
    public function run(Environment $environment)
    {
        $snmp = $this->buildSnmpObject();
        try {
            if (count($this->oids) > 0) {
                return $snmp->get(array_values($this->oids));
            }
            return [];
        } catch (SNMPException $e) {
            //TODO: need to deal with the code here and returning good/warning/down
            //print_r($e->getMessage());
            return [];
        } catch (Exception $e) {
            return [];
        }
    }

    private function buildSnmpObject()
    {
        $snmp = new SNMP(
            $this->version,
            $this->ipAddress,
            $this->community,
            2000000, //microseconds
            1
        );
        $snmp->valueretrieval = SNMP_VALUE_LIBRARY;
        $snmp->oid_output_format = SNMP_OID_OUTPUT_NUMERIC;
        $snmp->enum_print = true;
        $snmp->exceptions_enabled = SNMP::ERRNO_ANY;

        //TODO: Fix this part
        if ($this->version === SNMP::VERSION_3)
        {
            $snmp->setSecurity(
                isset($host['snmp_overrides']['snmp3_sec_level']) ? $host['snmp_overrides']['snmp3_sec_level'] : $templateDetails['snmp3_sec_level'],
                isset($host['snmp_overrides']['snmp3_auth_protocol']) ? $host['snmp_overrides']['snmp3_auth_protocol'] : $templateDetails['snmp3_auth_protocol'],
                isset($host['snmp_overrides']['snmp3_auth_passphrase']) ? $host['snmp_overrides']['snmp3_auth_passphrase'] : $templateDetails['snmp3_auth_passphrase'],
                isset($host['snmp_overrides']['snmp3_priv_protocol']) ? $host['snmp_overrides']['snmp3_priv_protocol'] : $templateDetails['snmp3_priv_protocol'],
                isset($host['snmp_overrides']['snmp3_priv_passphrase']) ? $host['snmp_overrides']['snmp3_priv_passphrase'] : $templateDetails['snmp3_priv_passphrase'],
                isset($host['snmp_overrides']['snmp3_context_name']) ? $host['snmp_overrides']['snmp3_context_name'] : $templateDetails['snmp3_context_name'],
                isset($host['snmp_overrides']['snmp3_context_engine_id']) ? $host['snmp_overrides']['snmp3_context_engine_id'] : $templateDetails['snmp3_context_engine_id']
            );
        }

        return $snmp;
    }
}
