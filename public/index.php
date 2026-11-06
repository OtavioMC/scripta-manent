<?php

declare(strict_types=1);

use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Http\Server\RequestHandlerInterface;
use ScriptaManent\Http\Acesso\SessaoDeAcesso;
use ScriptaManent\Http\SessaoNativa;

require __DIR__ . '/../vendor/autoload.php';

$criarAplicacao = require __DIR__ . '/../config/aplicacao.php';
if (!$criarAplicacao instanceof Closure) {
    throw new LogicException('config/aplicacao.php precisa devolver a função que monta a aplicação.');
}
$aplicacao = $criarAplicacao(new SessaoNativa(SessaoDeAcesso::INATIVIDADE_MAXIMA_EM_SEGUNDOS));
if (!$aplicacao instanceof RequestHandlerInterface) {
    throw new LogicException('A montagem da aplicação precisa devolver um RequestHandlerInterface.');
}

$fabrica = new Psr17Factory();
$requisicao = (new ServerRequestCreator($fabrica, $fabrica, $fabrica, $fabrica))->fromGlobals();

(new SapiEmitter())->emit($aplicacao->handle($requisicao));
