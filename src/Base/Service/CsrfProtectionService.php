<?php
declare(strict_types=1);

namespace App\Base\Service;

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

    public function generateCsrfTokenForForm(string $form): string
    {
        $token = $this->generateCsrfTokenString();
        $_SESSION['csrfTokens'][$form] = $token;
        return $token;
    }

    public function validateCsrfTokenForForm(string $form): void
    {
        if(!isset($_SESSION['csrfTokens'][$form])) {
            $this->logger->warning('Looking for CSRF Token but not found for form: '.$form);
            throw new CsrfCheckFailedException();
        }

        if($_SESSION['csrfTokens'][$form] === $_POST['csrf_'.$form]) {
            unset($_SESSION['csrfTokens'][$form]);
            return;
        }

        throw new CsrfCheckFailedException();
    }

    private function generateCsrfTokenString(): string
    {
        try {
            return bin2hex(random_bytes(32));
        } catch (RandomException $e) {
            $this->logger->error($e->getMessage(), [$this::class]);
            throw new ImproperlyConfiguredServerException(previous: $e);
        }
    }

}
