<?php
declare(strict_types=1);

namespace App\Account\Controller;

use App\Base\Http\HtmlResponse;
use League\Plates\Engine;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class AccountController
{

    public function __construct(
        private readonly Engine $template,
    )
    {
    }

    public function viewAccount(ServerRequestInterface $request): ResponseInterface
    {
        return new HtmlResponse($this->template->render('account/account'));
    }

}
