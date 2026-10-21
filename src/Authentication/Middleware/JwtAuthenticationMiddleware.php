<?php
declare(strict_types=1);

namespace App\Authentication\Middleware;

use App\Account\Model\Account;
use App\Authentication\Exception\AccountJwtAuthenticationFailedException;
use App\Authentication\Exception\AccountJwtAuthenticationInformationMismatchException;
use App\Authentication\Service\AccountService;
use App\Authentication\Service\AuthenticationSessionService;
use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class JwtAuthenticationMiddleware implements MiddlewareInterface
{

    public function __construct(
        private readonly AuthenticationSessionService $authenticationSessionService,
    )
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $loginRedirectResponse = new RedirectResponse('/authentication/login');
        if($this->authenticationSessionService->isRefreshTokenValid() === false)
        {
            return $loginRedirectResponse;
        }

        if($this->authenticationSessionService->isJwtSessionValid() === false)
        {
            $token = $this->authenticationSessionService->getRefreshToken();
            if($token === null)
            {
                return $loginRedirectResponse;
            }
            try {
                $this->authenticationSessionService->createJwtSession($this->authenticationSessionService->getRefreshToken());
            } catch (AccountJwtAuthenticationFailedException|AccountJwtAuthenticationInformationMismatchException $e) {
                return $loginRedirectResponse;
            }
        }

        $account = $this->authenticationSessionService->getJwtAccount();

        if($account instanceof Account)
        {
            return $handler->handle($request->withAttribute(Account::class, $account));
        }

        return $loginRedirectResponse;
    }

}
