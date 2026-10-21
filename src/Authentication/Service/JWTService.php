<?php
declare(strict_types=1);

namespace App\Authentication\Service;

use App\Account\Model\Account;
use App\Authentication\DTO\JwtTokenAuthenticationDTO;
use App\Authentication\Exception\AccountJwtAuthenticationInformationMismatchException;
use App\Authentication\Exception\AccountJwtRefreshTokenExpiredException;
use App\Authentication\Exception\AccountJwtRefreshTokenGenerationFailedException;
use App\Authentication\Exception\AccountJwtRefreshTokenInvalidException;
use App\Authentication\Exception\AccountJwtTokenExpiredException;
use App\Authentication\Exception\AccountJwtTokenInvalidException;
use App\Authentication\Exception\AccountWasNotFoundException;
use App\Authentication\Model\AccountJwtRefreshToken;
use App\Authentication\Table\AccountJwtRefreshTokenTable;
use App\Base\Exception\InvalidJsonException;
use App\Software;
use DateTime;
use Jose\Component\Core\JWK;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Signature\JWSVerifier;
use Jose\Component\Signature\Serializer\CompactSerializer;
use Ramsey\Uuid\Uuid;

class JWTService
{

    public function __construct(
        private readonly AccountJwtRefreshTokenTable $accountJwtRefreshTokenTable,
        private readonly AccountService $accountService,
        private readonly JWK $jsonWebTokenKey,
        private readonly JWSBuilder $jwsBuilder,
        private readonly JWSVerifier $jwsVerifier,
        private readonly CompactSerializer $compactSerializer
    )
    {
    }

    public function generateJwtTokenFromRefreshToken(string $refreshToken, string $audience = ''): JwtTokenAuthenticationDTO
    {
        $refreshToken = $this->findRefreshTokenByToken($refreshToken);
        if($refreshToken === null)
        {
            throw new AccountJwtRefreshTokenInvalidException();
        }

        if($refreshToken->expires <= new DateTime())
        {
            throw new AccountJwtRefreshTokenExpiredException();
        }

        $account = $this->accountService->getAccountById($refreshToken->account);
        if($account === null)
        {
            throw new AccountWasNotFoundException();
        }

        $payload = [
            'iss' => $_ENV['SOFTWARE_HOST'],
            'aud' => empty($audience) ? $_ENV['SOFTWARE_HOST'] : $audience,
            'iat' => new \DateTime()->getTimestamp(),
            'exp' => new \DateTime()->modify('+'.Software::JWT_TOKEN_LIFETIME_MINUTES.' minutes')->getTimestamp(),
            'sub' => $account->uuid,
            'jti' => md5($refreshToken->token)
        ];

        $jws = $this->jwsBuilder->create()
            ->withPayload(json_encode($payload))
            ->addSignature($this->jsonWebTokenKey, ['alg' => 'HS256'])
            ->build();
        return new JwtTokenAuthenticationDTO($this->compactSerializer->serialize($jws), $payload['exp']);
    }

    public function verifyJwtToken(string $jwt, string $refreshTokenValue): Account
    {
        try {
            $jwtToken = $this->compactSerializer->unserialize($jwt);
        } catch (\InvalidArgumentException) {
            throw new AccountJwtTokenInvalidException();
        }

        if($this->jwsVerifier->verifyWithKey($jwtToken, $this->jsonWebTokenKey, 0) === false) {
            throw new AccountJwtTokenInvalidException();
        }

        $payload = json_decode($jwtToken->getPayload(), true);
        if($payload === null)
        {
            throw new InvalidJsonException();
        }

        if($payload['jti'] != md5($refreshTokenValue))
        {
            throw new AccountJwtAuthenticationInformationMismatchException("Refresh token does not match the payloads refresh token");
        }

        $refreshToken = $this->findRefreshTokenByToken($refreshTokenValue);
        if($refreshToken === null)
        {
            throw new AccountJwtRefreshTokenInvalidException();
        }

        $currentDateTime = new \DateTimeImmutable();

        if($payload['exp'] <= $currentDateTime->getTimestamp() || $refreshToken->expires <= $currentDateTime) {
            throw new AccountJwtTokenExpiredException();
        }

        $account = $this->accountService->getAccountById($refreshToken->account);
        if($account === null || $payload['sub'] != $account->uuid)
        {
            throw new AccountWasNotFoundException();
        }

        return $account;
    }

    public function verifyRefreshToken(string $refreshTokenValue): void
    {
        $jwtToken = $this->findRefreshTokenByToken($refreshTokenValue);
        if($jwtToken === null)
        {
            throw new AccountJwtRefreshTokenInvalidException();
        }

        if($jwtToken->expires <= new \DateTimeImmutable())
        {
            throw new AccountJwtRefreshTokenExpiredException($jwtToken->expires->format(Software::DB_DATETIME_FORMAT));
        }
    }

    public function generateJwtRefreshToken(int $accountId): AccountJwtRefreshToken
    {
        $accountJwtRefreshToken = new AccountJwtRefreshToken();
        do {
            $accountJwtRefreshToken->token = Uuid::uuid4()->toString();
        } while ($this->findRefreshTokenByToken($accountJwtRefreshToken->token) instanceof AccountJwtRefreshToken);
        $accountJwtRefreshToken->account = $accountId;
        $accountJwtRefreshToken->issued = new \DateTime();
        $accountJwtRefreshToken->expires = new \DateTime()->modify('+'.Software::JWT_REFRESH_TOKEN_LIFETIME_MINUTES.' minutes');
        if($this->accountJwtRefreshTokenTable->insert($accountJwtRefreshToken)) {
            return $accountJwtRefreshToken;
        }
        throw new AccountJwtRefreshTokenGenerationFailedException();
    }

    public function findRefreshTokenByToken(string $token): ?AccountJwtRefreshToken
    {
        return $this->accountJwtRefreshTokenTable->findByToken($token);
    }

}
