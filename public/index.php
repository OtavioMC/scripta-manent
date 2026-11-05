<?php

declare(strict_types=1);

use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Http\Server\RequestHandlerInterface;

require __DIR__ . '/../vendor/autoload.php';

$aplicacao = require __DIR__ . '/../config/aplicacao.php';
if (!$aplicacao instanceof RequestHandlerInterface) {
    throw new LogicException('config/aplicacao.php precisa devolver um RequestHandlerInterface.');
}

$fabrica = new Psr17Factory();
$requisicao = (new ServerRequestCreator($fabrica, $fabrica, $fabrica, $fabrica))->fromGlobals();

(new SapiEmitter())->emit($aplicacao->handle($requisicao));
