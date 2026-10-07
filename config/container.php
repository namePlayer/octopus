<?php declare(strict_types=1);

use App\Base\Factory\JwtFactory;
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
    ->addArgument(\App\Base\Service\CsrfProtectionService::class)
    ->addArgument(\App\Base\Service\AlertService::class)
    ->addArgument(\App\Base\Service\TranslationService::class);

$container->add(\App\Authentication\Controller\LoginController::class)
    ->addArgument(\League\Plates\Engine::class)
    ->addArgument(\App\Authentication\Service\AuthenticationService::class)
    ->addArgument(\App\Base\Service\CsrfProtectionService::class)
    ->addArgument(\App\Base\Service\AlertService::class)
    ->addArgument(\App\Authentication\Service\AuthenticationSessionService::class);

$container->add(\App\Authentication\Controller\PasswordResetController::class)
    ->addArgument(\League\Plates\Engine::class)
    ->addArgument(\App\Base\Service\AlertService::class)
    ->addArgument(\App\Base\Service\CsrfProtectionService::class)
    ->addArgument(\App\Authentication\Validator\ForgotPasswordValidator::class)
    ->addArgument(\App\Authentication\Service\PasswordResetService::class)
    ->addArgument(\App\Authentication\Validator\PasswordResetValidator::class);

$container->add(\App\Account\Controller\AccountController::class)
    ->addArgument(\League\Plates\Engine::class)
    ->addArgument(\App\Authentication\Service\AuthenticationSessionService::class);

#
# Services
#
$container->add(\App\Authentication\Service\PasswordService::class);

$container->add(\App\Authentication\Service\AccountService::class)
    ->addArgument(\App\Authentication\Service\PasswordService::class)
    ->addArgument(\App\Authentication\Table\AccountTable::class);

$container->add(\App\Authentication\Service\AuthenticationService::class)
    ->addArgument(\App\Authentication\Service\AccountService::class)
    ->addArgument(\App\Authentication\Service\PasswordService::class)
    ->addArgument(\Monolog\Logger::class);

$container->add(\App\Authentication\Service\PasswordResetService::class)
    ->addArgument(\App\Authentication\Service\AccountService::class)
    ->addArgument(\App\Authentication\Service\PasswordService::class)
    ->addArgument(\App\Authentication\Table\AccountForgotPasswordTokenTable::class)
    ->addArgument(\Monolog\Logger::class);

$container->add(\App\Base\Service\CacheService::class)
    ->addArgument(\Monolog\Logger::class);

$container->add(\App\Base\Service\CsrfProtectionService::class)
    ->addArgument(\Monolog\Logger::class);

$container->add(\App\Base\Service\AlertService::class)
    ->addArgument(\Monolog\Logger::class);

$container->add(\App\Authentication\Service\AuthenticationSessionService::class)
    ->addArgument(\Monolog\Logger::class)
    ->addArgument(\App\Authentication\Service\JWTService::class);

$container->add(\App\Authentication\Service\JWTService::class)
    ->addArgument(\App\Authentication\Table\AccountJwtRefreshTokenTable::class)
    ->addArgument(\App\Authentication\Service\AccountService::class)
    ->addArgument(\Jose\Component\Core\JWK::class)
    ->addArgument(\Jose\Component\Signature\JWSBuilder::class)
    ->addArgument(\Jose\Component\Signature\JWSVerifier::class)
    ->addArgument(\Jose\Component\Signature\Serializer\CompactSerializer::class);

$container->add(\App\Base\Service\TranslationService::class)
    ->addArgument($_ENV['APP_DEFAULT_LANGUAGE'])
    ->addArgument(\App\Software::TRANSLATIONS_DIR)
    ->addArgument(\Monolog\Logger::class);

#
# Repositories
#
$container->add(\App\Authentication\Table\AccountTable::class)
    ->addArgument(\Doctrine\DBAL\Connection::class)
    ->addArgument(\Monolog\Logger::class);

$container->add(\App\Authentication\Table\AccountForgotPasswordTokenTable::class)
    ->addArgument(\Doctrine\DBAL\Connection::class)
    ->addArgument(\Monolog\Logger::class);

$container->add(\App\Authentication\Table\AccountJwtRefreshTokenTable::class)
    ->addArgument(\Doctrine\DBAL\Connection::class)
    ->addArgument(\Monolog\Logger::class);

#
# Validators
#
$container->add(\App\Authentication\Validator\RegistrationValueValidator::class);

$container->add(\App\Authentication\Validator\ForgotPasswordValidator::class)
    ->addArgument(\App\Software::MAXIMUM_EMAIL_LENGTH);

$container->add(\App\Authentication\Validator\PasswordResetValidator::class)
    ->addArgument($_ENV['APP_MINIMUM_PASSWORD_LENGTH']);

#
# Dependencies
#
$container->add(\Doctrine\DBAL\Connection::class, new \App\Base\Factory\DatabaseFactory()->connect());

$jwtFactory = new JwtFactory();

$container->add(\Jose\Component\Core\JWK::class, $jwtFactory->getJwk());

$container->add(\Jose\Component\Signature\JWSBuilder::class, $jwtFactory->getJwsBuilder());

$container->add(\Jose\Component\Signature\JWSVerifier::class, $jwtFactory->getJwsVerifier());

$container->add(\Jose\Component\Signature\Serializer\CompactSerializer::class, $jwtFactory->getCompactSerializer());

$container->add(\Monolog\Logger::class)
    ->addArgument('app')
    ->addMethodCall('pushHandler',
        [new \App\Base\Factory\LoggerFactory()->createPushHandler()]
    );

$container->add(\App\Base\PlatesExtension\CsrfPlatesExtension::class)
    ->addArgument(\App\Base\Service\CsrfProtectionService::class);

$container->add(\App\Base\PlatesExtension\AlertsPlatesExtension::class)
    ->addArgument(\App\Base\Service\AlertService::class)
    ->addArgument(\App\Base\Service\TranslationService::class);

$container->add(\App\Base\PlatesExtension\TranslatorPlatesExtension::class)
    ->addArgument(\App\Base\Service\TranslationService::class);

$container->add(League\Plates\Engine::class)
    ->addArgument(__DIR__.'/../template')
    ->addMethodCall('loadExtension', [\App\Base\PlatesExtension\CsrfPlatesExtension::class])
    ->addMethodCall('loadExtension', [\App\Base\PlatesExtension\AlertsPlatesExtension::class])
    ->addMethodCall('loadExtension', [\App\Base\PlatesExtension\TranslatorPlatesExtension::class]);

$responseFactory = (new \Laminas\Diactoros\ResponseFactory());
$jsonStrategy = new \League\Route\Strategy\JsonStrategy($responseFactory)->setContainer($container);
$applicationStrategy = new \League\Route\Strategy\ApplicationStrategy()->setContainer($container);
$router = new \League\Route\Router()->setStrategy($applicationStrategy);
