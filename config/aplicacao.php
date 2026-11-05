<?php

declare(strict_types=1);

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Server\RequestHandlerInterface;
use ScriptaManent\Http\Controller\VersaoController;
use ScriptaManent\Http\Roteador;

return (static function (): RequestHandlerInterface {
    $versao = getenv('VERSAO');
    $respostas = new Psr17Factory();

    return new Roteador($respostas, [
        ['GET', '/versao', new VersaoController($respostas, $versao === false ? 'dev' : $versao)],
    ]);
})();
