<?php
declare(strict_types=1);

namespace App\Authentication\Controller;

use App\Authentication\DTO\CreateAccountDTO;
use App\Authentication\Exception\AccountCreationFailedException;
use App\Authentication\Exception\EmailExceedsMaximumLengthException;
use App\Authentication\Exception\InvalidAccountEmailException;
use App\Authentication\Exception\PasswordRepeatDoesNotMatchException;
use App\Authentication\Exception\PasswordToShortException;
use App\Authentication\Service\AuthenticationService;
use App\Authentication\Validator\RegistrationValueValidator;
use App\Base\Http\HtmlResponse;
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
        $registerDTO = new CreateAccountDTO($data['registrationEmail'], $data['registrationPassword'], $data['registrationRepeatPassword']);

        try {
            $this->registrationValueValidator->validate($registerDTO);
            $this->authenticationService->register($registerDTO);
            $this->messages[] = ['type' => 'success', 'message' => 'Das Benutzerkonto wurde angelegt.'];
        } catch (InvalidAccountEmailException $e) {
            $this->messages[] = ['type' => 'danger', 'message' => 'Die angegebene E-Mail-Adresse ist ungültig.'];
        } catch (PasswordRepeatDoesNotMatchException $e) {
            $this->messages[] = ['type' => 'danger', 'message' => 'Die angegebenen Passwörter stimmen nicht überein.'];
        } catch (PasswordToShortException $e) {
            $this->messages[] = ['type' => 'danger', 'message' => 'Das angegebene Passwort ist zu kurz.'];
        } catch (EmailExceedsMaximumLengthException $e) {
            $this->messages[] = ['type' => 'danger', 'message' => 'Die angegebene E-Mail-Adresse überschreitet das zulässige Maximum'];
        } catch (AccountCreationFailedException $e) {
            $this->messages[] = ['type' => 'danger', 'message' => 'Das Benutzerkonto konnte aufgrund eines Fehlers nicht angelegt werden.'];
        }

        return $this->viewRegisterForm($request);
    }

}
