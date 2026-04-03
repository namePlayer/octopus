<?php
declare(strict_types=1);

namespace App\Base\DTO;

readonly class NewAlertDTO
{

    public function __construct(
        public string $type,
        public string $message,
    )
    {
    }

}
