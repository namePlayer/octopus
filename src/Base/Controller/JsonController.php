<?php
declare(strict_types=1);

namespace App\Base\Controller;

use App\Base\Http\JsonResponse;
use Laminas\Diactoros\Response;
use Psr\Http\Message\RequestInterface;

class JsonController
{

    public function load(RequestInterface $request): Response
    {
        return new JsonResponse(200, ['Controller' => 'JsonController']);
    }

}
