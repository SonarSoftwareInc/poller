<?php

require(__DIR__ . '/../vendor/autoload.php');

use Poller\Web\Controllers\AuthedUserController;
use Poller\Web\Controllers\LoginController;

session_start();

$dispatcher = FastRoute\simpleDispatcher(function(FastRoute\RouteCollector $r) {
    $r->addRoute('GET', '/', [LoginController::class, 'show']);
    $r->addRoute('POST', '/', [LoginController::class, 'auth']);
    $r->addRoute('GET', '/home', [AuthedUserController::class, 'handle']);
    $r->addRoute('POST', '/settings', [AuthedUserController::class, 'handle']);
    $r->addRoute('GET', '/settings', [AuthedUserController::class, 'handle']);
    $r->addRoute('GET', '/logout', [AuthedUserController::class, 'handle']);
});

$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
}
$uri = rawurldecode($uri);

$routeInfo = $dispatcher->dispatch($httpMethod, $uri);
switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::FOUND:
        $handler = $routeInfo[1];
        $vars = $routeInfo[2];
        $controller = new $handler[0];
        $method = $handler[1];
        return $controller->$method($uri, $httpMethod, $vars);
    case FastRoute\Dispatcher::NOT_FOUND:
        break;
    default:
        //TODO: Do something;
        break;
}
