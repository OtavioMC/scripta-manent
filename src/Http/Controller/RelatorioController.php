<?php

declare(strict_types=1);

namespace ScriptaManent\Http\Controller;

use DateTimeImmutable;
use DateTimeZone;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ScriptaManent\Application\Relatorio\FonteDoRelatorio;
use ScriptaManent\Application\Relatorio\GeradorDePdf;
use ScriptaManent\Domain\Relatorio\Relatorio;
use ScriptaManent\Http\Sessao;
use ScriptaManent\Http\Visao;

final readonly class RelatorioController implements RequestHandlerInterface
{
    public function __construct(
        private Visao $visao,
        private FonteDoRelatorio $fonte,
        private DateTimeZone $fusoDeExibicao,
        private GeradorDePdf $gerador,
        private Sessao $sessao,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $agora = new DateTimeImmutable('now', $this->fusoDeExibicao);
        $aviso = $this->sessao->retirar('aviso');

        return $this->visao->responder('relatorio/tela.html.twig', [
            'relatorio' => Relatorio::montar($this->fonte->linhas()),
            'geradoEm' => sprintf('%s, %sh%s', $agora->format('d/m/Y'), $agora->format('G'), $agora->format('i')),
            'pdfLigado' => $this->gerador->ligado(),
            'aviso' => is_string($aviso) ? $aviso : null,
        ]);
    }
}
