<?php
declare(strict_types=1);

namespace App\Base\PlatesExtension;

use App\Base\Interface\CsrfProtectionInterface;
use League\Plates\Engine;
use League\Plates\Extension\ExtensionInterface;

class CsrfPlatesExtension implements ExtensionInterface
{

    public function __construct(
        private readonly CsrfProtectionInterface $csrfProtectionService,
    )
    {
    }

    public function register(Engine $engine): void
    {
        $engine->registerFunction('generateCsrfField', [$this, 'generateCsrfField']);
    }

    public function generateCsrfField(string $formName): string
    {
        $token = $this->csrfProtectionService->generateCsrfTokenForForm($formName);
        return '<input type="hidden" name="'.$token->fieldName.'" value="' . $token->token . '">';
    }

}
