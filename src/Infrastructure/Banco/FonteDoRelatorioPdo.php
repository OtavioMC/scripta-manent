<?php

declare(strict_types=1);

namespace ScriptaManent\Infrastructure\Banco;

use PDO;
use ScriptaManent\Application\Busca;
use ScriptaManent\Application\Relatorio\FonteDoRelatorio;
use ScriptaManent\Domain\Livro\Dinheiro;
use ScriptaManent\Domain\Relatorio\LinhaDoRelatorio;
use UnexpectedValueException;

final readonly class FonteDoRelatorioPdo implements FonteDoRelatorio
{
    public function __construct(private Conexao $conexao)
    {
    }

    public function linhas(?Busca $busca = null): array
    {
        $filtro = '';
        $parametros = [];
        if ($busca !== null && !$busca->vazia()) {
            $filtro = ' WHERE Codl IN (SELECT Codl FROM vw_livros_por_autor
                WHERE Titulo LIKE :titulo OR Autor LIKE :autor OR Editora LIKE :editora OR Assuntos LIKE :assuntos)';
            $termo = Like::contendo($busca->texto);
            $parametros = ['titulo' => $termo, 'autor' => $termo, 'editora' => $termo, 'assuntos' => $termo];
        }
        $consulta = $this->conexao->pdo()->prepare(
            'SELECT CodAu, Autor, Codl, Titulo, Editora, Edicao, AnoPublicacao, Valor, Assuntos
             FROM vw_livros_por_autor' . $filtro . '
             ORDER BY Autor IS NULL, Autor, Titulo, Codl',
        );
        $consulta->execute($parametros);

        return array_values(array_map($this->linhaDoRelatorio(...), $consulta->fetchAll(PDO::FETCH_ASSOC)));
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
