<?php declare(strict_types=1);

namespace App\Authentication\Controller;

use App\Authentication\Service\AccountService;
use App\Authentication\Service\AlertService;
use App\Authentication\Service\EmailService;
use App\Authentication\Service\PasswordResetTokenService;
use App\Authentication\Exception\PasswordResetTokenExpiredException;
use App\Authentication\Exception\PasswordResetTokenInvalidException;
use App\Authentication\Model\Account;
use App\Base\Exception\CsrfCheckFailedException;
use App\Base\Interface\LoggerInterface;
use App\Base\Http\HtmlResponse;
use App\Base\Interface\AlertServiceInterface;
use App\Base\Interface\CsrfProtectionInterface;
use App\Base\Interface\TranslationServiceInterface;
use App\Base\Service\TranslationService;
use App\Base\Service\CsrfProtectionService;
use App\Authentication\Exception\AccountEmailIsInvalidException;
use App\Authentication\Exception\PasswordToShortException;
use App\Authentication\Exception\AccountEmailIsAlreadyUsedException;
use Monolog\Logger;
use App\Authentication\Service\PasswordResetTokenService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use League\Plates\Engine;

class ForgotPasswordController
{
    public function __construct(
        private readonly EmailService $emailService,
        private readonly AccountService $accountService,
        private readonly AlertService $alertService,
        private readonly TranslationService $translationService,
        private readonly Logger $logger,
        private readonly CsrfProtectionService $csrfProtectionService
    )
    {
    }

    public function viewForgotPasswordForm(ServerRequestInterface $request): ResponseInterface
    {
        return new HtmlResponse($this->translationService->renderTemplate('authentication/forgot-password', [
            'messages' => $this->alertService->getAlerts(),
            'csrfToken' => $this->csrfProtectionService->generateToken('forgot-password'),
            'appTitle' => 'Passwort vergessen',
            'appDescription' => 'Geben Sie Ihre E-Mail-Adresse ein, um einen Reset-Link zu erhalten.',
            'submitButtonText' => 'Reset-Link anfordern',
            'linkText' => 'Zurück zur Login-Seite',
        ]));
    }

    public function requestPasswordReset(ServerRequestInterface $request): ResponseInterface
    {
        $email = $_POST['email'] ?? '';

        if (empty($email)) {
            $this->alertService->addAlert('danger', 'Bitte geben Sie eine gültige E-Mail-Adresse ein.');
            return $this->showForgotPasswordForm();
        }

        try {
            $account = $this->accountService->getUserByEmail($email);
            
            if ($account === null) {
                $this->logger->warning('No account found for email: ' . $email);
            } else {
                $this->emailService->sendPasswordResetEmail($account);
                $this->alertService->addAlert('success', 'Ein Passwort-Reset-Link wurde an Ihre E-Mail gesendet.');
            }
        } catch (\Exception $e) {
            $this->logger->error('Fehler beim Senden des Reset-Links: ' . $e->getMessage());
            $this->alertService->addAlert('danger', 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es später erneut.');
        }

        return $this->showForgotPasswordForm();
    }

    private function showForgotPasswordForm(): HtmlResponse
    {
        return new HtmlResponse($this->translationService->renderTemplate('authentication/forgot-password', [
            'messages' => $this->alertService->getAlerts(),
            'csrfToken' => $this->csrfProtectionService->generate(),
            'appTitle' => 'Passwort vergessen',
            'appDescription' => 'Geben Sie Ihre E-Mail-Adresse ein, um einen Reset-Link zu erhalten.',
            'submitButtonText' => 'Reset-Link anfordern',
            'linkText' => 'Zurück zur Login-Seite',
        ]));
    }

}
