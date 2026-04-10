<?php
declare(strict_types=1);

namespace App\Base\Service;

use App\Base\Exception\IllegalCharacterException;
use App\Base\Exception\TranslationLocaleWasNotFoundException;
use App\Base\Interface\TranslationInterface;
use Monolog\Logger;

final class TranslationService implements TranslationInterface
{

    private array $translations = [];

    public function __construct(
        private readonly string $defaultLocale,
        private readonly string $translationDir,
        private readonly Logger $logger,
    )
    {
        $this->loadTranslation($this->defaultLocale);
    }

    public function translate(string $key, array $params = [], ?string $locale = null): string
    {
        if(is_null($locale)) {
            $locale = $this->defaultLocale;
        }
        $this->loadTranslation($locale);

        $index = explode('.', $key);
        $translation = $this->translations[$locale];

        foreach ($index as $value) {
            if(!array_key_exists($value, $translation)) {
                $translation = $key;
                break;
            }
            $translation = $translation[$value];
        }

        if(!is_string($translation)) {
            $translation = $key;
        }

        foreach ($params as $param => $value) {
            $translation = str_replace($param, $value, $translation);
        }

        return $translation;
    }

    private function loadTranslation(string $locale): void
    {
        if(preg_match('/^[a-zA-Z_\-]+$/', $locale) === 0)
        {
            $this->logger->error('Invalid character found in locale string. Only allowed are a-zA-z_-', ['locale' => $locale]);
            throw new IllegalCharacterException();
        }

        if(isset($this->translations[$locale])) {
            return;
        }

        $translationPath = $this->translationDir . '/' . $locale . '.php';
        if(!file_exists($translationPath))
        {
            $this->logger->error('Failed loading the active translation. The file was not found.', ['file' => $translationPath]);
            throw new TranslationLocaleWasNotFoundException($locale);
        }
        $translations = include $translationPath;
        if(!is_array($translations))
        {
            $this->logger->error('Failed to load the active translation. The files content does not equal an array.', ['file' => $translationPath]);
            throw new TranslationLocaleWasNotFoundException($translationPath);
        }
        $this->translations[$locale] = $translations;
        unset($translations);
    }

}
