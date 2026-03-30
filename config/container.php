<?php declare(strict_types=1);

use League\Container\Container;

$container = new Container();

#
# Controllers
#
$container->add(\App\Base\Controller\IndexController::class)
    ->addArgument(\League\Plates\Engine::class);

$container->add(\App\Base\Controller\JsonController::class);

$container->add(\App\Authentication\Controller\RegistrationController::class)
    ->addArgument(\League\Plates\Engine::class)
    ->addArgument(\App\Authentication\Validator\RegistrationValueValidator::class)
    ->addArgument(\App\Authentication\Service\AuthenticationService::class)
    ->addArgument(\App\Base\Service\CsrfProtectionService::class);

$container->add(\App\Authentication\Controller\LoginController::class)
    ->addArgument(\League\Plates\Engine::class)
    ->addArgument(\App\Authentication\Service\AuthenticationService::class);

#
# Services
#
$container->add(\App\Authentication\Service\PasswordService::class);

$container->add(\App\Authentication\Service\AccountService::class)
    ->addArgument(\App\Authentication\Service\PasswordService::class)
    ->addArgument(\App\Authentication\Table\AccountTable::class);

$container->add(\App\Authentication\Service\AuthenticationService::class)
    ->addArgument(\App\Authentication\Service\AccountService::class);

$container->add(\App\Base\Service\CacheService::class)
    ->addArgument(\Monolog\Logger::class);

$container->add(\App\Base\Service\CsrfProtectionService::class)
    ->addArgument(\Monolog\Logger::class);

#
# Repositories
#
$container->add(\App\Authentication\Table\AccountTable::class)
    ->addArgument(\Doctrine\DBAL\Connection::class)
    ->addArgument(\Monolog\Logger::class);

#
# Validators
#
$container->add(\App\Authentication\Validator\RegistrationValueValidator::class);

#
# Dependencies
#
$container->add(\Doctrine\DBAL\Connection::class, new \App\Base\Factory\DatabaseFactory()->connect());

$container->add(\Monolog\Logger::class)
    ->addArgument('app')
    ->addMethodCall('pushHandler',
        [new \App\Base\Factory\LoggerFactory()->createPushHandler()]
    );

$container->add(\App\Base\PlatesExtension\CsrfPlatesExtension::class)
    ->addArgument(\App\Base\Service\CsrfProtectionService::class);

$container->add(League\Plates\Engine::class)
    ->addArgument(__DIR__.'/../template')
    ->addMethodCall('loadExtension', [\App\Base\PlatesExtension\CsrfPlatesExtension::class]);

$responseFactory = (new \Laminas\Diactoros\ResponseFactory());
$jsonStrategy = new \League\Route\Strategy\JsonStrategy($responseFactory)->setContainer($container);
$applicationStrategy = new \League\Route\Strategy\ApplicationStrategy()->setContainer($container);
$router = new \League\Route\Router()->setStrategy($applicationStrategy);
