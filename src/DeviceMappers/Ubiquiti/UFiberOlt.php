<?php

namespace Poller\DeviceMappers\Ubiquiti;

use GuzzleHttp\Client as GuzzleClient;
use Poller\DeviceMappers\BaseDeviceMapper;
use Poller\Models\Device;
use Poller\Models\Device\NetworkInterface;
use Poller\Models\SnmpResult;
use Poller\Services\Log;
use Poller\Web\Services\Database;

class UFiberOlt extends BaseDeviceMapper
{
    private Database $database;

    private GuzzleClient $guzzle;

    private Log $log;

    private ?array $credentials;

    public function __construct(Device $device)
    {
        parent::__construct($device);

        $this->database = new Database();
        $this->guzzle = new GuzzleClient();
        $this->log = new Log();

        $this->credentials = $this->database->getCredential(Database::UBIQUITI_UFIBER_OLT_HTTPS);
    }

    public function map(SnmpResult $snmpResult): SnmpResult
    {
        $snmpResult = parent::map($snmpResult);

        if (!$this->credentials || !$this->login()) {
            return $snmpResult;
        }

        $ponInterfaces = $this->getPonInterfaces();
        $this->populatePonInterfaceStatistics($ponInterfaces);
        $this->associateOnusToPonInterfaces($ponInterfaces);
        $this->interfaces = [...$this->interfaces, ...$ponInterfaces];

        $snmpResult->setInterfaces($this->interfaces);

        return $snmpResult;
    }

    private function login(): bool
    {
        try {
            $response = $this->guzzle->post($this->getOltUrl('user/login'), [
                'headers' => ['Accept' => 'application/json'],
                'verify' => false,
                'json' => [
                    'username' => $this->credentials['username'],
                    'password' => $this->credentials['password'],
                ]
            ]);
            if ($xAuthTokens = $response->getHeader('x-auth-token')) {
                $this->guzzle = new GuzzleClient([
                    'headers' => [
                        'Accept' => 'application/json',
                        'x-auth-token' => $xAuthTokens[0],
                    ],
                    'verify' => false,
                ]);

                return true;
            }
        } catch (\Exception $e) {
            $this->log->exception($e, [
                'ip' => $this->device->getIp(),
            ]);
        }

        return false;
    }

    /**
     * Fetch PON interfaces via HTTP GET request to /api/v1.0/interfaces
     * @return NetworkInterface[]
     */
    private function getPonInterfaces(): array
    {
        try {
            $response = $this->guzzle->get($this->getOltUrl('interfaces'));

            if (!($jsonObject = \json_decode($response->getBody()->getContents(), false))) {
                throw new \Exception("Failed to parse interfaces JSON response.");
            }
        } catch (\Exception $e) {
            $this->log->exception($e, [
                'ip' => $this->device->getIp(),
            ]);

            return [];
        }

        $pons = [];
        foreach ($jsonObject as $interface) {
            if (!\preg_match('/^pon([0-9]+)$/', $interface->identification->name, $m)) {
                continue;
            }
            $ponNumber = intval($m[1]);

            $pon = new NetworkInterface(100000 + $ponNumber);
            $pon->setName($interface->identification->name);
            $pon->setMacAddress($interface->identification->mac);
            $pon->setStatus(isset($interface->status) && $interface->status->enabled && $interface->status->plugged);

            $pons[$ponNumber] = $pon;
        }

        return $pons;
    }

    /**
     * Fetch PON interface statistics via HTTP GET request to /api/v1.0/statistics and update the NetworkInterface
     * objects.
     * @param NetworkInterface[] $pons
     */
    private function populatePonInterfaceStatistics(array $pons): void
    {
        if (!$pons) {
            return;
        }

        try {
            $response = $this->guzzle->get($this->getOltUrl('statistics'));

            if (!($jsonObject = \json_decode($response->getBody()->getContents(), false))) {
                throw new \Exception("Failed to parse statistics JSON response.");
            }

            if (\is_array($jsonObject) && isset($jsonObject[0]->interfaces)) {
                $jsonObject = $jsonObject[0];
            }
        } catch (\Exception $e) {
            $this->log->exception($e, [
                'ip' => $this->device->getIp(),
            ]);

            return;
        }

        foreach ($jsonObject->interfaces as $interface) {
            if (!\preg_match('/^pon([0-9]+)$/', $interface->name, $m)) {
                continue;
            }
            $ponNumber = intval($m[1]);

            if (isset($pons[$ponNumber])) {
                $pon = $pons[$ponNumber];

                $pon->setErrorsIn($interface->statistics->rxErrors);
                $pon->setErrorsOut($interface->statistics->txErrors);
                $pon->setOctetsIn($interface->statistics->rxBytes);
                $pon->setOctetsOut($interface->statistics->txBytes);
            }
        }
    }

    /**
     * Associate PON ONU MACs to their terminating PON interfaces (via HTTP GET request to /api/v1.0/gpon/onus)
     * @param NetworkInterface[] $pons
     */
    private function associateOnusToPonInterfaces(array $pons): void
    {
        if (!$pons) {
            return;
        }

        try {
            $response = $this->guzzle->get($this->getOltUrl('gpon/onus'));

            if (!($jsonObject = \json_decode($response->getBody()->getContents(), false))) {
                throw new \Exception("Failed to parse gpon/onus JSON response.");
            }
        } catch (\Exception $e) {
            $this->log->exception($e, [
                'ip' => $this->device->getIp(),
            ]);

            return;
        }

        $macs = [];
        foreach ($jsonObject as $onu) {
            if (isset($pons[$onu->oltPort])) {
                if (!isset($macs[$onu->oltPort])) {
                    $macs[$onu->oltPort] = [];
                }
                $macs[$onu->oltPort][] = $onu->mac;
            }
        }
        foreach ($macs as $oltPort => $connectedMacs) {
            $pons[$oltPort]->setConnectedLayer1Macs($connectedMacs);
        }
    }

    private function getOltUrl(string $endpoint): string
    {
        return 'https://' . $this->device->getIp() . ':' .
            ($this->credentials['port'] ?: 443) . '/api/v1.0/' . ltrim($endpoint, '/');
    }
}
