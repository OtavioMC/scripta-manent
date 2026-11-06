<?php

declare(strict_types=1);

namespace ScriptaManent\Infrastructure\Banco;

use PDO;
use ScriptaManent\Application\Relatorio\FonteDoRelatorio;
use ScriptaManent\Domain\Livro\Dinheiro;
use ScriptaManent\Domain\Relatorio\LinhaDoRelatorio;
use UnexpectedValueException;

final readonly class FonteDoRelatorioPdo implements FonteDoRelatorio
{
    public function __construct(private Conexao $conexao)
    {
    }

    public function linhas(): array
    {
        $consulta = $this->conexao->pdo()->query(
            'SELECT CodAu, Autor, Codl, Titulo, Editora, Edicao, AnoPublicacao, Valor, Assuntos
             FROM vw_livros_por_autor
             ORDER BY Autor IS NULL, Autor, Titulo, Codl',
        );
        $linhas = $consulta === false ? [] : $consulta->fetchAll(PDO::FETCH_ASSOC);

        return array_values(array_map($this->linhaDoRelatorio(...), $linhas));
    }

    private function linhaDoRelatorio(mixed $linha): LinhaDoRelatorio
    {
        if (!is_array($linha)) {
            throw new UnexpectedValueException('A view do relatório devolveu uma linha inválida.');
        }

        $autor = $linha['Autor'] ?? null;
        $codigoDoAutor = $linha['CodAu'] ?? null;
        $assuntos = $linha['Assuntos'] ?? null;

        return new LinhaDoRelatorio(
            is_numeric($codigoDoAutor) ? (int) $codigoDoAutor : null,
            is_string($autor) ? $autor : null,
            (int) $this->obrigatorio($linha, 'Codl'),
            $this->obrigatorio($linha, 'Titulo'),
            $this->obrigatorio($linha, 'Editora'),
            (int) $this->obrigatorio($linha, 'Edicao'),
            $this->obrigatorio($linha, 'AnoPublicacao'),
            Dinheiro::doBanco($this->obrigatorio($linha, 'Valor')),
            is_string($assuntos) ? $assuntos : '',
        );
    }

    /**
     * @param array<mixed> $linha
     */
    private function obrigatorio(array $linha, string $coluna): string
    {
        $valor = $linha[$coluna] ?? null;
        if (!is_string($valor) && !is_int($valor)) {
            throw new UnexpectedValueException(sprintf('A view do relatório não devolveu a coluna %s.', $coluna));
        }

        return (string) $valor;
    }
}
