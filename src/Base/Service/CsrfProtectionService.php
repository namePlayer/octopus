<?php
declare(strict_types=1);

namespace App\Base\Service;

use App\Base\DTO\CsrfTokenDTO;
use App\Base\Exception\CsrfCheckFailedException;
use App\Base\Exception\ImproperlyConfiguredServerException;
use Monolog\Logger;
use Random\RandomException;

class CsrfProtectionService
{

    public function __construct(
        private readonly Logger $logger
    )
    {}

    public function generateCsrfTokenForForm(string $form): CsrfTokenDTO
    {
        $secret = $this->getCsrfSecret();

        $token = hash_hmac('sha256', $form, $secret);
        $_SESSION['csrfTokens'][$form] = $token;

        return new CsrfTokenDTO('csrf_'.$form, $token);
    }

    public function validateCsrfTokenForForm(string $form): void
    {
        if(!isset($_SESSION['csrfTokens'][$form]) || !isset($_POST['csrf_'.$form])) {
            $this->logger->warning('Looking for CSRF Token but not found for form: '.$form);
            throw new CsrfCheckFailedException();
        }

        if(hash_equals($this->generateCsrfTokenForForm($form)->token, $_POST['csrf_'.$form])) {
            unset($_SESSION['csrfTokens'][$form]);
            return;
        }

        throw new CsrfCheckFailedException();
    }

    private function getCsrfSecret(): string
    {
        if(!isset($_SESSION['csrfSecret']))
        {
            $this->generateCsrfSecret();
        }
        return $_SESSION['csrfSecret'];
    }

    private function generateCsrfSecret(): void
    {
        try {
            $_SESSION['csrfSecret'] = bin2hex(random_bytes(32));
        } catch (RandomException $e) {
            $this->logger->error($e->getMessage(), [$this::class]);
            throw new ImproperlyConfiguredServerException(previous: $e);
        }
    }

}
