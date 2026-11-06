<?php

declare(strict_types=1);

namespace ScriptaManent\Tests\Unidade\Http\Controller;

use DateTimeZone;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use ScriptaManent\Application\Busca;
use ScriptaManent\Application\Relatorio\FonteDoRelatorio;
use ScriptaManent\Application\Relatorio\GeradorDePdf;
use ScriptaManent\Application\Relatorio\PdfIndisponivel;
use ScriptaManent\Http\Controller\RelatorioPdfController;
use ScriptaManent\Http\Visao;
use ScriptaManent\Tests\Duble\LogEmMemoria;
use ScriptaManent\Tests\Duble\SessaoEmMemoria;
use ScriptaManent\Tests\Duble\TwigDeTeste;

final class RelatorioPdfControllerTest extends TestCase
{
    private LogEmMemoria $log;
    private SessaoEmMemoria $sessao;

    protected function setUp(): void
    {
        $this->log = new LogEmMemoria();
        $this->sessao = new SessaoEmMemoria();
    }

    public function testDevolveOPdfParaBaixar(): void
    {
        $resposta = $this->baixar($this->gerador('%PDF-1.4 conteúdo'));

        self::assertSame(200, $resposta->getStatusCode());
        self::assertSame('application/pdf', $resposta->getHeaderLine('Content-Type'));
        self::assertMatchesRegularExpression(
            '/^attachment; filename="relatorio-livros-por-autor-\d{4}-\d{2}-\d{2}\.pdf"$/',
            $resposta->getHeaderLine('Content-Disposition'),
        );
        self::assertSame('%PDF-1.4 conteúdo', (string) $resposta->getBody());
    }

    public function testComOGeradorForaDoArVoltaParaAPreviaComAvisoERegistraNoLog(): void
    {
        $resposta = $this->baixar($this->gerador(null));

        self::assertSame(303, $resposta->getStatusCode());
        self::assertSame('/relatorio', $resposta->getHeaderLine('Location'));
        self::assertIsString($this->sessao->obter('aviso'));
        self::assertStringContainsString(
            'O PDF do relatório não pôde ser gerado agora.',
            (string) $this->sessao->obter('aviso'),
        );
        self::assertSame('warning', $this->log->registros[0]['nivel'] ?? null);
    }

    private function baixar(GeradorDePdf $gerador): ResponseInterface
    {
        $twig = TwigDeTeste::criar();
        $fonte = new class implements FonteDoRelatorio {
            public function linhas(?Busca $busca = null): array
            {
                return [];
            }
        };

        return (new RelatorioPdfController(
            new Visao($twig, new Psr17Factory()),
            $fonte,
            $gerador,
            new Psr17Factory(),
            $this->sessao,
            $this->log,
            new DateTimeZone('America/Sao_Paulo'),
            ['aplicacao.css' => 'body {}'],
        ))->handle(new ServerRequest('GET', '/relatorio/pdf'));
    }

    private function gerador(?string $pdf): GeradorDePdf
    {
        return new class ($pdf) implements GeradorDePdf {
            public function __construct(private readonly ?string $pdf)
            {
            }

            public function ligado(): bool
            {
                return true;
            }

            public function gerar(string $html, array $arquivos): string
            {
                return $this->pdf ?? throw new PdfIndisponivel('fora do ar');
            }
        };
    }
}
