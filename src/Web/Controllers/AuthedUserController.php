<?php

namespace Poller\Web\Controllers;

use Poller\Exceptions\ValidationException;
use Poller\Web\Loader;
use Poller\Web\Services\Database;
use Symfony\Component\VarDumper\Cloner\Data;

class AuthedUserController
{
    public function handle(string $uri, string $httpMethod, array $vars)
    {
        if (!isset($_SESSION['userInfo'])) {
            header("Location: /");
            exit();
        }

        $database = new Database();

        $variables = [
            'tabs' => [
                'home' => 'false',
                'settings' => 'false',
                'devicecreds' => false,
                'logs' => 'false'
            ],
            'form_values' => [
                'settings' => [],
            ],
            'table_values' => [
                'credentials' => [],
            ],
            'validation_errors' => [
                'settings' => [],
                'devicecreds' => [],
            ],
            'logs' => [],
            'version' => get_version(),
        ];

        if ($httpMethod === 'POST') {
            switch ($uri) {
                case '/settings':
                    try {
                        $this->updateSettings();
                    } catch (ValidationException $e) {
                        $variables['validation_errors']['settings'][$e->getField()] = $e->getMessage();
                    }
                    $template = 'main.html';
                    $variables['tabs']['settings'] = 'true';
                    break;
                case '/credentials':
                    try {
                        $this->createCredentials();
                    } catch (ValidationException $e) {
                        $variables['validation_errors']['devicecreds'][$e->getField()] = $e->getMessage();
                    }
                    $template = 'main.html';
                    $variables['tabs']['devicecreds'] = 'true';
                    break;
                case '/delete_credential':
                    try {
                        $this->deleteCredential();
                    } catch (ValidationException $e) {
                        $variables['validation_errors']['devicecreds'][$e->getField()] = $e->getMessage();
                    }
                    $template = 'main.html';
                    $variables['tabs']['devicecreds'] = 'true';
                    break;
                case '/home':
                default:
                    $template = 'main.html';
                    $variables['tabs']['home'] = 'true';
                    break;
            }
        } else {
            switch ($uri) {
                case '/logout':
                    unset($_SESSION['userInfo']);
                    header('Location:/');
                    exit();
                default:
                    $template = 'main.html';
                    $variables['tabs']['home'] = 'true';
                    break;
            }

        }

        $variables['form_values'] = [
            'settings' => [
                'sonarUrl' => $database->get(Database::SONAR_URL),
                'apiKey' => $database->get(Database::POLLER_API_KEY),
                'logExceptions' => $database->get(Database::LOG_EXCEPTIONS),
            ]
        ];

        $variables['table_values']['credentials'] = $database->getAllCredentials();

        $handle = fopen(__DIR__ . '/../../../logs/poller.log', 'r');
        $lines = 250;
        $line = 0;
        if ($handle) {
            while (($buffer = fgets($handle, 50000)) !== false && $line < $lines) {
                $split = explode(' ', $buffer, 4);
                $variables['logs'][] = [
                    'date' => str_replace('[', '',$split[0]),
                    'time' => str_replace(']', '', $split[1]),
                    'level' => str_replace(':', '', $split[2]),
                    'message' => $split[3]
                ];
                $line++;
            }
            fclose($handle);
        }

        $variables['logs'] = array_reverse(array_slice($variables['logs'], -250, 250));

        $loader = new Loader();
        $template = $loader->load($template);
        echo $template->render($variables);
        die();
    }

    private function deleteCredential()
    {
        $database = new Database();
        $database->deleteCredential($_POST['type']);
    }

    private function createCredentials()
    {
        $credentialType = trim($_POST['credentialType']);
        if (!$credentialType) {
            $exception = new ValidationException('Credential type is required');
            $exception->setField('credentialType');
            throw $exception;
        }

        $username = trim($_POST['username']);
        if (!$username) {
            $exception = new ValidationException('Username is required');
            $exception->setField('username');
            throw $exception;
        }

        $password = trim($_POST['password']);
        if (!$password) {
            $exception = new ValidationException('Password is required');
            $exception->setField('password');
            throw $exception;
        }

        $repeatPassword = trim($_POST['repeatPassword']);
        if ($repeatPassword !== $password) {
            $exception = new ValidationException('The passwords do not match.');
            $exception->setField('password');
            throw $exception;
        }

        $port = trim($_POST['port']);
        if (!$port) {
            $exception = new ValidationException('Port is required');
            $exception->setField('port');
            throw $exception;
        }

        $database = new Database();
        if ($database->getCredential($credentialType) !== null) {
            $exception = new ValidationException('There is already an existing credential for this type.');
            $exception->setField('credentialType');
        }

        if (!is_numeric($port) || (int)$port < 1 || (int)$port > 65535) {
            $exception = new ValidationException('That is not a valid port.');
            $exception->setField('port');
        }

        $database->setCredential(
            $credentialType,
            $username,
            $password,
            $port
        );
    }

    private function updateSettings()
    {
        $sonarUrl = trim($_POST['sonarUrl']);
        $apiKey = trim($_POST['apiKey']);
        $logExceptions = (int)$_POST['logExceptions'] ?? 0;
        $fullUrl = 'https://' . $sonarUrl . '.sonar.software';
        if (filter_var($fullUrl, FILTER_VALIDATE_URL) === false) {
            $exception = new ValidationException('Invalid URL');
            $exception->setField('sonarUrl');
            throw $exception;
        }

        if (empty($sonarUrl)) {
            $exception = new ValidationException('URL is required.');
            $exception->setField('sonarUrl');
            throw $exception;
        }

        if (empty($apiKey)) {
            $exception = new ValidationException('API key is required.');
            $exception->setField('apiKey');
            throw $exception;
        }

        $database = new Database();
        $database->set(Database::SONAR_URL, $sonarUrl);
        $database->set(Database::POLLER_API_KEY, $apiKey);
        $database->set(Database::LOG_EXCEPTIONS, $logExceptions);
    }
}
