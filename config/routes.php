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

$router->get('/authentication/login', 'App\Authentication\Controller\LoginController::viewLoginForm')
    ->setHost($_ENV['SOFTWARE_HOST']);
$router->post('/authentication/login', 'App\Authentication\Controller\LoginController::login')
    ->setHost($_ENV['SOFTWARE_HOST']);

# Passwort-Reset Routes
$router->get('/authentication/forgot-password', 'App\Authentication\Controller\ForgotPasswordController::viewForgotPasswordForm')
    ->setHost($_ENV['SOFTWARE_HOST']);
$router->post('/authentication/forgot-password', 'App\Authentication\Controller\ForgotPasswordController::requestPasswordReset')
    ->setHost($_ENV['SOFTWARE_HOST']);

$router->get('/authentication/reset/{token}', 'App\Authentication\Controller\ResetPasswordController::viewResetPasswordForm')
    ->setHost($_ENV['SOFTWARE_HOST']);
$router->post('/authentication/reset/{token}', 'App\Authentication\Controller\ResetPasswordController::resetPassword')
    ->setHost($_ENV['SOFTWARE_HOST']);

$response = $router->dispatch($request);
new \Laminas\HttpHandlerRunner\Emitter\SapiEmitter()->emit($response);
