<?php
declare(strict_types=1);

namespace App\Authentication\DTO;

class CreateAccountDTO
{

    public function __construct(
        public readonly string $email,
        #[\SensitiveParameter]
        public readonly string $password,
        #[\SensitiveParameter]
        public readonly string $repeatPassword,
        public readonly bool $acceptedTerms
    )
    {
    }

}
