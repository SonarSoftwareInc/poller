<?php

namespace Poller\DeviceMappers\MikroTik;

use Exception;
use PEAR2\Net\RouterOS\Client;
use PEAR2\Net\RouterOS\Request;
use PEAR2\Net\RouterOS\Response;
use PEAR2\Net\RouterOS\SocketException;
use PEAR2\Net\Transmitter\NetworkStream;
use Poller\DeviceMappers\BaseDeviceMapper;
use Poller\Log;
use Poller\Models\SnmpResult;
use Poller\Services\Formatter;

class MikroTik extends BaseDeviceMapper
{
    private ?Client $client = null;

    public function map(SnmpResult $snmpResult)
    {
        $snmpResult = $this->getWirelessClients(parent::map($snmpResult));
        return $this->getBridgingTable($snmpResult);
    }

    /**
     * @param SnmpResult $snmpResult
     * @return array|mixed
     */
    private function getWirelessClients(SnmpResult $snmpResult):SnmpResult
    {
        try {
            $result = $this->walk("1.3.6.1.4.1.14988.1.1.1.2.1.1");
            foreach ($result as $datum) {
                $boom = explode(".", $datum->getOid());
                $interfaceIndex = $boom[count($boom)-1];
                try {
                    $mac = Formatter::formatMac($datum->getValue()->__toString());

                    if(isset($this->interfaces[$interfaceIndex])) {
                        $existingMacs = $this->interfaces[$interfaceIndex]->setConnectedLayer1Macs();
                        $existingMacs[] = $mac;
                        $this->interfaces[$interfaceIndex]->setConnectedLayer1Macs($existingMacs);
                    }
                } catch (Exception $e) {
                    continue;
                }
            }
        } catch (Exception $e) {
            $log = new Log();
            $log->exception($e, [
                'ip' => $this->device->getIp(),
            ]);
        }

        $snmpResult->setInterfaces($this->interfaces);
        return $snmpResult;
    }

    private function getBridgingTable(SnmpResult $snmpResult):SnmpResult
    {
        $client = $this->getClient();
        if (!$client) {
            return $snmpResult;
        }

        $interfaces = $snmpResult->getInterfaces();
        $interfacesByName = [];
        foreach ($interfaces as $interface) {
            $interfacesByName[$interface->getName()] = $interface;
        }

        $request = new Request('/interface/bridge/host/print');
        $request->setArgument('.proplist', '.id,local,on-interface');
        $responses = $client->sendSync($request);
        foreach ($responses as $response) {
            if ($response->getType() !== Response::TYPE_DATA && $response->getType() !== Response::TYPE_FINAL) {
                return $snmpResult;
            }

            $id = $response->getProperty('.id');
            if ($id !== null) {
                $mac = $response->getProperty('local');
                $interface = $response->getProperty('on-interface');
                if (isset($interfacesByName[$interface])) {
                    $existingMacs = $interfacesByName[$interface]->getConnectedLayer2Macs();
                    $existingMacs[] = $mac;
                    $interfacesByName[$interface]->setConnectedLayer2Macs($existingMacs);
                }
            }
        }

        $snmpResult->setInterfaces($interfaces);
        return $snmpResult;
    }

    /**
     * Get the API client
     * @return Client|null
     */
    private function getClient()
    {
        if ($this->client !== null) {
            return $this->client;
        }

        if ($this->device->getSshUsername() === null || $this->device->getSshPassword() === null) {
            return null;
        }

        $context = stream_context_create(
            [
                'ssl' => [
                    'verify_peer' => false,
                    'allowed_self_signed' => true,
                    'verify_peer_name' => false,
                ]
            ]
        );

        try {
            $client = new Client(
                $this->device->getIp(),
                $this->device->getSshUsername(),
                $this->device->getSshPassword(),
                $this->device->getPort(),
                false,
                10,
                NetworkStream::CRYPTO_TLS,
                $context
            );
        } catch (Exception $e) {
            $log = new Log();
            $log->exception($e);
            return null;
        }

        $this->client = $client;
        return $this->client;
    }
}
