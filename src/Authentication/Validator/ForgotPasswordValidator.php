<?php
declare(strict_types=1);

namespace App\Authentication\Validator;

use App\Authentication\DTO\ForgotPasswordDTO;
use App\Authentication\Exception\AccountEmailExceedsMaximumLengthException;
use App\Authentication\Exception\AccountEmailIsInvalidException;

readonly class ForgotPasswordValidator
{

    public function __construct(
        private int $emailCharacterLimit
    )
    {
    }

    public function validate(ForgotPasswordDTO $forgotPasswordDTO): void
    {
        if(filter_var($forgotPasswordDTO->email, FILTER_VALIDATE_EMAIL) === false) {
            throw new AccountEmailIsInvalidException();
        }

        if(mb_strlen($forgotPasswordDTO->email) >= $this->emailCharacterLimit) {
            throw new AccountEmailExceedsMaximumLengthException();
        }
    }

}
