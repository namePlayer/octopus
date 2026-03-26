<?php
declare(strict_types=1);

namespace App\Http;

use Laminas\Diactoros\Response\JsonResponse as LaminasJsonResponse;

use function is_null;

class JsonResponse extends LaminasJsonResponse
{
    public function __construct(
        int $code,
        ?array $data = null,
        string $message = '',
        array $headers = [],
        int $encodingOptions = LaminasJsonResponse::DEFAULT_JSON_FLAGS
    ) {
        $response = [
            'code' => $code,
            'message' => empty($message) ? $this->getDefaultMessageForCode($code) : $message,
        ];

        if (!is_null($data)) {
            $response['data'] = $data;
        }

        parent::__construct($response, $code, $headers, $encodingOptions);
    }

    private function getDefaultMessageForCode(int $code): string
    {
        return match ($code) {
            200 => 'success',
            400 => 'data-missing',
            403 => 'authentication-required',
            404 => 'not-found',
            405 => 'method-not-allowed',
            410 => 'no-longer-available',
            500 => 'unknown-error',
            default => 'no-message-declared'
        };
    }
}
