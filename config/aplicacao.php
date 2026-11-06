<?php

declare(strict_types=1);

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Server\RequestHandlerInterface;
use ScriptaManent\Http\Controller\InicioController;
use ScriptaManent\Http\Controller\VersaoController;
use ScriptaManent\Http\PaginaDeErro;
use ScriptaManent\Http\Roteador;
use ScriptaManent\Http\TratadorDeErros;
use ScriptaManent\Http\Visao;
use ScriptaManent\Infrastructure\Log\LogEmArquivo;
use Twig\Environment;

return (static function (): RequestHandlerInterface {
    $versao = getenv('VERSAO');
    $respostas = new Psr17Factory();

    $twig = require __DIR__ . '/twig.php';
    if (!$twig instanceof Environment) {
        throw new LogicException('config/twig.php precisa devolver um Twig\Environment.');
    }
    $visao = new Visao($twig, $respostas);
    $paginaDeErro = new PaginaDeErro($visao);

    $roteador = new Roteador($paginaDeErro, [
        ['GET', '/', new InicioController($visao)],
        ['GET', '/versao', new VersaoController($respostas, $versao === false ? 'dev' : $versao)],
    ]);

    return new TratadorDeErros($roteador, $paginaDeErro, new LogEmArquivo('php://stderr'));
})();
