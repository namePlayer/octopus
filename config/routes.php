<?php declare(strict_types=1);

use League\Route\Router;

$request = \Laminas\Diactoros\ServerRequestFactory::fromGlobals(
    $_SERVER,
    $_GET,
    $_POST,
    $_COOKIE,
    $_FILES
);

/* @var Router $router */
$router->get('/', 'App\Controller\IndexController::load');

$router->get('/json', 'App\Controller\IndexController::load');


$response = $router->dispatch($request);
(new \Laminas\HttpHandlerRunner\Emitter\SapiEmitter())->emit($response);
