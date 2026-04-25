<?php
declare(strict_types=1);

namespace App\Authentication\Validator;

use App\Authentication\DTO\ResetForgotPasswordDTO;
use App\Authentication\Exception\PasswordRepeatDoesNotMatchException;
use App\Authentication\Exception\PasswordToShortException;

readonly class PasswordResetValidator
{

    public function __construct(
        private int $minimumPasswordLength,
    )
    {
    }

    public function validate(ResetForgotPasswordDTO $resetForgotPasswordDTO): void
    {
        if(mb_strlen($resetForgotPasswordDTO->password) < $this->minimumPasswordLength) {
            throw new PasswordToShortException();
        }

        if($resetForgotPasswordDTO->password !== $resetForgotPasswordDTO->repeatPassword)
        {
            throw new PasswordRepeatDoesNotMatchException();
        }
    }

}
