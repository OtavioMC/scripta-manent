<?php

declare(strict_types=1);

namespace ScriptaManent\Tests\Unidade\Http\Acesso;

use PHPUnit\Framework\TestCase;
use ScriptaManent\Http\Acesso\SessaoDeAcesso;
use ScriptaManent\Http\Acesso\Situacao;
use ScriptaManent\Tests\Duble\SessaoEmMemoria;

final class SessaoDeAcessoTest extends TestCase
{
    private SessaoEmMemoria $sessao;
    private int $agora = 1_000_000;

    protected function setUp(): void
    {
        $this->sessao = new SessaoEmMemoria();
    }

    public function testSemEntrarASessaoEAnonima(): void
    {
        self::assertSame(Situacao::Anonimo, $this->acesso()->situacao());
    }

    public function testEntrarRenovaOIdentificadorEAutentica(): void
    {
        $this->acesso()->entrar('avaliador');

        self::assertSame(1, $this->sessao->renovacoes);
        self::assertSame(Situacao::Autenticado, $this->acesso()->situacao());
    }

    public function testUsoDentroDasDuasHorasMantemASessao(): void
    {
        $this->acesso()->entrar('avaliador');
        $this->agora += SessaoDeAcesso::INATIVIDADE_MAXIMA_EM_SEGUNDOS;

        self::assertSame(Situacao::Autenticado, $this->acesso()->situacao());
    }

    public function testCadaUsoRecomecaAContagemDeInatividade(): void
    {
        $this->acesso()->entrar('avaliador');
        $this->agora += 7000;
        $this->acesso()->situacao();
        $this->agora += 7000;

        self::assertSame(Situacao::Autenticado, $this->acesso()->situacao());
    }

    public function testConsultaDeVersoesNaoContaComoUso(): void
    {
        $this->acesso()->entrar('avaliador');
        $this->agora += 7000;
        $this->acesso()->situacao(contaComoUso: false);
        $this->agora += 7000;

        self::assertSame(Situacao::Expirado, $this->acesso()->situacao());
    }

    public function testMaisDeDuasHorasSemUsoExpiraASessao(): void
    {
        $this->acesso()->entrar('avaliador');
        $this->agora += SessaoDeAcesso::INATIVIDADE_MAXIMA_EM_SEGUNDOS + 1;

        self::assertSame(Situacao::Expirado, $this->acesso()->situacao());
        self::assertSame(Situacao::Anonimo, $this->acesso()->situacao());
    }

    public function testSairEncerraASessao(): void
    {
        $this->acesso()->entrar('avaliador');

        $this->acesso()->sair();

        self::assertTrue($this->sessao->encerrada);
        self::assertSame(Situacao::Anonimo, $this->acesso()->situacao());
    }

    private function acesso(): SessaoDeAcesso
    {
        return new SessaoDeAcesso($this->sessao, fn (): int => $this->agora);
    }
}
