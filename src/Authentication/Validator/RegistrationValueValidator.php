<?php
declare(strict_types=1);

namespace App\Authentication\Validator;

use App\Authentication\DTO\CreateAccountDTO;
use App\Authentication\Exception\AccountEmailExceedsMaximumLengthException;
use App\Authentication\Exception\AccountEmailIsInvalidException;
use App\Authentication\Exception\PasswordRepeatDoesNotMatchException;
use App\Authentication\Exception\PasswordToShortException;
use App\Software;

class RegistrationValueValidator
{

    public function validate(CreateAccountDTO $registerAccountDTO): void
    {

        if(mb_strlen($registerAccountDTO->email) > Software::MAXIMUM_EMAIL_LENGTH)
        {
            throw new AccountEmailExceedsMaximumLengthException();
        }

        if(filter_var($registerAccountDTO->email, FILTER_VALIDATE_EMAIL) === false) {
            throw new AccountEmailIsInvalidException();
        }

        if(mb_strlen($registerAccountDTO->password) < $_ENV['APP_MINIMUM_PASSWORD_LENGTH']) {
            throw new PasswordToShortException();
        }

        if($registerAccountDTO->password !== $registerAccountDTO->repeatPassword)
        {
            throw new PasswordRepeatDoesNotMatchException();
        }

    }

}
