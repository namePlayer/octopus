<?php
declare(strict_types=1);

namespace App\Exception;

use Exception;
use Throwable;

class EnvironmentException extends Exception
{

    public function __construct(string $environmentVar = "", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct('Could not load ' . $environmentVar, $code, $previous);
    }

}
