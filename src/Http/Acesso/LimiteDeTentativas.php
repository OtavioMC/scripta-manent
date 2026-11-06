<?php

declare(strict_types=1);

namespace ScriptaManent\Http\Acesso;

use Closure;
use RuntimeException;

final readonly class LimiteDeTentativas
{
    public const int FALHAS_LIVRES = 5;
    private const int ESPERA_INICIAL_EM_SEGUNDOS = 30;
    private const int ESPERA_MAXIMA_EM_SEGUNDOS = 900;

    /**
     * @param Closure(): int $agora
     */
    public function __construct(
        private string $pasta,
        private Closure $agora,
    ) {
    }

    public function segundosDeEspera(string $origem): int
    {
        $bloqueadoAte = $this->ler($origem)['bloqueadoAte'];

        return max(0, $bloqueadoAte - ($this->agora)());
    }

    public function registrarFalha(string $origem): void
    {
        $registro = $this->ler($origem);
        $falhas = $registro['falhas'] + 1;
        $bloqueadoAte = $registro['bloqueadoAte'];
        if ($falhas >= self::FALHAS_LIVRES) {
            $espera = self::ESPERA_INICIAL_EM_SEGUNDOS * 2 ** ($falhas - self::FALHAS_LIVRES);
            $bloqueadoAte = ($this->agora)() + min($espera, self::ESPERA_MAXIMA_EM_SEGUNDOS);
        }

        $this->gravar($origem, ['falhas' => $falhas, 'bloqueadoAte' => $bloqueadoAte]);
    }

    public function limpar(string $origem): void
    {
        $arquivo = $this->arquivo($origem);
        if (is_file($arquivo)) {
            unlink($arquivo);
        }
    }

    /**
     * @return array{falhas: int, bloqueadoAte: int}
     */
    private function ler(string $origem): array
    {
        $arquivo = $this->arquivo($origem);
        $conteudo = is_file($arquivo) ? file_get_contents($arquivo) : false;
        $dados = is_string($conteudo) ? json_decode($conteudo, true) : null;
        $falhas = is_array($dados) ? $dados['falhas'] ?? 0 : 0;
        $bloqueadoAte = is_array($dados) ? $dados['bloqueadoAte'] ?? 0 : 0;

        return ['falhas' => is_int($falhas) ? $falhas : 0, 'bloqueadoAte' => is_int($bloqueadoAte) ? $bloqueadoAte : 0];
    }

    /**
     * @param array{falhas: int, bloqueadoAte: int} $registro
     */
    private function gravar(string $origem, array $registro): void
    {
        if (!is_dir($this->pasta) && !mkdir($this->pasta, 0700, true) && !is_dir($this->pasta)) {
            throw new RuntimeException(sprintf('A pasta %s não pôde ser criada.', $this->pasta));
        }

        file_put_contents($this->arquivo($origem), json_encode($registro), LOCK_EX);
    }

    private function arquivo(string $origem): string
    {
        return $this->pasta . '/' . hash('sha256', $origem) . '.json';
    }
}
