<?php

namespace Poller\Web\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\RequestException;
use Poller\Web\Exceptions\AuthException;
use Throwable;

class Auth
{
    /**
     * @param string $url
     * @param string $username
     * @param string $password
     * @return object
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function validateCredentials(string $url, string $username, string $password):object
    {
        $client = new Client();
        try {
            $response = $client->post($url . '/api/whoami', [
                'json' => [
                    'username' => $username,
                    'password' => $password
                ]
            ]);
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                $json = json_decode($e->getResponse()->getBody()->getContents());
                throw new AuthException($json->message ?? Psr7\str($e->getResponse()));
            }
            throw new AuthException($e->getMessage());
        } catch (Throwable $e) {
            throw new AuthException($e->getMessage());
        }

        $json = json_decode($response->getBody()->getContents());

        if ($response->getStatusCode() !== 200) {
            throw new AuthException($json->message ?? 'Authentication failure.');
        }
        return $json;
    }
}
