<?php

declare(strict_types=1);

namespace ScriptaManent\Http\Controller;

use DateTimeImmutable;
use DateTimeZone;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ScriptaManent\Application\Relatorio\FonteDoRelatorio;
use ScriptaManent\Domain\Relatorio\Relatorio;
use ScriptaManent\Http\Visao;

final readonly class RelatorioController implements RequestHandlerInterface
{
    public function __construct(
        private Visao $visao,
        private FonteDoRelatorio $fonte,
        private DateTimeZone $fusoDeExibicao,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $agora = new DateTimeImmutable('now', $this->fusoDeExibicao);

        return $this->visao->responder('relatorio/tela.html.twig', [
            'relatorio' => Relatorio::montar($this->fonte->linhas()),
            'geradoEm' => sprintf('%s, %sh%s', $agora->format('d/m/Y'), $agora->format('G'), $agora->format('i')),
        ]);
    }
}
