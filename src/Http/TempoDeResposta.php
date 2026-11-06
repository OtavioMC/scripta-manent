<?php

declare(strict_types=1);

namespace ScriptaManent\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ScriptaManent\Application\Cache\Medicoes;

final readonly class TempoDeResposta implements RequestHandlerInterface
{
    private const array DESCRICOES = ['cache' => 'Redis', 'banco' => 'MySQL'];

    public function __construct(
        private RequestHandlerInterface $aplicacao,
        private Medicoes $medicoes,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $inicio = hrtime(true);
        $resposta = $this->aplicacao->handle($request);
        $total = (hrtime(true) - $inicio) / 1_000_000;

        $metricas = [];
        $origens = [];
        foreach ($this->medicoes->leituras() as $indice => $leitura) {
            $origens[] = $leitura['origem'];
            $metricas[] = sprintf(
                '%s%d;desc="%s";dur=%.2f',
                $leitura['origem'],
                $indice + 1,
                self::DESCRICOES[$leitura['origem']] ?? $leitura['origem'],
                $leitura['milissegundos'],
            );
        }
        $metricas[] = sprintf('total;dur=%.2f', $total);
        $resposta = $resposta->withHeader('Server-Timing', implode(', ', $metricas));

        if ($origens === []) {
            return $resposta;
        }

        return $resposta->withHeader('X-Cache', in_array('banco', $origens, true) ? 'MISS' : 'HIT');
    }
}
