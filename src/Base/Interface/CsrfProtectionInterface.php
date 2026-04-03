<?php
declare(strict_types=1);

namespace App\Base\Interface;

use App\Base\DTO\CsrfTokenDTO;

interface CsrfProtectionInterface
{

    public function generateCsrfTokenForForm(string $form): CsrfTokenDTO;

    public function validateCsrfTokenForForm(string $form): void;

}
