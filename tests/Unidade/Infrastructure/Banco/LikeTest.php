<?php

declare(strict_types=1);

namespace ScriptaManent\Tests\Unidade\Infrastructure\Banco;

use PHPUnit\Framework\TestCase;
use ScriptaManent\Infrastructure\Banco\Like;

final class LikeTest extends TestCase
{
    public function testProcuraOTextoEmQualquerPosicao(): void
    {
        self::assertSame('%Gaiman%', Like::contendo('Gaiman'));
    }

    public function testCuringasDigitadosViramTextoComum(): void
    {
        self::assertSame('%100\\%\\_novo%', Like::contendo('100%_novo'));
    }

    public function testBarraInvertidaDigitadaViraTextoComum(): void
    {
        self::assertSame('%a\\\\b%', Like::contendo('a\\b'));
    }
}
