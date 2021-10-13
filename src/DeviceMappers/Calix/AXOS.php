<?php

namespace Poller\DeviceMappers\Calix;

use Poller\DeviceMappers\BaseDeviceMapper;
use Poller\Models\Device;
use Poller\Models\Device\NetworkInterface;
use Poller\Models\SnmpResult;
use phpseclib\Net\SSH2;
use Poller\Services\Log;
use Poller\Web\Services\Database;

class AXOS extends BaseDeviceMapper
{
    private Database $database;

    private Log $log;

    private ?array $credentials;

    private ?SSH2 $ssh = null;

    public function __construct(Device $device)
    {
        parent::__construct($device);

        $this->database = new Database();
        $this->log = new Log();

        $this->credentials = $this->database->getCredential(Database::CALIX_AXOS_SSH);
    }

    /**
     * @throws \Exception
     */
    public function map(SnmpResult $snmpResult): SnmpResult
    {
        $snmpResult = parent::map($snmpResult);

        if (!$this->sshLogin()) {
            throw new \Exception("Failed to map: could not login via SSH to device " .
                $this->device->getIp() . ".");
        }

        if (!($interfaceNames = $this->getInterfaceNames())) {
            throw new \Exception("Failed to map: could not get interface names from device " .
                $this->device->getIp() . ".");
        }
        $onts = $this->getOnts();
        $this->ssh->disconnect();

        // filter out ONT "interfaces"
        $this->interfaces = \array_filter(
            $this->interfaces,
            fn(NetworkInterface $interface): bool => \in_array($interface->getName(), $interfaceNames),
        );

        $this->associateOntsToPonInterfaces($onts);

        $snmpResult->setInterfaces($this->interfaces);

        return $snmpResult;
    }

    private function getOnts(): array
    {
        return $this->parseOntLinkages(
            \trim($this->ssh->exec('show ont-linkages'))
        );
    }

    private function getInterfaceNames(): array
    {
        return $this->parseInterfaceSummaryStatus(
            \trim($this->ssh->exec('show interface summary status'))
        );
    }

    private function associateOntsToPonInterfaces(array $onts): void
    {
        foreach ($this->interfaces as $interface) {
            if (isset($onts[$interface->getName()])) {
                $interface->setConnectedLayer1Macs($onts[$interface->getName()]);
            }
        }
    }

    private function sshLogin(): bool
    {
        if (! $this->credentials) {
            $this->log->error("Could not login to " . $this->device->getIp() . " because no user-provided SSH2 credentials.");
            return false;
        }

        if ($this->ssh && $this->ssh->isAuthenticated()) {
            return true;
        }

        $this->ssh = new SSH2($this->device->getIp(), $this->credentials['port'], 3);

        if (!$this->ssh->login($this->credentials['username'], $this->credentials['password'])) {
            $this->log->error("Failed to login to " . $this->device->getIp() . " using user-provided SSH2 credentials.");
            return false;
        }

        $this->ssh->exec('paginate false');
        $this->ssh->exec('terminal-width 0');
        $this->ssh->exec('terminal-length 0');

        return true;
    }

    /**
     * Parses the output of "show ont-linkages" on the Calix.
     * Returns associative array with keys being PON interface on the OLT and values being array of connected ONT macs.
     */
    private function parseOntLinkages(string $input): array
    {
        $onts = [];
        $currentOnt = null;

        foreach (\preg_split('/[\r\n]+/', $input) as $line) {
            $line = \trim($line);

            if (\substr($line, 0, 11) == 'ont-linkage') {
                $currentOnt = [
                    'mac' => null,
                    'olt-interface' => null,
                ];
            } else if ($currentOnt && \substr($line, 0, 7) == 'ont-mac') {
                $currentOnt['mac'] = \preg_split('/[\t\s]+/', $line)[1];
            } else if ($currentOnt && \substr($line, 0, 9) == 'interface') {
                $currentOnt['olt-interface'] = \preg_split('/[\t\s]+/', $line)[1];
            }

            if ($currentOnt && $currentOnt['mac'] && $currentOnt['olt-interface']) {
                if (!isset($onts[$currentOnt['olt-interface']])) {
                    $onts[$currentOnt['olt-interface']] = [];
                }
                $onts[$currentOnt['olt-interface']][] = $currentOnt['mac'];

                $currentOnt = null;
            }
        }

        return $onts;
    }

    /**
     * Parses output of "show interface summary status" on the Calix.
     * Returns array of the built-in interface names on the unit
     */
    private function parseInterfaceSummaryStatus(string $input): array
    {
        $interfaceNames = [];

        foreach (\preg_split('/[\r\n]+/', $input) as $line) {
            $line = \trim($line);

            if (preg_match('/^([0-9]+\/[0-9]+\/[A-Za-z0-9]+)/', $line, $m)) {
                $interfaceNames[] = $m[1];
            }
        }

        return $interfaceNames;
    }
}