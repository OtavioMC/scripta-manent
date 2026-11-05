<?php

declare(strict_types=1);

namespace ScriptaManent\Http;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use UnexpectedValueException;

use function FastRoute\simpleDispatcher;

final readonly class Roteador implements RequestHandlerInterface
{
    private Dispatcher $despachante;

    /**
     * @param list<array{string, string, RequestHandlerInterface}> $rotas
     */
    public function __construct(
        private ResponseFactoryInterface $respostas,
        array $rotas,
    ) {
        $this->despachante = simpleDispatcher(static function (RouteCollector $colecao) use ($rotas): void {
            foreach ($rotas as [$metodo, $caminho, $handler]) {
                $colecao->addRoute($metodo, $caminho, $handler);
            }
        });
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $metodo = $request->getMethod();
        $caminho = $request->getUri()->getPath();
        $resultado = $this->despachante->dispatch($metodo, $caminho);

        return match ($resultado[0]) {
            Dispatcher::FOUND => $this->handlerEncontrado($resultado[1])
                ->handle($this->comParametros($request, $resultado[2])),
            Dispatcher::METHOD_NOT_ALLOWED => $this->metodoNaoPermitido($metodo, $caminho, $resultado[1]),
            default => $this->erro(404, sprintf('Nenhuma página responde no endereço %s.', $caminho)),
        };
    }

    private function handlerEncontrado(mixed $handler): RequestHandlerInterface
    {
        if (!$handler instanceof RequestHandlerInterface) {
            throw new UnexpectedValueException('A rota encontrada não aponta para um RequestHandlerInterface.');
        }

        return $handler;
    }

    private function comParametros(ServerRequestInterface $request, mixed $parametros): ServerRequestInterface
    {
        if (!is_array($parametros)) {
            throw new UnexpectedValueException('O fast-route não informou os parâmetros do caminho.');
        }

        foreach ($parametros as $nome => $valor) {
            $request = $request->withAttribute((string) $nome, $valor);
        }

        return $request;
    }

    private function metodoNaoPermitido(string $metodo, string $caminho, mixed $aceitos): ResponseInterface
    {
        if (!is_array($aceitos)) {
            throw new UnexpectedValueException('O fast-route não informou os métodos aceitos pela rota.');
        }

        $lista = implode(', ', array_filter($aceitos, is_string(...)));

        return $this->erro(405, sprintf('O endereço %s não aceita %s. Métodos aceitos: %s.', $caminho, $metodo, $lista))
            ->withHeader('Allow', $lista);
    }

    private function erro(int $status, string $mensagem): ResponseInterface
    {
        $resposta = $this->respostas->createResponse($status)
            ->withHeader('Content-Type', 'text/plain; charset=utf-8');
        $resposta->getBody()->write($mensagem);

        return $resposta;
    }
}
