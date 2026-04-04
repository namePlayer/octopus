<?php
declare(strict_types=1);

namespace App\Base\Interface;

interface TranslationInterface
{

    public function translate(string $key, array $params = [], ?string $locale = null): string;

}
