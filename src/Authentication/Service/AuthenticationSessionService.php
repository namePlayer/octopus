<?php
declare(strict_types=1);

namespace App\Authentication\Service;

use App\Account\Model\Account;
use App\Authentication\Exception\AccountJwtAuthenticationFailedException;
use App\Authentication\Exception\AccountJwtAuthenticationInformationMismatchException;
use App\Authentication\Exception\AccountJwtRefreshTokenGenerationFailedException;
use App\Authentication\Exception\AccountJwtRefreshTokenInvalidException;
use App\Authentication\Exception\AccountWasNotFoundException;
use Monolog\Logger;

class AuthenticationSessionService
{

    public function __construct(
        private readonly Logger $logger,
        private readonly JWTService $jwtService,
        private readonly string $tokenCookieName = 'token',
        private readonly string $refreshCookieName = 'refreshToken',
    )
    {
    }

    public function createSession(Account $account): void
    {
        try {
            $refreshToken = $this->jwtService->generateJwtRefreshToken($account->id);
            $jwtToken = $this->jwtService->generateJwtTokenFromRefreshToken($refreshToken->token);
            setcookie($this->refreshCookieName, $refreshToken->token, $refreshToken->expires->getTimestamp());
            setcookie($this->tokenCookieName, $jwtToken->token, $jwtToken->expires);
        } catch (AccountJwtRefreshTokenGenerationFailedException $e) {
            $this->logger->error('JWT refresh token generation failed', [$e->getMessage()]);
            throw new AccountJwtAuthenticationFailedException();
        } catch (AccountJwtRefreshTokenInvalidException $e) {
            $this->logger->error('JWT token generation failed', [$e->getMessage()]);
            throw new AccountJwtAuthenticationFailedException();
        } catch (AccountWasNotFoundException $e) {
            $this->logger->error('JWT refresh token generation failed', [$e->getMessage()]);
            throw new AccountJwtAuthenticationInformationMismatchException();
        }
    }

    private function validateSession(): void
    {

    }

}
