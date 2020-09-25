<?php

namespace Poller\Pipelines;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Poller\Web\Services\Database;
use RuntimeException;
use const JSON_PRETTY_PRINT;

class Fetcher
{
    public function fetch(bool $debugMode = false)
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
                    'Content-Type' => 'application/json',
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

        if ($debugMode === true) {
            $handle = fopen(__DIR__ . '/../sonar_data.json', 'w');
            fwrite($handle, json_encode($data, JSON_PRETTY_PRINT));
            fclose($handle);
        }

        return $data;
    }
}
