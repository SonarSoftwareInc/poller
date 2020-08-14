<?php

namespace Poller\Pipelines;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use RuntimeException;

class Fetcher
{
    public function fetch()
    {
        //todo: need to store/load the proper URL and API key
        $url = null;
        $key = null;

        $client = new Client();
        try {
            $result = $client->post("$url/api/poller", [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'timeout' => 30,
                ],
                'json' => [
                    'api_key' => $key,
                    'version' => getenv('SONAR_POLLER_VERSION', true) ?? 'Unknown',
                ]
            ]);
            //TODO: sort out what I want to do here
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

        //todo: finish
        print_r($result->getBody()->getContents());
    }
}
