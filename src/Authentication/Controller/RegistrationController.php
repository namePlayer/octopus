<?php
declare(strict_types=1);

namespace App\Authentication\Controller;

use App\Authentication\DTO\CreateAccountDTO;
use App\Authentication\Exception\AccountCreationFailedException;
use App\Authentication\Exception\AccountDoesNotAcceptTermsException;
use App\Authentication\Exception\AccountEmailExceedsMaximumLengthException;
use App\Authentication\Exception\AccountEmailIsAlreadyUsedException;
use App\Authentication\Exception\AccountEmailIsInvalidException;
use App\Authentication\Exception\PasswordRepeatDoesNotMatchException;
use App\Authentication\Exception\PasswordToShortException;
use App\Authentication\Service\AuthenticationService;
use App\Authentication\Validator\RegistrationValueValidator;
use App\Base\Exception\CsrfCheckFailedException;
use App\Base\Http\HtmlResponse;
use App\Base\Interface\AlertServiceInterface;
use App\Base\Interface\CsrfProtectionInterface;
use App\Base\Interface\TranslationInterface;
use App\Software;
use Laminas\Diactoros\Response\RedirectResponse;
use League\Plates\Engine;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class RegistrationController
{

    private array $messages = [];

    public function __construct(
        private readonly Engine $template,
        private readonly RegistrationValueValidator $registrationValueValidator,
        private readonly AuthenticationService $authenticationService,
        private readonly CsrfProtectionInterface $csrfProtectionService,
        private readonly AlertServiceInterface $alertService,
        private readonly TranslationInterface $translationService
    )
    {
    }

    public function viewRegisterForm(ServerRequestInterface $request): ResponseInterface
    {
        return new HtmlResponse($this->template->render('authentication/register', ['messages' => $this->messages]));
    }

    public function register(ServerRequestInterface $request): ResponseInterface
    {
        $data = $request->getParsedBody();
        $registerDTO = new CreateAccountDTO(
            $data['registrationEmail'] ?? '',
            $data['registrationPassword'] ?? '',
            $data['registrationRepeatPassword'] ?? '',
            isset($data['registrationAcceptTerms']));

        try {
            $this->registrationValueValidator->validate($registerDTO);
            $this->csrfProtectionService->validateCsrfTokenForForm('registration');
            $this->authenticationService->register($registerDTO);
            $this->alertService->addAlert('success', Software::ALERT_TRANSLATION_INDICATOR.'account.registration.messages.success');
            return new RedirectResponse('/authentication/login');
        } catch (AccountEmailIsInvalidException $e) {
            $this->alertService->addAlert('danger', Software::ALERT_TRANSLATION_INDICATOR.'account.general.messages.invalidEmail');
        } catch (PasswordRepeatDoesNotMatchException $e) {
            $this->alertService->addAlert('danger', Software::ALERT_TRANSLATION_INDICATOR.'account.general.messages.passwordRepeatWrong');
        } catch (PasswordToShortException $e) {
            $this->alertService->addAlert('danger', Software::ALERT_TRANSLATION_INDICATOR.'account.general.messages.passwordTooShort');
        } catch (AccountEmailExceedsMaximumLengthException $e) {
            $this->alertService->addAlert('danger', Software::ALERT_TRANSLATION_INDICATOR.'account.general.messages.emailTooLong');
        } catch (AccountCreationFailedException $e) {
            $this->alertService->addAlert('danger', Software::ALERT_TRANSLATION_INDICATOR.'account.registration.messages.failure');
        } catch (CsrfCheckFailedException $e) {
            $this->alertService->addAlert('danger', Software::ALERT_TRANSLATION_INDICATOR.'account.registration.messages.csrfFailure');
        } catch (AccountDoesNotAcceptTermsException $e) {
            $this->alertService->addAlert('danger', Software::ALERT_TRANSLATION_INDICATOR.'account.registration.messages.doesNotAcceptTerms');
        } catch (AccountEmailIsAlreadyUsedException $e) {
            // This thing is never used in here because the necessary flag "throwDuplicateEmailError" is not set to true, therefore not needed.
            // The stated flag is only used in the console command
        }

        return $this->viewRegisterForm($request);
    }

}
