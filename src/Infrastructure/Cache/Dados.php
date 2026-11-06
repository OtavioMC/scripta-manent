<?php

declare(strict_types=1);

namespace ScriptaManent\Infrastructure\Cache;

use UnexpectedValueException;

final class Dados
{
    /**
     * @return list<mixed>
     */
    public static function lista(mixed $valor): array
    {
        if (!is_array($valor) || !array_is_list($valor)) {
            throw new UnexpectedValueException('O cache não guardou uma lista.');
        }

        return $valor;
    }

    /**
     * @param array<mixed> $registro
     */
    public static function inteiro(array $registro, string $campo): int
    {
        $valor = $registro[$campo] ?? null;
        if (!is_int($valor)) {
            throw new UnexpectedValueException(sprintf('O cache não guardou o campo %s como número.', $campo));
        }

        return $valor;
    }

    /**
     * @param array<mixed> $registro
     */
    public static function inteiroOuNulo(array $registro, string $campo): ?int
    {
        return ($registro[$campo] ?? null) === null ? null : self::inteiro($registro, $campo);
    }

    /**
     * @param array<mixed> $registro
     */
    public static function texto(array $registro, string $campo): string
    {
        $valor = $registro[$campo] ?? null;
        if (!is_string($valor)) {
            throw new UnexpectedValueException(sprintf('O cache não guardou o campo %s como texto.', $campo));
        }

        return $valor;
    }

    /**
     * @param array<mixed> $registro
     */
    public static function textoOuNulo(array $registro, string $campo): ?string
    {
        return ($registro[$campo] ?? null) === null ? null : self::texto($registro, $campo);
    }

    /**
     * @param array<mixed> $registro
     * @return list<string>
     */
    public static function textos(array $registro, string $campo): array
    {
        return array_map(static function (mixed $item) use ($campo): string {
            if (!is_string($item)) {
                throw new UnexpectedValueException(sprintf('O campo %s não é uma lista de textos.', $campo));
            }

            return $item;
        }, self::lista($registro[$campo] ?? null));
    }

    /**
     * @param array<mixed> $registro
     * @return list<int>
     */
    public static function inteiros(array $registro, string $campo): array
    {
        return array_map(static function (mixed $item) use ($campo): int {
            if (!is_int($item)) {
                throw new UnexpectedValueException(sprintf('O campo %s não é uma lista de números.', $campo));
            }

            return $item;
        }, self::lista($registro[$campo] ?? null));
    }

    /**
     * @return array<mixed>
     */
    public static function registro(mixed $valor): array
    {
        if (!is_array($valor)) {
            throw new UnexpectedValueException('O cache não guardou um registro.');
        }

        return $valor;
    }
}
