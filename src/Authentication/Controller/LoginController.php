<?php
declare(strict_types=1);

namespace App\Authentication\Controller;

use App\Authentication\DTO\LoginAccountDTO;
use App\Authentication\Exception\AccountInvalidLoginCredentialsEnteredException;
use App\Authentication\Service\AuthenticationService;
use App\Base\Exception\CsrfCheckFailedException;
use App\Base\Http\HtmlResponse;
use App\Base\Interface\AlertServiceInterface;
use App\Base\Interface\CsrfProtectionInterface;
use League\Plates\Engine;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class LoginController
{

    private array $messages = [];

    public function __construct(
        private readonly Engine $template,
        private readonly AuthenticationService $authenticationService,
        private readonly CsrfProtectionInterface $csrfProtectionService,
        private readonly AlertServiceInterface $alertService,
    )
    {
    }

    public function viewLoginForm(ServerRequestInterface $request): ResponseInterface
    {
        return new HtmlResponse($this->template->render('authentication/login', [
            'messages' => $this->messages,
        ]));
    }

    public function login(ServerRequestInterface $request): ResponseInterface
    {
        $post = $request->getParsedBody();

        $loginAccountDto = new LoginAccountDTO(
            $post['loginEmail'] ?? '',
            $post['loginPassword'] ?? '');

        try {
            $this->csrfProtectionService->validateCsrfTokenForForm('login');
            $this->authenticationService->login($loginAccountDto);
            $this->alertService->addAlert('success', 'Credentials are correct, nice one :)');
        } catch (CsrfCheckFailedException $e) {
            $this->alertService->addAlert('danger', 'Das Benutzerkonto konnte aufgrund eines CSRF Fehlers nicht angelegt werden.');
        } catch (AccountInvalidLoginCredentialsEnteredException $e) {
            $this->alertService->addAlert('danger', 'Die angegebenen Anmeldeinformationen konnten keinem Benutzerkonto zugewiesen werden.');
        }

        return $this->viewLoginForm($request);
    }

}
