<?php
declare(strict_types=1);

namespace App\Base\PlatesExtension;

use App\Base\Interface\TranslationInterface;
use League\Plates\Engine;
use League\Plates\Extension\ExtensionInterface;

class TranslatorPlatesExtension implements ExtensionInterface
{

    public function __construct(
        private readonly TranslationInterface $translationService,
    )
    {
    }

    public function register(Engine $engine): void
    {
        $engine->registerFunction('translate', [$this, 'translate']);
    }

    public function translate(string $key, array $parameters = []): string
    {
        return $this->translationService->translate($key, $parameters);
    }

}