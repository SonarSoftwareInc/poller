<?php

namespace Poller\DeviceMappers\Ubiquiti;

use Poller\DeviceMappers\BaseDeviceMapper;
use Poller\Exceptions\SnmpException;
use Poller\Models\Device;
use Poller\Services\Log;
use Poller\Models\SnmpResult;
use Poller\Services\Formatter;

class AccessPoint extends BaseDeviceMapper
{
    const OID_UBNT_STA_MAC = '1.3.6.1.4.1.41112.1.4.7.1.1';
    const OID_AFLTU_STA_REMOTE_MAC = '1.3.6.1.4.1.41112.1.10.1.4.1.11';

    private Log $log;

    private string $staMacOid;

    public function __construct(Device $device, string $staMacOid = self::OID_UBNT_STA_MAC)
    {
        parent::__construct($device);

        $this->log = new Log();
        $this->staMacOid = $staMacOid;
    }

    public function map(SnmpResult $snmpResult): SnmpResult
    {
        $snmpResult = parent::map($snmpResult);

        if ($ath0 = $snmpResult->getInterfaceByName('ath0')) {
            $ath0->setConnectedLayer1Macs(
                \array_merge($ath0->getConnectedLayer1Macs(), $this->getAssociatedRadioMacs())
            );
        }

        return $snmpResult;
    }

    private function getAssociatedRadioMacs(): array
    {
        try {
            $staRemoteMacs = \array_values(
                $this->walk($this->staMacOid)->getAll()
            );

            $staRemoteMacs = \array_map(function (string $mac): ?string {
                try {
                    return Formatter::formatMac($mac);
                } catch (\InvalidArgumentException $e) {
                    // some macs have been returned in a mangled format during testing.
                    // return null here and filter them out so that at least the other
                    // macs can be discovered.
                    return null;
                }
            }, $staRemoteMacs);

            return \array_filter($staRemoteMacs, fn(?string $mac): bool => !empty($mac));
        } catch (SnmpException $e) {
            $this->log->exception($e, [
                'ip' => $this->device->getIp(),
                'oid' => $this->staMacOid,
            ]);

            return [];
        }
    }
}
