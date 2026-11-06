<?php

declare(strict_types=1);

namespace ScriptaManent\Infrastructure\Banco;

use PDO;
use RuntimeException;
use ScriptaManent\Application\DadosDeExemplo\RestauradorDeDados;

final readonly class RestauradorDeDadosPdo implements RestauradorDeDados
{
    private const array TABELAS_NA_ORDEM_DE_EXCLUSAO = ['Livro_Autor', 'Livro_Assunto', 'Livro', 'Autor', 'Assunto'];

    public function __construct(
        private Conexao $conexao,
        private string $script,
    ) {
    }

    public function restaurar(): void
    {
        $insercoes = $this->insercoesDoScript();

        $this->conexao->emTransacao(static function (PDO $pdo) use ($insercoes): void {
            foreach (self::TABELAS_NA_ORDEM_DE_EXCLUSAO as $tabela) {
                $pdo->exec('DELETE FROM ' . $tabela);
            }
            foreach ($insercoes as $insercao) {
                $pdo->exec($insercao);
            }
        });
    }

    /**
     * @return list<string>
     */
    private function insercoesDoScript(): array
    {
        $conteudo = is_file($this->script) ? file_get_contents($this->script) : false;
        if ($conteudo === false) {
            throw new RuntimeException(sprintf('O script de dados de exemplo %s não pôde ser lido.', $this->script));
        }

        $comandos = array_map(trim(...), preg_split('/;\s*$/m', $conteudo) ?: []);
        $insercoes = array_values(array_filter($comandos, static fn (string $comando): bool => str_starts_with(
            $comando,
            'INSERT INTO ',
        )));
        if ($insercoes === []) {
            throw new RuntimeException(sprintf('O script %s não tem nenhum INSERT.', $this->script));
        }

        return $insercoes;
    }
}
