<?php

namespace App\Base\Factory;

use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Core\JWK;
use Jose\Component\Signature\Algorithm\HS256;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Signature\JWSVerifier;
use Jose\Component\Signature\Serializer\CompactSerializer;

class JwtFactory
{

    private readonly JWK $jwk;
    private readonly JWSBuilder $jwsBuilder;
    private readonly JWSVerifier $jwsVerifier;
    private readonly CompactSerializer $compactSerializer;

    public function __construct()
    {
        $this->jwk = new JWK([
            'kty' => 'oct',
            'k' => $_ENV['APP_JWT_SECRET']
        ]);
        $algorithmManager = new AlgorithmManager([new HS256()]);
        $this->jwsBuilder = new JWSBuilder($algorithmManager);
        $this->jwsVerifier = new JWSVerifier($algorithmManager);
        $this->compactSerializer = new CompactSerializer();
    }

    public function getJwk(): JWK
    {
        return $this->jwk;
    }

    public function getJwsBuilder(): JWSBuilder
    {
        return $this->jwsBuilder;
    }

    public function getJwsVerifier(): JWSVerifier
    {
        return $this->jwsVerifier;
    }

    public function getCompactSerializer(): CompactSerializer
    {
        return $this->compactSerializer;
    }

}
