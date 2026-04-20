<?php
declare(strict_types=1);

namespace App\Base\Service;

use App\Base\Exception\IllegalCharacterException;
use App\Base\Exception\IllegalPathException;
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
    }

    public function translate(string $key, array $params = [], ?string $locale = null): string
    {
        if(is_null($locale)) {
            $locale = $this->defaultLocale;
        }

        if(!isset($this->translations[$locale])) {
            $this->loadTranslation($locale);
        }

        $index = explode('.', $key);
        $translation = $this->translations[$locale];

        foreach ($index as $value) {
            if(!isset($translation[$value])) {
                $translation = $key;
                break;
            }
            $translation = $translation[$value];
        }

        if(!is_string($translation)) {
            $this->logger->warning('Translation key does not resolve to a valid string', [
                'key' => $key, 'locale' => $locale
            ]);
            $translation = $key;
        }

        return str_replace(
            array_keys($params),
            array_values($params),
            $translation
        );
    }

    private function loadTranslation(string $locale): void
    {
        if(preg_match('/^[a-z]{2}(_[A-Z]{2})?$/', $locale) === 0)
        {
            $this->logger->error('Invalid character found in locale string. Only allowed are a-zA-z_-', ['locale' => $locale]);
            throw new IllegalCharacterException();
        }

        $translationPath = $this->translationDir . '/' . $locale . '.php';
        if(!file_exists($translationPath))
        {
            $this->logger->error('Failed loading the active translation. The file was not found.', ['file' => $translationPath]);
            throw new TranslationLocaleWasNotFoundException($locale);
        }

        if(str_starts_with(realpath($translationPath), realpath($this->translationDir)) === false) {
            $this->logger->error('Possible path traversal detected. Stopping.', [
                'expected' => realpath($this->translationDir),
                'path' => realpath($translationPath),
                'locale' => $locale
            ]);
            throw new IllegalPathException();
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
