<?php

namespace Poller\Web\Controllers;

use Poller\Web\Loader;
use Poller\Web\Services\Auth;
use Poller\Web\Services\Database;
use Throwable;

class LoginController
{
    public function show()
    {
        $database = new Database();
        $url = $database->get(Database::SONAR_URL);
        $loader = new Loader();
        $template = $loader->load('login.html');
        $template->display([
            'url' => $url
        ]);
    }

    public function auth()
    {
        $loader = new Loader();
        $template = $loader->load('login.html');
        $auth = new Auth();

        $url = 'https://' . $_POST['sonarUrl'] . '.sonar.software';
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            echo $template->render([
               'error' => "$url is not a valid hostname."
            ]);
            die();
        }

        $username = $_POST['inputUsername'];
        $password = $_POST['inputPassword'];

        try {
            $userInfo = $auth->validateCredentials($url, $username, $password);
            $_SESSION['userInfo'] = $userInfo;
            header("Location: /home");
            exit();
        } catch (Throwable $e) {
            echo $template->render([
               'error' => $e->getMessage()
            ]);
            die();
        }
    }
}
