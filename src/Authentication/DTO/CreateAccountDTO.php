<?php
declare(strict_types=1);

namespace App\Authentication\DTO;

class CreateAccountDTO
{

    public function __construct(
        public readonly string $email,
        public readonly string $password,
        public readonly string $repeatPassword,
    )
    {
    }

}
