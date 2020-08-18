<?php

namespace Poller\DeviceMappers\Etherwan;

use Exception;
use Poller\DeviceMappers\BaseDeviceMapper;
use Poller\Log;
use Poller\Models\SnmpResult;
use Poller\Services\Formatter;

class EtherwanSwitch extends BaseDeviceMapper
{

    public function map(SnmpResult $snmpResult)
    {
        return $this->getBridgingTable(parent::map($snmpResult));
    }

    /**
     * Get the bridging table
     * @param SnmpResult $snmpResult
     * @return SnmpResult
     */
    private function getBridgingTable(SnmpResult $snmpResult):SnmpResult
    {
        $switchingDatabasePortNumbers = $this->walk("1.3.6.1.4.1.2736.1.1.11.1.1.4");
        $mapping = [];
        foreach ($switchingDatabasePortNumbers->getAll() as $oid => $value) {
            $boom = explode(".", $oid);
            $mapping[$boom[count($boom)-1]] = $value;
        }

        $macAddresses = $this->walk("1.3.6.1.4.1.2736.1.1.11.1.1.2");
        foreach ($macAddresses->getAll() as $oid => $value) {
            try {
                $macAddress = Formatter::formatMac($value);
                $boom = explode(".",$oid);
                if (isset($mapping[$boom[count($boom)-1]])) {
                    $existingMacs = $this->interfaces[$mapping[$boom[count($boom) - 1]]]->getConnectedLayer2Macs();
                    $existingMacs[] = $macAddress;
                    $this->interfaces[$mapping[$boom[count($boom) - 1]]]->setConnectedLayer2Macs($macAddresses);
                }
            } catch (Exception $e) {
                $log = new Log();
                $log->exception($e, [
                    'ip' => $this->device->getIp(),
                ]);
            }
        }

        $snmpResult->setInterfaces($this->interfaces);
        return $snmpResult;
    }
}
