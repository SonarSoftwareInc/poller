<?php

namespace Poller\Web;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class Loader
{
    private $twig;

    public function __construct()
    {
        $loader = new FilesystemLoader(__DIR__ . '/Templates');
        $this->twig = new Environment($loader, []);
    }

    /**
     * @param string $filename
     * @return \Twig\TemplateWrapper
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function load(string $filename)
    {
        return $this->twig->load($filename);
    }
}
