<?php
declare(strict_types=1);

namespace App\Authentication\Controller;

use App\Authentication\Service\AuthenticationService;
use App\Base\Http\HtmlResponse;
use League\Plates\Engine;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class LoginController
{

    public function __construct(
        private readonly Engine $template,
        private readonly AuthenticationService $authenticationService,
    )
    {
    }

    public function viewLoginForm(ServerRequestInterface $request): ResponseInterface
    {
        return new HtmlResponse($this->template->render('authentication/login', []));
    }

    public function login(ServerRequestInterface $request): ResponseInterface
    {

    }

}
