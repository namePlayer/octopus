<?php
declare(strict_types=1);

namespace App\Base\Controller;

use App\Base\Http\HtmlResponse;
use Laminas\Diactoros\Response;
use League\Plates\Engine;
use Psr\Http\Message\RequestInterface;

class IndexController
{

    public function __construct(
        private Engine $templateEngine
    )
    {
    }

    public function load(RequestInterface $request): Response
    {
        return new HtmlResponse($this->templateEngine->render('index'));
    }

}
