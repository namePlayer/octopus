<?php
declare(strict_types=1);

namespace App\Authentication\Controller;

use App\Authentication\DTO\ForgotPasswordDTO;
use App\Authentication\DTO\ResetForgotPasswordDTO;
use App\Authentication\Exception\AccountEmailExceedsMaximumLengthException;
use App\Authentication\Exception\AccountEmailIsInvalidException;
use App\Authentication\Exception\AccountForgotPasswordCreationFailedException;
use App\Authentication\Exception\AccountWasNotFoundException;
use App\Authentication\Exception\PasswordRepeatDoesNotMatchException;
use App\Authentication\Exception\PasswordResetFailedWithInvalidatedTokenException;
use App\Authentication\Exception\PasswordResetFailedWithStillValidTokenException;
use App\Authentication\Exception\PasswordResetTokenHasAlreadyBeenUsedException;
use App\Authentication\Exception\PasswordResetTokenWasNotFoundException;
use App\Authentication\Exception\PasswordToShortException;
use App\Authentication\Service\PasswordResetService;
use App\Authentication\Validator\ForgotPasswordValidator;
use App\Authentication\Validator\PasswordResetValidator;
use App\Base\Exception\CsrfCheckFailedException;
use App\Base\Http\HtmlResponse;
use App\Base\Interface\AlertServiceInterface;
use App\Base\Service\CsrfProtectionService;
use App\Base\Service\TranslationService;
use App\Software;
use Laminas\Diactoros\Response\RedirectResponse;
use League\Plates\Engine;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

readonly class PasswordResetController
{

    public function __construct(
        private Engine                  $template,
        private AlertServiceInterface   $alertService,
        private CsrfProtectionService   $csrfProtectionService,
        private TranslationService      $translationService,
        private ForgotPasswordValidator $forgotPasswordValidator,
        private PasswordResetService    $passwordResetService,
        private PasswordResetValidator $passwordResetValidator,
    )
    {
    }

    public function viewPasswordForgot(ServerRequestInterface $request): ResponseInterface
    {
        return new HtmlResponse($this->template->render('authentication/forgotPassword'));
    }

    public function forgotPassword(ServerRequestInterface $request): ResponseInterface
    {
        $requestData = $request->getParsedBody();
        $forgotPasswordDTO = new ForgotPasswordDTO($requestData['forgotPasswordEmail']);

        try {
            $this->csrfProtectionService->validateCsrfTokenForForm('forgotPassword');
            $this->forgotPasswordValidator->validate($forgotPasswordDTO);
            $this->passwordResetService->sendForgotPassword($forgotPasswordDTO);
            $this->alertService->addAlert('success', Software::ALERT_TRANSLATION_INDICATOR.'account.forgotPassword.messages.success');
        } catch (CsrfCheckFailedException $e) {
            $this->alertService->addAlert('danger', Software::ALERT_TRANSLATION_INDICATOR.'account.forgotPassword.messages.csrfFailure');
        } catch (AccountEmailExceedsMaximumLengthException $e) {
            $this->alertService->addAlert('danger', Software::ALERT_TRANSLATION_INDICATOR.'account.general.messages.emailTooLong');
        } catch (AccountEmailIsInvalidException $e) {
            $this->alertService->addAlert('danger', Software::ALERT_TRANSLATION_INDICATOR.'account.general.messages.invalidEmail');
        } catch (AccountForgotPasswordCreationFailedException $e) {
            $this->alertService->addAlert('danger', Software::ALERT_TRANSLATION_INDICATOR.'account.forgotPassword.messages.creationFailed');
        }

        return $this->viewPasswordForgot($request);
    }

    public function viewPasswordReset(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        $resetToken = $this->passwordResetService->findPasswordForgotByToken($args['token'] ?? '');
        if($resetToken === null || $resetToken->used !== null){
            return $this->showPasswordResetErrorPage();
        }

        return new HtmlResponse($this->template->render('authentication/resetPassword'));
    }

    public function passwordReset(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        $resetToken = $this->passwordResetService->findPasswordForgotByToken($args['token'] ?? '');
        if($resetToken === null || $resetToken->used !== null){
            return $this->showPasswordResetErrorPage();
        }

        $data = $request->getParsedBody();
        $resetPasswordDTO = new ResetForgotPasswordDTO(
            $resetToken->token,
            $data['resetNewPassword'] ?? '',
            $data['resetNewPasswordRepeat'] ?? ''
        );
        try {
            $this->csrfProtectionService->validateCsrfTokenForForm('resetPassword');
            $this->passwordResetValidator->validate($resetPasswordDTO);
            $this->passwordResetService->resetPasswordWhenForgotten($resetPasswordDTO);
            $this->alertService->addAlert('success', Software::ALERT_TRANSLATION_INDICATOR.'account.resetPassword.messages.success');
            return new RedirectResponse('/authentication/login');
        } catch (CsrfCheckFailedException $e) {
            $this->alertService->addAlert('danger', Software::ALERT_TRANSLATION_INDICATOR.'account.resetPassword.messages.csrfFailure');
        } catch (PasswordResetFailedWithStillValidTokenException $e) {
            $this->alertService->addAlert('danger', Software::ALERT_TRANSLATION_INDICATOR.'account.resetPassword.messages.tokenStillValid');
        } catch (PasswordRepeatDoesNotMatchException $e) {
            $this->alertService->addAlert('danger', Software::ALERT_TRANSLATION_INDICATOR.'account.general.messages.passwordRepeatWrong');
        } catch (PasswordToShortException $e) {
            $this->alertService->addAlert('danger', Software::ALERT_TRANSLATION_INDICATOR.'account.general.messages.passwordTooShort');
        } catch (PasswordResetFailedWithInvalidatedTokenException $e) {
            $this->alertService->addAlert('danger', Software::ALERT_TRANSLATION_INDICATOR.'account.resetPassword.messages.tokenWasInvalidated');
            return $this->showPasswordResetErrorPage();
        } catch (PasswordResetTokenHasAlreadyBeenUsedException|PasswordResetTokenWasNotFoundException|AccountWasNotFoundException $e) {
            return $this->showPasswordResetErrorPage();
        }
        return $this->viewPasswordReset($request, $args);
    }

    private function showPasswordResetErrorPage(): ResponseInterface
    {
        return new HtmlResponse($this->template->render('authentication/resetPasswordError'));
    }

}
