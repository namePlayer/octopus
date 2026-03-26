<?php declare(strict_types=1);

use Symfony\Component\Console\Application;

require_once __DIR__.'/../vendor/autoload.php';

$console = new Application();

$console->add(new \App\Command\CacheClearCommand());

$console->run();
