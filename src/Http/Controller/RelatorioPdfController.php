<?php

declare(strict_types=1);

namespace ScriptaManent\Http\Controller;

use DateTimeImmutable;
use DateTimeZone;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use ScriptaManent\Application\Relatorio\FonteDoRelatorio;
use ScriptaManent\Application\Relatorio\GeradorDePdf;
use ScriptaManent\Application\Relatorio\PdfIndisponivel;
use ScriptaManent\Domain\Relatorio\Relatorio;
use ScriptaManent\Http\Sessao;
use ScriptaManent\Http\Visao;

final readonly class RelatorioPdfController implements RequestHandlerInterface
{
    /**
     * @param array<string, string> $estilos
     */
    public function __construct(
        private Visao $visao,
        private FonteDoRelatorio $fonte,
        private GeradorDePdf $gerador,
        private ResponseFactoryInterface $respostas,
        private Sessao $sessao,
        private LoggerInterface $log,
        private DateTimeZone $fusoDeExibicao,
        private array $estilos,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $agora = new DateTimeImmutable('now', $this->fusoDeExibicao);
        $html = $this->visao->renderizar('relatorio/pdf.html.twig', [
            'relatorio' => Relatorio::montar($this->fonte->linhas()),
            'geradoEm' => sprintf('%s, %sh%s', $agora->format('d/m/Y'), $agora->format('G'), $agora->format('i')),
        ]);

        try {
            $pdf = $this->gerador->gerar($html, $this->estilos);
        } catch (PdfIndisponivel $erro) {
            $this->log->warning('PDF do relatório não gerado', ['exception' => $erro]);
            $this->sessao->guardar(
                'aviso',
                'O PDF do relatório não pôde ser gerado agora. A prévia abaixo está atualizada; '
                . 'tente baixar o PDF de novo em alguns minutos.',
            );

            return $this->visao->redirecionar('/relatorio');
        }

        $resposta = $this->respostas->createResponse(200)
            ->withHeader('Content-Type', 'application/pdf')
            ->withHeader('Content-Disposition', sprintf(
                'attachment; filename="relatorio-livros-por-autor-%s.pdf"',
                $agora->format('Y-m-d'),
            ));
        $resposta->getBody()->write($pdf);

        return $resposta;
    }
}
