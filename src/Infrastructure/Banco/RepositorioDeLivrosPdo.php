<?php

declare(strict_types=1);

namespace ScriptaManent\Infrastructure\Banco;

use PDO;
use PDOException;
use ScriptaManent\Application\Livro\LivroNaLista;
use ScriptaManent\Application\Livro\RepositorioDeLivros;
use ScriptaManent\Domain\ErroDeValidacao;
use ScriptaManent\Domain\Livro\Dinheiro;
use ScriptaManent\Domain\Livro\Livro;
use UnexpectedValueException;

final readonly class RepositorioDeLivrosPdo implements RepositorioDeLivros
{
    private const string SEPARADOR = "\n";

    public function __construct(private Conexao $conexao)
    {
    }

    public function listar(): array
    {
        $consulta = $this->conexao->pdo()->query(
            "SELECT Livro.Codl, Livro.Titulo, Livro.Editora, Livro.Edicao, Livro.AnoPublicacao, Livro.Valor,
                (SELECT GROUP_CONCAT(Autor.Nome ORDER BY Autor.Nome SEPARATOR '\\n')
                 FROM Livro_Autor JOIN Autor ON Autor.CodAu = Livro_Autor.Autor_CodAu
                 WHERE Livro_Autor.Livro_Codl = Livro.Codl) AS Autores,
                (SELECT GROUP_CONCAT(Livro_Autor.Autor_CodAu SEPARATOR '\\n')
                 FROM Livro_Autor WHERE Livro_Autor.Livro_Codl = Livro.Codl) AS CodigosDosAutores,
                (SELECT GROUP_CONCAT(Livro_Assunto.Assunto_codAs SEPARATOR '\\n')
                 FROM Livro_Assunto WHERE Livro_Assunto.Livro_Codl = Livro.Codl) AS CodigosDosAssuntos,
                (SELECT GROUP_CONCAT(Assunto.Descricao ORDER BY Assunto.Descricao SEPARATOR '\\n')
                 FROM Livro_Assunto JOIN Assunto ON Assunto.codAs = Livro_Assunto.Assunto_codAs
                 WHERE Livro_Assunto.Livro_Codl = Livro.Codl) AS Assuntos
             FROM Livro
             ORDER BY Livro.Titulo, Livro.Codl",
        );
        $linhas = $consulta === false ? [] : $consulta->fetchAll(PDO::FETCH_ASSOC);

        return array_values(array_map($this->livroNaLista(...), $linhas));
    }

    public function buscar(int $codigo): ?Livro
    {
        $consulta = $this->conexao->pdo()->prepare(
            'SELECT Codl, Titulo, Editora, Edicao, AnoPublicacao, Valor FROM Livro WHERE Codl = :codigo',
        );
        $consulta->execute(['codigo' => $codigo]);
        $linha = $consulta->fetch(PDO::FETCH_ASSOC);
        if (!is_array($linha)) {
            return null;
        }

        return Livro::criar(
            $codigo,
            $this->texto($linha, 'Titulo'),
            $this->texto($linha, 'Editora'),
            $this->texto($linha, 'Edicao'),
            $this->texto($linha, 'AnoPublicacao'),
            Dinheiro::doBanco($this->texto($linha, 'Valor'))->paraCampo(),
            $this->codigos(
                'SELECT Autor_CodAu FROM Livro_Autor WHERE Livro_Codl = :codigo ORDER BY Autor_CodAu',
                $codigo,
            ),
            $this->codigos(
                'SELECT Assunto_codAs FROM Livro_Assunto WHERE Livro_Codl = :codigo ORDER BY Assunto_codAs',
                $codigo,
            ),
        );
    }

    public function inserir(Livro $livro): Livro
    {
        return $this->conexao->emTransacao(function (PDO $pdo) use ($livro): Livro {
            $pdo->prepare(
                'INSERT INTO Livro (Titulo, Editora, Edicao, AnoPublicacao, Valor)
                 VALUES (:titulo, :editora, :edicao, :ano, :valor)',
            )->execute($this->dados($livro));
            $inserido = $livro->comCodigo((int) $pdo->lastInsertId());
            $this->gravarVinculos($pdo, $inserido);

            return $inserido;
        });
    }

    public function alterar(Livro $livro): void
    {
        $this->conexao->emTransacao(function (PDO $pdo) use ($livro): void {
            $pdo->prepare(
                'UPDATE Livro SET Titulo = :titulo, Editora = :editora, Edicao = :edicao,
                    AnoPublicacao = :ano, Valor = :valor
                 WHERE Codl = :codigo',
            )->execute([...$this->dados($livro), 'codigo' => $livro->codigo]);
            $this->apagarVinculos($pdo, (int) $livro->codigo);
            $this->gravarVinculos($pdo, $livro);
        });
    }

    public function excluir(int $codigo): void
    {
        $this->conexao->emTransacao(function (PDO $pdo) use ($codigo): void {
            $this->apagarVinculos($pdo, $codigo);
            $pdo->prepare('DELETE FROM Livro WHERE Codl = :codigo')->execute(['codigo' => $codigo]);
        });
    }

    /**
     * @return array<string, string|int>
     */
    private function dados(Livro $livro): array
    {
        return [
            'titulo' => $livro->titulo,
            'editora' => $livro->editora,
            'edicao' => $livro->edicao,
            'ano' => $livro->ano->valor,
            'valor' => $livro->valor->paraBanco(),
        ];
    }

    private function gravarVinculos(PDO $pdo, Livro $livro): void
    {
        $autor = $pdo->prepare('INSERT INTO Livro_Autor (Livro_Codl, Autor_CodAu) VALUES (:livro, :autor)');
        $assunto = $pdo->prepare('INSERT INTO Livro_Assunto (Livro_Codl, Assunto_codAs) VALUES (:livro, :assunto)');

        try {
            foreach ($livro->autores as $codigo) {
                $autor->execute(['livro' => $livro->codigo, 'autor' => $codigo]);
            }
            foreach ($livro->assuntos as $codigo) {
                $assunto->execute(['livro' => $livro->codigo, 'assunto' => $codigo]);
            }
        } catch (PDOException $erro) {
            if (!ErroDoMysql::referenciaInexistente($erro)) {
                throw $erro;
            }

            throw new ErroDeValidacao([
                'vinculos' => 'Um dos autores ou assuntos escolhidos não está mais cadastrado. '
                    . 'Confira as listas e salve de novo.',
            ]);
        }
    }

    private function apagarVinculos(PDO $pdo, int $codigo): void
    {
        $pdo->prepare('DELETE FROM Livro_Autor WHERE Livro_Codl = :codigo')->execute(['codigo' => $codigo]);
        $pdo->prepare('DELETE FROM Livro_Assunto WHERE Livro_Codl = :codigo')->execute(['codigo' => $codigo]);
    }

    /**
     * @return list<int>
     */
    private function codigos(string $sql, int $codigo): array
    {
        $consulta = $this->conexao->pdo()->prepare($sql);
        $consulta->execute(['codigo' => $codigo]);

        $codigos = array_filter($consulta->fetchAll(PDO::FETCH_COLUMN), is_numeric(...));

        return array_values(array_map(intval(...), $codigos));
    }

    private function livroNaLista(mixed $linha): LivroNaLista
    {
        if (!is_array($linha)) {
            throw new UnexpectedValueException('A consulta de livros devolveu uma linha inválida.');
        }

        return new LivroNaLista(
            (int) $this->texto($linha, 'Codl'),
            $this->texto($linha, 'Titulo'),
            $this->texto($linha, 'Editora'),
            (int) $this->texto($linha, 'Edicao'),
            $this->texto($linha, 'AnoPublicacao'),
            Dinheiro::doBanco($this->texto($linha, 'Valor')),
            $this->lista($linha['Autores'] ?? null),
            $this->lista($linha['Assuntos'] ?? null),
            array_map(intval(...), $this->lista($linha['CodigosDosAutores'] ?? null)),
            array_map(intval(...), $this->lista($linha['CodigosDosAssuntos'] ?? null)),
        );
    }

    /**
     * @param array<mixed> $linha
     */
    private function texto(array $linha, string $coluna): string
    {
        $valor = $linha[$coluna] ?? null;
        if (!is_string($valor) && !is_int($valor)) {
            throw new UnexpectedValueException(sprintf('A consulta de livros não devolveu a coluna %s.', $coluna));
        }

        return (string) $valor;
    }

    /**
     * @return list<string>
     */
    private function lista(mixed $valor): array
    {
        return is_string($valor) && $valor !== '' ? explode(self::SEPARADOR, $valor) : [];
    }
}
