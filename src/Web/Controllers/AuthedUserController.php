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
                'logs' => 'false'
            ],
            'form_values' => [
                'settings' => [],
            ],
            'validation_errors' => [
                'settings' => [],
            ]
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

        $loader = new Loader();
        $template = $loader->load($template);
        echo $template->render($variables);
        die();
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
