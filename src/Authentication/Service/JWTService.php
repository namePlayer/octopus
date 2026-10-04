<?php
declare(strict_types=1);

namespace App\Authentication\Service;

use App\Authentication\DTO\JwtTokenAuthenticationDTO;
use App\Authentication\Exception\AccountJwtRefreshTokenGenerationFailedException;
use App\Authentication\Exception\AccountJwtRefreshTokenInvalidException;
use App\Authentication\Exception\AccountWasNotFoundException;
use App\Authentication\Model\AccountJwtRefreshToken;
use App\Authentication\Table\AccountJwtRefreshTokenTable;
use App\Software;
use Jose\Component\Core\JWK;
use Jose\Component\Signature\JWS;
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
            'jti' => $refreshToken->token
        ];

        $jws = $this->jwsBuilder->create()
            ->withPayload(json_encode($payload))
            ->addSignature($this->jsonWebTokenKey, ['alg' => 'HS256'])
            ->build();
        return new JwtTokenAuthenticationDTO($this->compactSerializer->serialize($jws), $payload['exp']);
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

    private function findRefreshTokenByToken(string $token): ?AccountJwtRefreshToken
    {
        return $this->accountJwtRefreshTokenTable->findByToken($token);
    }

}
