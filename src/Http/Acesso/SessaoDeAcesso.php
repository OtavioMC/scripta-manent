<?php

declare(strict_types=1);

namespace ScriptaManent\Http\Acesso;

use Closure;
use ScriptaManent\Http\Sessao;

final readonly class SessaoDeAcesso
{
    public const int INATIVIDADE_MAXIMA_EM_SEGUNDOS = 7200;
    private const string CHAVE = 'acesso';

    /**
     * @param Closure(): int $agora
     */
    public function __construct(
        private Sessao $sessao,
        private Closure $agora,
    ) {
    }

    public function entrar(string $usuario): void
    {
        $this->sessao->renovarIdentificador();
        $this->sessao->guardar(self::CHAVE, ['usuario' => $usuario, 'ultimoUso' => ($this->agora)()]);
    }

    public function sair(): void
    {
        $this->sessao->encerrar();
    }

    public function situacao(bool $contaComoUso = true): Situacao
    {
        $acesso = $this->sessao->obter(self::CHAVE);
        $usuario = is_array($acesso) ? $acesso['usuario'] ?? null : null;
        $ultimoUso = is_array($acesso) ? $acesso['ultimoUso'] ?? null : null;
        if (!is_string($usuario) || !is_int($ultimoUso)) {
            return Situacao::Anonimo;
        }

        $agora = ($this->agora)();
        if ($agora - $ultimoUso > self::INATIVIDADE_MAXIMA_EM_SEGUNDOS) {
            $this->sessao->retirar(self::CHAVE);

            return Situacao::Expirado;
        }

        $this->sessao->guardar(self::CHAVE, ['usuario' => $usuario, 'ultimoUso' => $agora, 'uso' => $contaComoUso]);

        return Situacao::Autenticado;
    }
}
