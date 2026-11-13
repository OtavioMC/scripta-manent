<?php

declare(strict_types=1);

namespace ScriptaManent\Infrastructure\Banco;

use PDO;
use PDOException;
use ScriptaManent\Application\Assunto\RepositorioDeAssuntos;
use ScriptaManent\Application\Busca;
use ScriptaManent\Application\ListaPaginada;
use ScriptaManent\Application\Pagina;
use ScriptaManent\Domain\Assunto\Assunto;
use ScriptaManent\Domain\ExclusaoBloqueada;
use UnexpectedValueException;

final readonly class RepositorioDeAssuntosPdo implements RepositorioDeAssuntos
{
    public function __construct(private Conexao $conexao)
    {
    }

    public function listar(?Busca $busca = null): array
    {
        [$filtro, $parametros] = $this->filtroDaBusca($busca);
        $consulta = $this->conexao->pdo()->prepare(
            'SELECT codAs, Descricao FROM Assunto' . $filtro . ' ORDER BY Descricao, codAs',
        );
        $consulta->execute($parametros);

        return array_values(array_map($this->assuntoDaLinha(...), $consulta->fetchAll(PDO::FETCH_ASSOC)));
    }

    public function pagina(Busca $busca, Pagina $pagina): ListaPaginada
    {
        [$filtro, $parametros] = $this->filtroDaBusca($busca);
        $contagem = $this->conexao->pdo()->prepare('SELECT COUNT(*) FROM Assunto' . $filtro);
        $contagem->execute($parametros);
        $totalDeItens = (int) $contagem->fetchColumn();
        $pagina = $pagina->semPassarDaUltima($totalDeItens);

        $consulta = $this->conexao->pdo()->prepare(
            'SELECT codAs, Descricao FROM Assunto' . $filtro
                . ' ORDER BY Descricao, codAs LIMIT :quantidade OFFSET :pular',
        );
        foreach ($parametros as $nome => $valor) {
            $consulta->bindValue($nome, $valor);
        }
        $consulta->bindValue('quantidade', Pagina::ITENS, PDO::PARAM_INT);
        $consulta->bindValue('pular', $pagina->quantidadeDeItensAntes(), PDO::PARAM_INT);
        $consulta->execute();

        return new ListaPaginada(
            array_values(array_map($this->assuntoDaLinha(...), $consulta->fetchAll(PDO::FETCH_ASSOC))),
            $totalDeItens,
            $pagina,
        );
    }

    /**
     * @return array{string, array<string, string>}
     */
    private function filtroDaBusca(?Busca $busca): array
    {
        if ($busca === null || $busca->vazia()) {
            return ['', []];
        }

        return [' WHERE Descricao LIKE :descricao', ['descricao' => Like::contendo($busca->texto)]];
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
        try {
            $this->conexao->pdo()->prepare('DELETE FROM Assunto WHERE codAs = :codigo')->execute(['codigo' => $codigo]);
        } catch (PDOException $erro) {
            if (!ErroDoMysql::violouChaveEstrangeira($erro)) {
                throw $erro;
            }

            throw ExclusaoBloqueada::doAssunto(
                $this->buscar($codigo)->descricao ?? '',
                $this->titulosDosLivros($codigo),
            );
        }
    }

    /**
     * @return list<string>
     */
    private function titulosDosLivros(int $codigo): array
    {
        $consulta = $this->conexao->pdo()->prepare(
            'SELECT Livro.Titulo FROM Livro
             JOIN Livro_Assunto ON Livro_Assunto.Livro_Codl = Livro.Codl
             WHERE Livro_Assunto.Assunto_codAs = :codigo
             ORDER BY Livro.Titulo',
        );
        $consulta->execute(['codigo' => $codigo]);

        return array_values(array_filter($consulta->fetchAll(PDO::FETCH_COLUMN), is_string(...)));
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
