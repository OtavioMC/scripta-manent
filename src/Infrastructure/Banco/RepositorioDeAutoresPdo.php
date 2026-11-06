<?php

declare(strict_types=1);

namespace ScriptaManent\Infrastructure\Banco;

use PDO;
use PDOException;
use ScriptaManent\Application\Autor\RepositorioDeAutores;
use ScriptaManent\Application\Busca;
use ScriptaManent\Domain\Autor\Autor;
use ScriptaManent\Domain\ExclusaoBloqueada;
use UnexpectedValueException;

final readonly class RepositorioDeAutoresPdo implements RepositorioDeAutores
{
    public function __construct(private Conexao $conexao)
    {
    }

    public function listar(?Busca $busca = null): array
    {
        $sql = 'SELECT CodAu, Nome FROM Autor';
        $parametros = [];
        if ($busca !== null && !$busca->vazia()) {
            $sql .= ' WHERE Nome LIKE :nome';
            $parametros['nome'] = Like::contendo($busca->texto);
        }
        $consulta = $this->conexao->pdo()->prepare($sql . ' ORDER BY Nome, CodAu');
        $consulta->execute($parametros);

        return array_values(array_map($this->autorDaLinha(...), $consulta->fetchAll(PDO::FETCH_ASSOC)));
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
        try {
            $this->conexao->pdo()->prepare('DELETE FROM Autor WHERE CodAu = :codigo')->execute(['codigo' => $codigo]);
        } catch (PDOException $erro) {
            if (!ErroDoMysql::violouChaveEstrangeira($erro)) {
                throw $erro;
            }

            throw ExclusaoBloqueada::doAutor($this->buscar($codigo)->nome ?? '', $this->titulosDosLivros($codigo));
        }
    }

    /**
     * @return list<string>
     */
    private function titulosDosLivros(int $codigo): array
    {
        $consulta = $this->conexao->pdo()->prepare(
            'SELECT Livro.Titulo FROM Livro
             JOIN Livro_Autor ON Livro_Autor.Livro_Codl = Livro.Codl
             WHERE Livro_Autor.Autor_CodAu = :codigo
             ORDER BY Livro.Titulo',
        );
        $consulta->execute(['codigo' => $codigo]);

        return array_values(array_filter($consulta->fetchAll(PDO::FETCH_COLUMN), is_string(...)));
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
