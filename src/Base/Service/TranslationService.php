<?php
declare(strict_types=1);

namespace App\Base\Service;

use App\Base\Exception\IllegalCharacterException;
use App\Base\Exception\TranslationLocaleWasNotFoundException;
use App\Base\Interface\TranslationInterface;
use App\Software;
use Monolog\Logger;

class TranslationService implements TranslationInterface
{

    private array $translations = [];
    private string $locale = '';

    public function __construct(
        private readonly Logger $logger,
    )
    {
        $this->locale = $_ENV['APP_DEFAULT_LANGUAGE'];
    }

    public function translate(string $key, array $params = [], ?string $locale = null): string
    {
        if(!empty($locale))
        {
            $this->setLocale($locale);
        }

        if(empty($this->translations)) {
            $this->loadTranslations();
        }

        $index = explode('.', $key);
        $translation = $this->translations;

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

        return $translation;
    }

    public function setLocale(string $locale): void
    {
        if(str_contains($locale, '\\'))
        {
            $this->logger->error('Detected illegal character in locale lookup. Aborting.', ['character' => '\\']);
            throw new IllegalCharacterException();
        }

        if(str_contains($locale, '/'))
        {
            $this->logger->error('Detected illegal character in locale lookup. Aborting.', ['character' => '/']);
            throw new IllegalCharacterException();
        }

        $this->locale = $locale;
    }

    private function getLocale(): string
    {
        return $this->locale;
    }

    private function loadTranslations(): void
    {
        $translationPath = Software::TRANSLATIONS_DIR . '/' . $this->getLocale() . '.php';
        if(!file_exists($translationPath))
        {
            $this->logger->error('Failed loading the active translation.', ['file' => $translationPath]);
            throw new TranslationLocaleWasNotFoundException($this->locale);
        }
        $this->translations = require_once $translationPath;
    }

}
