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
$router->get('/', 'App\Base\Controller\IndexController::load');

$router->get('/json', 'App\Base\Controller\IndexController::load');

$router->get('/authentication/registration', 'App\Authentication\Controller\RegistrationController::viewRegisterForm')
    ->setHost($_ENV['SOFTWARE_HOST']);
$router->post('/authentication/registration', 'App\Authentication\Controller\RegistrationController::register')
    ->setHost($_ENV['SOFTWARE_HOST']);


$response = $router->dispatch($request);
new \Laminas\HttpHandlerRunner\Emitter\SapiEmitter()->emit($response);
