<?php declare(strict_types=1);

namespace App\Authentication\Exception;

use RuntimeException;
use Throwable;

class PasswordResetTokenInvalidException extends RuntimeException
{
    public function __construct(string $token, ?Throwable $previous = null)
    {
        parent::__construct(sprintf('Token "%s" ist ungültig oder zugehöriger Account nicht gefunden', $token), 0, $previous);
    }
}
