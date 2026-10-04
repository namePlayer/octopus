<?php
declare(strict_types=1);

namespace App\Authentication\DTO;

class JwtTokenAuthenticationDTO
{

    public function __construct(
        public readonly string $token,
        public readonly int $expires
    )
    {
    }

}
