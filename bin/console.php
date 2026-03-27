<?php declare(strict_types=1);

use Symfony\Component\Console\Application;

require_once __DIR__.'/../vendor/autoload.php';

\App\Software::initEnvironment();

require_once __DIR__.'/../config/container.php';

/* @var \League\Container\Container $container */

$console = new Application();

$console->addCommand(new \App\Base\Command\CacheClearCommand());

$console->addCommand(new \App\Authentication\Command\AuthenticationRegisterCommand(
    $container->get(\App\Authentication\Validator\RegistrationValueValidator::class),
    $container->get(\App\Authentication\Service\AuthenticationService::class),
    $container->get(\App\Authentication\Service\PasswordService::class)
));

$console->run();
