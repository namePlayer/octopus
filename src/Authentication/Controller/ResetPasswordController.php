<?php

declare(strict_types=1);

namespace App\Authentication\Controller;

use App\Authentication\Service\AuthService;
use App\Authentication\Service\PasswordResetTokenService;
use App\Base\Service\AlertService;
use App\Base\Service\TranslationService;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use DateTime;

class ResetPasswordController
{
    public function __construct(
        private readonly PasswordResetTokenService $tokenService,
        private readonly AuthService $authService,
        private readonly AlertService $alertService,
        private readonly TranslationService $translationService,
        private readonly LoggerInterface $logger
    ) {}

    public function viewResetPasswordForm(string $token, ServerRequestInterface $request): ResponseInterface
    {
        try {
            $account = $this->tokenService->validateAndExpire($token);

            if ($account === null) {
                $this->logger->warning('ResetPasswordController: Token nicht valide');
                $viewData = $this->translationService->getTranslations([
                    'resetFormTitle' => 'Passwort setzen',
                    'resetFormDescription' => 'Bitte geben Sie Ihr neues Passwort ein.',
                    'resetFormPassword' => 'Neues Passwort',
                    'resetFormSubmit' => 'Passwort setzen'
                ]);

                return $this->authService->showPage(
                    $viewData,
                    'formResetPassword'
                );
            }

            // Account in die Session/Lokalspeicher setzen
            $this->authService->setAccountInStorage($account);
        } catch (NotFoundExceptionInterface $e) {
            $this->logger->warning('ResetPasswordController: Token nicht gefunden');
            
            $viewData = $this->translationService->getTranslations([
                'resetFormTitle' => 'Passwort setzen',
                'resetFormDescription' => 'Ungültiger Token. Der Link ist abgelaufen oder ungültig.'
            ]);

            return $this->authService->showPage(
                $viewData,
                'formResetPassword'
            );
        } catch (\Throwable $e) {
            $this->logger->error('ResetPasswordController: Fehler: ' . $e->getMessage());
            
            $viewData = $this->translationService->getTranslations([
                'resetFormTitle' => 'Passwort setzen',
                'resetFormDescription' => 'Ein unerwarteter Fehler ist aufgetreten.'
            ]);

            return $this->authService->showPage(
                $viewData,
                'formResetPassword'
            );
        }
    }

    /**
     * Setzt das Passwort per POST request
     */
    public function resetPassword(string $token, string $newPassword, ServerRequestInterface $request): ResponseInterface
    {
        try {
            try {
                $account = $this->tokenService->validateAndExpire($token);
                if ($account === null) {
                    throw new \RuntimeException('Token nicht valide oder abgelaufen');
                }
            } catch (NotFoundExceptionInterface $e) {
                $this->logger->warning('ResetPasswordController: Token nicht valide');
                
                $viewData = $this->translationService->getTranslations([
                    'resetFormTitle' => 'Passwort setzen',
                    'resetFormDescription' => 'Ungültiger Token. Der Link ist abgelaufen oder ungültig.'
                ]);

                return $this->authService->showPage(
                    $viewData,
                    'formResetPassword'
                );
            }

            // Passwort hashen und setzen
            if (!$this->authService->setNewPassword(
                $account->getId(),
                $newPassword,
                DateTime::createFromFormat(
                    'd.m.Y H:i',
                    $account->getPasswordResetTokenExpires() ?? 'now',
                    false
                )
            )) {
                throw new \RuntimeException('Passwort konnte nicht gesetzt werden');
            }

            // Nach erfolgreichem Setzen Token expirieren
            $this->tokenService->expireToken($token);

            $this->logger->info('Passwort erfolgreich geändert für Account-ID: ' . $account->getId());

            $viewData = $this->translationService->getTranslations([
                'messageTitleResetSuccess' => 'Erfolg',
                'messageResetSuccess' => 'Ihr Passwort wurde erfolgreich geändert. Sie können sich nun anmelden.'
            ]);

            return $this->authService->showSuccessMessage($viewData);
        } catch (\Throwable $e) {
            $this->logger->error('ResetPasswordController: Fehler bei Passwortänderung: ' . $e->getMessage());

            $viewData = $this->translationService->getTranslations([
                'messageTitleGenericError' => 'Fehler',
                'messageGenericError' => 'Ein Fehler ist beim Ändern des Passworts aufgetreten.'
            ]);

            return $this->authService->showPage(
                $viewData,
                'formResetPassword'
            );
        }
    }
}
