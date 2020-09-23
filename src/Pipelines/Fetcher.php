<?php

namespace Poller\Pipelines;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Poller\Web\Services\Database;
use RuntimeException;

class Fetcher
{
    public function fetch()
    {
        $client = new Client();
        $database = new Database();
        $sonarUrl = $database->get(Database::SONAR_URL);
        $fullUrl = 'https://' . $sonarUrl . '.sonar.software';
        try {
            $response = $client->post("$fullUrl/api/poller", [
                'headers' => [
                    'User-Agent' => "SonarPoller/" . get_version(),
                    'Accept' => 'application/json',
                    'Accept-Encoding' => 'gzip',
                ],
                'timeout' => 30,
                'json' => [
                    'api_key' => $database->get(Database::POLLER_API_KEY),
                    'version' => get_version(),
                ]
            ]);
            $data = json_decode($response->getBody()->getContents());
        } catch (ClientException $e) {
            $response = $e->getResponse();
            try {
                $message = json_decode($response->getBody()->getContents());
                throw new RuntimeException($message->error->message);
            } catch (Exception $e) {
                throw new RuntimeException($e->getMessage());
            }
        } catch (Exception $e) {
            throw new RuntimeException($e->getMessage());
        }

        return $data;
    }
}
