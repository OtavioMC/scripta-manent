<?php

declare(strict_types=1);

namespace ScriptaManent\Infrastructure\Banco;

use PDO;
use ScriptaManent\Application\Assunto\RepositorioDeAssuntos;
use ScriptaManent\Domain\Assunto\Assunto;
use UnexpectedValueException;

final readonly class RepositorioDeAssuntosPdo implements RepositorioDeAssuntos
{
    public function __construct(private Conexao $conexao)
    {
    }

    public function listar(): array
    {
        $consulta = $this->conexao->pdo()->query('SELECT codAs, Descricao FROM Assunto ORDER BY Descricao, codAs');
        $linhas = $consulta === false ? [] : $consulta->fetchAll(PDO::FETCH_ASSOC);

        return array_values(array_map($this->assuntoDaLinha(...), $linhas));
    }

    public function buscar(int $codigo): ?Assunto
    {
        $consulta = $this->conexao->pdo()->prepare('SELECT codAs, Descricao FROM Assunto WHERE codAs = :codigo');
        $consulta->execute(['codigo' => $codigo]);
        $linha = $consulta->fetch(PDO::FETCH_ASSOC);

        return $linha === false ? null : $this->assuntoDaLinha($linha);
    }

    public function inserir(Assunto $assunto): Assunto
    {
        $pdo = $this->conexao->pdo();
        $pdo->prepare('INSERT INTO Assunto (Descricao) VALUES (:descricao)')
            ->execute(['descricao' => $assunto->descricao]);

        return Assunto::existente((int) $pdo->lastInsertId(), $assunto->descricao);
    }

    public function alterar(Assunto $assunto): void
    {
        $this->conexao->pdo()
            ->prepare('UPDATE Assunto SET Descricao = :descricao WHERE codAs = :codigo')
            ->execute(['descricao' => $assunto->descricao, 'codigo' => $assunto->codigo]);
    }

    public function excluir(int $codigo): void
    {
        $this->conexao->pdo()->prepare('DELETE FROM Assunto WHERE codAs = :codigo')->execute(['codigo' => $codigo]);
    }

    private function assuntoDaLinha(mixed $linha): Assunto
    {
        $codigo = is_array($linha) ? $linha['codAs'] ?? null : null;
        $descricao = is_array($linha) ? $linha['Descricao'] ?? null : null;
        if (!is_numeric($codigo) || !is_string($descricao)) {
            throw new UnexpectedValueException('A consulta de assuntos não devolveu codAs e Descricao.');
        }

        return Assunto::existente((int) $codigo, $descricao);
    }
}
