<?php
declare(strict_types=1);

namespace App\Authentication\DTO;

class ResetForgotPasswordDTO
{

    public function __construct(
        public readonly string $token,
        public readonly string $password,
        public readonly string $repeatPassword,
    )
    {
    }

}
