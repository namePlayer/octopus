<?php
declare(strict_types=1);

namespace App\Authentication\Service;

use App\Account\Model\Account;
use App\Authentication\Exception\AccountJwtAuthenticationFailedException;
use App\Authentication\Exception\AccountJwtAuthenticationInformationMismatchException;
use App\Authentication\Exception\AccountJwtRefreshTokenExpiredException;
use App\Authentication\Exception\AccountJwtRefreshTokenGenerationFailedException;
use App\Authentication\Exception\AccountJwtRefreshTokenInvalidException;
use App\Authentication\Exception\AccountJwtTokenInvalidException;
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
            $this->setCookie($this->refreshCookieName, $refreshToken->token, $refreshToken->expires);
            $this->setCookie($this->tokenCookieName, $jwtToken->token, \DateTime::createFromTimestamp($jwtToken->expires));
        } catch (AccountJwtRefreshTokenGenerationFailedException $e) {
            $this->logger->error('JWT refresh token generation failed', [$e->getMessage()]);
            throw new AccountJwtAuthenticationFailedException();
        } catch (AccountJwtRefreshTokenInvalidException $e) {
            $this->logger->error('JWT token generation failed', [$e->getMessage()]);
            throw new AccountJwtAuthenticationFailedException();
        } catch (AccountWasNotFoundException $e) {
            $this->logger->error('JWT refresh token generation failed, no account could be linked to the jwt.', [$e->getMessage()]);
            throw new AccountJwtAuthenticationInformationMismatchException();
        } catch (AccountJwtRefreshTokenExpiredException $e) {
            $this->logger->error('JWT token generation failed, refresh token has already expired', [$e->getMessage()]);
            throw new AccountJwtAuthenticationFailedException();
        }
    }

    public function isJwtSessionValid(): bool
    {
        if(empty($_COOKIE[$this->tokenCookieName])) {
            return false;
        }

        try {
            $this->jwtService->verifyJwtToken($_COOKIE[$this->tokenCookieName], $_COOKIE[$this->refreshCookieName] ?? '');
            return true;
        } catch (AccountJwtAuthenticationInformationMismatchException $e) {
            $this->logger->info('JWT token marked as not valid due to information mismatch.', ['e' => $e->getMessage()]);
        } catch (AccountJwtRefreshTokenExpiredException $e) {
            $this->logger->info('JWT token marked as not valid due to the refresh token expired.');
        } catch (AccountJwtRefreshTokenInvalidException $e) {
            $this->logger->info('JWT token marked as not valid due to invalid refresh token.');
        } catch (AccountJwtTokenInvalidException $e) {
            $this->logger->info('JWT token marked as not valid due to the token itself could not be verified.');
        }
        return false;
    }

    private function setCookie(string $name, string $value, \DateTime $expires): bool
    {
        $setCookieRes = setcookie($name, $value, [
            "expires" => $expires->getTimestamp(),
            "path" => "/",
            "secure" => true,
            "httponly" => true,
            "samesite" => "None",
        ]);
        $_COOKIE[$name] = $value;
        $this->logger->debug('Setting cookie in authentication session service', ['result' => $setCookieRes]);
        return $setCookieRes;
    }

}
