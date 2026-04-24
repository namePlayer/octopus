<?php
declare(strict_types=1);

namespace App\Base\Service;

use App\Base\Exception\IllegalCharacterException;
use App\Base\Exception\PossiblePathTraversalException;
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
            $this->logger->error('Invalid character found in locale string. Only allowed characters are a-z, A-z, _',
                ['locale' => preg_replace('/[^\w\-]/', '', $locale)]);
            throw new IllegalCharacterException();
        }

        $translationDir = realpath($this->translationDir);
        if($translationDir === false) {
            $this->logger->error('An invalid path for the translations directory was configured', ['path' => $this->translationDir]);
            throw new TranslationLocaleWasNotFoundException();
        }
        $translationPath = $translationDir . '/' . $locale . '.php';
        $translationPath = realpath($translationPath);

        if($translationPath === false)
        {
            $this->logger->error('Failed loading the active translation. The file was not found.', ['file' => $translationPath]);
            throw new TranslationLocaleWasNotFoundException($locale);
        }

        if(str_starts_with($translationPath, $translationDir) === false) {
            $this->logger->error('Possible path traversal detected. Stopping.', [
                'expected' => $translationDir,
                'path' => $translationPath,
                'locale' => $locale
            ]);
            throw new PossiblePathTraversalException();
        }

        $translations = include $translationPath;
        if(!is_array($translations))
        {
            $this->logger->error('Failed to load the active translation. The files content does not equal an array.', ['file' => $translationPath]);
            throw new TranslationLocaleWasNotFoundException($translationPath);
        }
        $this->translations[$locale] = $translations;
    }

}
