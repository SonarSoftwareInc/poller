<?php

namespace Poller\Web\Controllers;

use Poller\Web\Loader;

class LoginController
{
    public function show()
    {
        $loader = new Loader();
        $template = $loader->load('login.html');
        echo $template->render();
    }
}
