<?php

declare(strict_types=1);

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

return (static function (): Environment {
    $twig = new Environment(new FilesystemLoader(__DIR__ . '/../templates'), [
        'autoescape' => 'html',
        'auto_reload' => true,
        'cache' => sys_get_temp_dir() . '/twig-' . PHP_SAPI,
        'strict_variables' => true,
    ]);
    $twig->addGlobal('menu', [
        [
            'endereco' => '/livros',
            'rotulo' => 'Livros',
            'chamada' => 'Título, editora, edição e valor de cada obra',
            'descricao' => 'Cadastre livros com editora, edição, ano, valor, autores e assuntos.',
            'imagem' => '/assets/img/carrossel/livros.jpg',
            'imagemGrande' => '/assets/img/carrossel/livros-1920.jpg',
            'textoAlternativo' => 'Estante de biblioteca com livros de lombadas coloridas',
        ],
        [
            'endereco' => '/autores',
            'rotulo' => 'Autores',
            'chamada' => 'Quem escreveu cada livro',
            'descricao' => 'Cadastre os autores que você vai vincular aos livros.',
            'imagem' => '/assets/img/carrossel/autores.jpg',
            'imagemGrande' => '/assets/img/carrossel/autores-1920.jpg',
            'textoAlternativo' => 'Máquina de escrever sobre uma mesa de madeira, iluminada por uma luminária',
        ],
        [
            'endereco' => '/assuntos',
            'rotulo' => 'Assuntos',
            'chamada' => 'Romance, conto, programação',
            'descricao' => 'Cadastre os assuntos usados para classificar os livros.',
            'imagem' => '/assets/img/carrossel/assuntos.jpg',
            'imagemGrande' => '/assets/img/carrossel/assuntos-1920.jpg',
            'textoAlternativo' => 'Pilha de livros ao lado de duas luminárias numa mesa de biblioteca',
        ],
        [
            'endereco' => '/relatorio',
            'rotulo' => 'Relatório',
            'chamada' => 'Livros agrupados por autor',
            'descricao' => 'Veja os livros agrupados por autor, com quantidade e valor total.',
            'imagem' => '/assets/img/carrossel/relatorio.jpg',
            'imagemGrande' => '/assets/img/carrossel/relatorio-1920.jpg',
            'textoAlternativo' => 'Teto com vitral do salão da Biblioteca Nacional, no Rio de Janeiro',
        ],
    ]);

    $twig->addGlobal('buscaMaxima', ScriptaManent\Application\Busca::MAXIMO);

    return $twig;
})();
