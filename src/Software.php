<?php
declare(strict_types=1);

namespace App;

use App\Exception\EnvironmentException;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Dotenv\Exception\PathException;

class Software
{

    public const VERSION = '0.0.1';
    public const BUILD = '000001';
    public const TYPE = 'dev';

    public const CACHE_DIR = __DIR__ . '/../data/cache';
    public const LOG_DIR = __DIR__ . '/../data/log';

    public const LOG_FILENAME = 'app.log';
    public const CONSOLE_LOG_FILENAME = 'console.log';

    /**
     * @throws EnvironmentException
     */
    public static function initEnvironment(string $location = __DIR__ . '/../.env'): void
    {
        $envLoad = new Dotenv();

        try {
            $envLoad->load($location);
        } catch (PathException $exception) {
            die('Could not open Environment File');
        }

        if (!isset($_ENV['SOFTWARE_TIMEZONE'])) {
            throw new EnvironmentException('SOFTWARE_TIMEZONE');
        }

        if (!isset($_ENV['SOFTWARE_TITLE'])) {
            throw new EnvironmentException('SOFTWARE_TITLE');
        }

        if (!isset($_ENV['DB_HOST'], $_ENV['DB_NAME'], $_ENV['DB_USER'], $_ENV['DB_PASSWORD'])) {
            throw new EnvironmentException('DB_HOST, DB_NAME, DB_USER or DB_PASSWORD');
        }

        if (!isset($_ENV['SOFTWARE_PRODUCTION'])) {
            $_ENV['SOFTWARE_PRODUCTION'] = false;
        }
    }

    public static function getLogger(): Logger
    {
        $logger = new Logger('log');
        $logger->pushHandler(new StreamHandler(self::LOG_DIR . '/' . self::LOG_FILENAME, Level::Warning));

        return $logger;
    }

}
