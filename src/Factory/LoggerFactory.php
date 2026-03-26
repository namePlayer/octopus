<?php declare(strict_types=1);

namespace App\Factory;

use App\Software;
use Monolog\Formatter\LineFormatter;
use Monolog\Formatter\NormalizerFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;

class LoggerFactory
{

    public function createPushHandler(string $logFilename = Software::LOG_FILENAME): StreamHandler
    {
        $fileLocation = Software::LOG_DIR.'/'.$logFilename;

        $streamHandler = new StreamHandler($fileLocation, Level::fromName($_ENV['SOFTWARE_LOGLEVEL']));
        $streamHandler->setFormatter($this->createFormatter());

        return $streamHandler;
    }

    private function createFormatter(): LineFormatter
    {

        $output = "[%level_name%] %datetime% | %message% %context% %extra%\n";

        return new LineFormatter($output);
    }

}
