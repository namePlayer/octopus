<?php
declare(strict_types=1);

namespace App;

use App\Base\Exception\EnvironmentException;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Dotenv\Exception\PathException;

class Software
{

    public const string VERSION = '0.0.1';
    public const string BUILD = '000001';
    public const string TYPE = 'dev';

    public const string BASE_DIR = __DIR__.'/..';
    public const string CACHE_DIR = Software::BASE_DIR . '/data/cache';
    public const string LOG_DIR = Software::BASE_DIR . '/data/log';
    public const string TRANSLATIONS_DIR = Software::BASE_DIR . '/translations';

    public const string LOG_FILENAME = 'app.log';
    public const string CONSOLE_LOG_FILENAME = 'console.log';

    public const string DB_DATETIME_FORMAT = 'Y-m-d H:i:s';

    public const int MAXIMUM_EMAIL_LENGTH = 255;
    public const string ALERT_DEFAULT_TEMPLATE = 'element/alert';
    public const string ALERT_TRANSLATION_INDICATOR = 'translate:';

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
