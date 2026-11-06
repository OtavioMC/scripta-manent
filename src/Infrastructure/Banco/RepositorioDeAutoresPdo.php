<?php

declare(strict_types=1);

namespace ScriptaManent\Infrastructure\Banco;

use PDO;
use ScriptaManent\Application\Autor\RepositorioDeAutores;
use ScriptaManent\Domain\Autor\Autor;
use UnexpectedValueException;

final readonly class RepositorioDeAutoresPdo implements RepositorioDeAutores
{
    public function __construct(private Conexao $conexao)
    {
    }

    public function listar(): array
    {
        $consulta = $this->conexao->pdo()->query('SELECT CodAu, Nome FROM Autor ORDER BY Nome, CodAu');

        $linhas = $consulta === false ? [] : $consulta->fetchAll(PDO::FETCH_ASSOC);

        return array_values(array_map($this->autorDaLinha(...), $linhas));
    }

    public function buscar(int $codigo): ?Autor
    {
        $consulta = $this->conexao->pdo()->prepare('SELECT CodAu, Nome FROM Autor WHERE CodAu = :codigo');
        $consulta->execute(['codigo' => $codigo]);
        $linha = $consulta->fetch(PDO::FETCH_ASSOC);

        return $linha === false ? null : $this->autorDaLinha($linha);
    }

    public function inserir(Autor $autor): Autor
    {
        $pdo = $this->conexao->pdo();
        $pdo->prepare('INSERT INTO Autor (Nome) VALUES (:nome)')->execute(['nome' => $autor->nome]);

        return Autor::existente((int) $pdo->lastInsertId(), $autor->nome);
    }

    public function alterar(Autor $autor): void
    {
        $this->conexao->pdo()
            ->prepare('UPDATE Autor SET Nome = :nome WHERE CodAu = :codigo')
            ->execute(['nome' => $autor->nome, 'codigo' => $autor->codigo]);
    }

    public function excluir(int $codigo): void
    {
        $this->conexao->pdo()->prepare('DELETE FROM Autor WHERE CodAu = :codigo')->execute(['codigo' => $codigo]);
    }

    private function autorDaLinha(mixed $linha): Autor
    {
        $codigo = is_array($linha) ? $linha['CodAu'] ?? null : null;
        $nome = is_array($linha) ? $linha['Nome'] ?? null : null;
        if (!is_numeric($codigo) || !is_string($nome)) {
            throw new UnexpectedValueException('A consulta de autores não devolveu CodAu e Nome.');
        }

        return Autor::existente((int) $codigo, $nome);
    }
}
