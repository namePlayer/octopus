<?php declare(strict_types=1);

namespace App\Authentication\Exception;

use RuntimeException;
use Throwable;

class PasswordResetTokenAlreadyUsedException extends RuntimeException
{
    public function __construct(string $token, ?Throwable $previous = null)
    {
        parent::__construct(sprintf('Token "%s" wurde bereits verwendet', $token), 0, $previous);
    }
}
