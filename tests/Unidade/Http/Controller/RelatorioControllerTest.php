<?php

declare(strict_types=1);

namespace ScriptaManent\Tests\Unidade\Http\Controller;

use DateTimeZone;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use ScriptaManent\Application\Relatorio\FonteDoRelatorio;
use ScriptaManent\Domain\Livro\Dinheiro;
use ScriptaManent\Domain\Relatorio\LinhaDoRelatorio;
use ScriptaManent\Http\Controller\RelatorioController;
use ScriptaManent\Http\Visao;
use ScriptaManent\Infrastructure\Pdf\GeradorDePdfGotenberg;
use ScriptaManent\Tests\Duble\SessaoEmMemoria;
use Twig\Environment;

final class RelatorioControllerTest extends TestCase
{
    public function testMostraOsGruposPorAutorOsLivrosSemAutorEAsCoautorias(): void
    {
        $pagina = $this->abrir([
            $this->linha(5, 'Neil Gaiman', 20, 'Belas Maldições', '59,90'),
            $this->linha(6, 'Terry Pratchett', 20, 'Belas Maldições', '59,90'),
            $this->linha(null, null, 30, 'As Mil e Uma Noites', '79,90'),
        ]);

        self::assertStringContainsString('>Neil Gaiman</h3>', $pagina);
        self::assertStringContainsString('>Livros sem autor</h3>', $pagina);
        self::assertStringContainsString('>Neil Gaiman e Terry Pratchett</h3>', $pagina);
        self::assertStringContainsString('R$ 139,80', $pagina);
    }

    public function testRelatorioSemLivrosDizComoComecar(): void
    {
        self::assertStringContainsString('Nenhum livro cadastrado.', $this->abrir([]));
    }

    /**
     * @param list<LinhaDoRelatorio> $linhas
     */
    private function abrir(array $linhas): string
    {
        $twig = require __DIR__ . '/../../../../config/twig.php';
        self::assertInstanceOf(Environment::class, $twig);
        $fonte = new class ($linhas) implements FonteDoRelatorio {
            /**
             * @param list<LinhaDoRelatorio> $linhas
             */
            public function __construct(private readonly array $linhas)
            {
            }

            public function linhas(): array
            {
                return $this->linhas;
            }
        };

        $controller = new RelatorioController(
            new Visao($twig, new Psr17Factory()),
            $fonte,
            new DateTimeZone('America/Sao_Paulo'),
            new GeradorDePdfGotenberg('', 5),
            new SessaoEmMemoria(),
        );

        return (string) $controller->handle(new ServerRequest('GET', '/relatorio'))->getBody();
    }

    private function linha(
        ?int $codigoDoAutor,
        ?string $autor,
        int $livro,
        string $titulo,
        string $valor,
    ): LinhaDoRelatorio {
        return new LinhaDoRelatorio(
            $codigoDoAutor,
            $autor,
            $livro,
            $titulo,
            'Editora',
            1,
            '2000',
            Dinheiro::deTexto($valor),
            'Fantasia',
        );
    }
}
