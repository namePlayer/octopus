<?php
declare(strict_types=1);

namespace App\Authentication\DTO;

readonly class ForgotPasswordDTO
{

    public function __construct(
        public string $email,
    )
    {
    }

}
