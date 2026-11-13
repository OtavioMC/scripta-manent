<?php

declare(strict_types=1);

namespace ScriptaManent\Infrastructure\Cache;

use ScriptaManent\Application\Assunto\RepositorioDeAssuntos;
use ScriptaManent\Application\Busca;
use ScriptaManent\Application\ListaPaginada;
use ScriptaManent\Application\Pagina;
use ScriptaManent\Domain\Assunto\Assunto;

final readonly class RepositorioDeAssuntosEmCache implements RepositorioDeAssuntos
{
    public function __construct(
        private RepositorioDeAssuntos $repositorio,
        private LeituraEmCache $cache,
    ) {
    }

    public function listar(?Busca $busca = null): array
    {
        return $this->cache->lembrar(
            'assuntos',
            $busca->texto ?? '',
            fn (): array => $this->repositorio->listar($busca),
            static fn (array $assuntos): array => array_map(
                static fn (Assunto $assunto): array => [
                    'codigo' => $assunto->codigo,
                    'descricao' => $assunto->descricao,
                ],
                $assuntos,
            ),
            static fn (mixed $dados): array => array_map(static function (mixed $item): Assunto {
                $registro = Dados::registro($item);

                return Assunto::existente(Dados::inteiro($registro, 'codigo'), Dados::texto($registro, 'descricao'));
            }, Dados::lista($dados)),
        );
    }

    public function pagina(Busca $busca, Pagina $pagina): ListaPaginada
    {
        return $this->repositorio->pagina($busca, $pagina);
    }

    public function buscar(int $codigo): ?Assunto
    {
        return $this->repositorio->buscar($codigo);
    }

    public function inserir(Assunto $assunto): Assunto
    {
        $inserido = $this->repositorio->inserir($assunto);
        $this->cache->invalidar('assuntos', 'livros', 'relatorio');

        return $inserido;
    }

    public function alterar(Assunto $assunto): void
    {
        $this->repositorio->alterar($assunto);
        $this->cache->invalidar('assuntos', 'livros', 'relatorio');
    }

    public function excluir(int $codigo): void
    {
        $this->repositorio->excluir($codigo);
        $this->cache->invalidar('assuntos', 'livros', 'relatorio');
    }
}
