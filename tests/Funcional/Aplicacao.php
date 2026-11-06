<?php

declare(strict_types=1);

namespace ScriptaManent\Tests\Funcional;

use Closure;
use LogicException;
use Nyholm\Psr7\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ScriptaManent\Tests\Duble\SessaoEmMemoria;

final class Aplicacao
{
    public static function abrir(string $metodo, string $caminho, bool $logado = true): ResponseInterface
    {
        $sessao = new SessaoEmMemoria();
        if ($logado) {
            $sessao->guardar('acesso', ['usuario' => 'avaliador', 'ultimoUso' => time()]);
        }

        $criar = require __DIR__ . '/../../config/aplicacao.php';
        if (!$criar instanceof Closure) {
            throw new LogicException('config/aplicacao.php precisa devolver a função que monta a aplicação.');
        }
        $aplicacao = $criar($sessao);
        if (!$aplicacao instanceof RequestHandlerInterface) {
            throw new LogicException('A montagem da aplicação precisa devolver um RequestHandlerInterface.');
        }

        return $aplicacao->handle(new ServerRequest($metodo, $caminho));
    }
}
