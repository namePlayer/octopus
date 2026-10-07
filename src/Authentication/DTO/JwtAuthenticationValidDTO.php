<?php
declare(strict_types=1);

namespace App\Authentication\DTO;

readonly class JwtAuthenticationValidDTO
{

    public function __construct(
        private bool $valid,
        private bool $requiresRefresh
    )
    {
    }

}
