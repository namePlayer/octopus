<?php
declare(strict_types=1);

namespace App\Base\DTO;

readonly class CsrfTokenDTO
{

    public function __construct(
        public string $fieldName,
        public string $token
    )
    {
    }

}
