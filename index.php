<?php
require __DIR__ . '/vendor/autoload.php';

use Slim\Factory\AppFactory;
use Nyholm\Psr7\Factory\Psr17Factory;

$psr17Factory = new Psr17Factory();
AppFactory::setResponseFactory($psr17Factory);

$app = AppFactory::create();
$app->addBodyParsingMiddleware();

// Carregar rotas
(require __DIR__ . '/src/endpoint/transacao.php')($app);

$app->run();
