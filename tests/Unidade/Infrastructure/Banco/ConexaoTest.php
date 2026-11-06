<?php

declare(strict_types=1);

namespace ScriptaManent\Tests\Unidade\Infrastructure\Banco;

use PDO;
use PDOException;
use PHPUnit\Framework\TestCase;
use ScriptaManent\Application\BancoIndisponivel;
use ScriptaManent\Infrastructure\Banco\Conexao;

final class ConexaoTest extends TestCase
{
    public function testFalhaAoConectarViraBancoIndisponivel(): void
    {
        $conexao = new Conexao(static function (): PDO {
            throw new PDOException('SQLSTATE[HY000] [2002] Connection refused');
        });

        $this->expectException(BancoIndisponivel::class);

        $conexao->pdo();
    }
}
