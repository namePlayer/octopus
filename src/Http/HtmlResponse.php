<?php

declare(strict_types=1);

namespace App\Http;

use Laminas\Diactoros\Response;

class HtmlResponse extends Response
{

    public function __construct($body = '', int $status = 200, array $headers = [])
    {
        $respone = new Response();

        $respone->getBody()->write($body);

        parent::__construct($respone->getBody(), $status, $headers);
    }

}
