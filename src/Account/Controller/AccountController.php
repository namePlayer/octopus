<?php
declare(strict_types=1);

namespace App\Account\Controller;

use App\Authentication\Service\AuthenticationSessionService;
use App\Base\Http\HtmlResponse;
use League\Plates\Engine;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class AccountController
{

    public function __construct(
        private readonly Engine $template,
        private readonly AuthenticationSessionService $authenticationSessionService,
    )
    {
    }

    public function viewAccount(ServerRequestInterface $request): ResponseInterface
    {
        var_dump($this->authenticationSessionService->isJwtSessionValid());

        return new HtmlResponse($this->template->render('account/account'));
    }

}
