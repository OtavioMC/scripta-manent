<?php

declare(strict_types=1);

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Server\RequestHandlerInterface;
use ScriptaManent\Application\Assunto\CadastroDeAssuntos;
use ScriptaManent\Application\Autor\CadastroDeAutores;
use ScriptaManent\Application\Livro\CadastroDeLivros;
use ScriptaManent\Http\Apresentador\FormularioDeAssuntoApresentador;
use ScriptaManent\Http\Apresentador\FormularioDeAutorApresentador;
use ScriptaManent\Http\Apresentador\FormularioDeLivroApresentador;
use ScriptaManent\Http\Controller\Assunto\ExcluirAssuntoController;
use ScriptaManent\Http\Controller\Assunto\FormularioDeAssuntoController;
use ScriptaManent\Http\Controller\Assunto\ListaDeAssuntosController;
use ScriptaManent\Http\Controller\Assunto\SalvarAssuntoController;
use ScriptaManent\Http\Controller\Autor\ExcluirAutorController;
use ScriptaManent\Http\Controller\Autor\FormularioDeAutorController;
use ScriptaManent\Http\Controller\Autor\ListaDeAutoresController;
use ScriptaManent\Http\Controller\Autor\SalvarAutorController;
use ScriptaManent\Http\Controller\InicioController;
use ScriptaManent\Http\Controller\Livro\ExcluirLivroController;
use ScriptaManent\Http\Controller\Livro\FormularioDeLivroController;
use ScriptaManent\Http\Controller\Livro\ListaDeLivrosController;
use ScriptaManent\Http\Controller\Livro\SalvarLivroController;
use ScriptaManent\Http\Controller\VersaoController;
use ScriptaManent\Http\PaginaDeErro;
use ScriptaManent\Http\Roteador;
use ScriptaManent\Http\SessaoNativa;
use ScriptaManent\Http\TratadorDeErros;
use ScriptaManent\Http\Visao;
use ScriptaManent\Infrastructure\Banco\Conexao;
use ScriptaManent\Infrastructure\Banco\RepositorioDeAssuntosPdo;
use ScriptaManent\Infrastructure\Banco\RepositorioDeAutoresPdo;
use ScriptaManent\Infrastructure\Banco\RepositorioDeLivrosPdo;
use ScriptaManent\Infrastructure\Log\LogEmArquivo;
use Twig\Environment;

return (static function (): RequestHandlerInterface {
    $variavel = static function (string $nome): string {
        $valor = getenv($nome);
        if ($valor === false || $valor === '') {
            throw new RuntimeException(sprintf('A variável de ambiente %s não está definida.', $nome));
        }

        return $valor;
    };
    $versao = getenv('VERSAO');
    $respostas = new Psr17Factory();
    $conexao = new Conexao(static fn (): PDO => new PDO(
        sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $variavel('BANCO_HOST'), $variavel('BANCO_NOME')),
        $variavel('BANCO_USUARIO'),
        $variavel('BANCO_SENHA'),
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false],
    ));
    $sessao = new SessaoNativa();

    $twig = require __DIR__ . '/twig.php';
    if (!$twig instanceof Environment) {
        throw new LogicException('config/twig.php precisa devolver um Twig\Environment.');
    }
    $visao = new Visao($twig, $respostas);
    $paginaDeErro = new PaginaDeErro($visao);
    $autores = new CadastroDeAutores(new RepositorioDeAutoresPdo($conexao));
    $formularioDeAutor = new FormularioDeAutorApresentador($visao, $paginaDeErro);
    $abrirAutor = new FormularioDeAutorController($formularioDeAutor, $autores);
    $salvarAutor = new SalvarAutorController($formularioDeAutor, $autores, $sessao, $visao);
    $excluirAutor = new ExcluirAutorController($formularioDeAutor, $autores, $sessao, $visao);
    $assuntos = new CadastroDeAssuntos(new RepositorioDeAssuntosPdo($conexao));
    $formularioDeAssunto = new FormularioDeAssuntoApresentador($visao, $paginaDeErro);
    $abrirAssunto = new FormularioDeAssuntoController($formularioDeAssunto, $assuntos);
    $salvarAssunto = new SalvarAssuntoController($formularioDeAssunto, $assuntos, $sessao, $visao);
    $excluirAssunto = new ExcluirAssuntoController($formularioDeAssunto, $assuntos, $sessao, $visao);
    $livros = new CadastroDeLivros(new RepositorioDeLivrosPdo($conexao));
    $formularioDeLivro = new FormularioDeLivroApresentador($visao, $paginaDeErro, $autores, $assuntos);
    $abrirLivro = new FormularioDeLivroController($formularioDeLivro, $livros);
    $salvarLivro = new SalvarLivroController($formularioDeLivro, $livros, $sessao, $visao);
    $excluirLivro = new ExcluirLivroController($formularioDeLivro, $livros, $sessao, $visao);

    $roteador = new Roteador($paginaDeErro, [
        ['GET', '/', new InicioController($visao)],
        ['GET', '/versao', new VersaoController($respostas, $versao === false ? 'dev' : $versao)],
        ['GET', '/livros', new ListaDeLivrosController($visao, $livros, $sessao, $formularioDeLivro)],
        ['GET', '/livros/novo', $abrirLivro],
        ['POST', '/livros', $salvarLivro],
        ['GET', '/livros/{codigo:\d+}/editar', $abrirLivro],
        ['POST', '/livros/{codigo:\d+}', $salvarLivro],
        ['POST', '/livros/{codigo:\d+}/excluir', $excluirLivro],
        ['GET', '/autores', new ListaDeAutoresController($visao, $autores, $sessao, $formularioDeAutor)],
        ['GET', '/autores/novo', $abrirAutor],
        ['POST', '/autores', $salvarAutor],
        ['GET', '/autores/{codigo:\d+}/editar', $abrirAutor],
        ['POST', '/autores/{codigo:\d+}', $salvarAutor],
        ['POST', '/autores/{codigo:\d+}/excluir', $excluirAutor],
        ['GET', '/assuntos', new ListaDeAssuntosController($visao, $assuntos, $sessao, $formularioDeAssunto)],
        ['GET', '/assuntos/novo', $abrirAssunto],
        ['POST', '/assuntos', $salvarAssunto],
        ['GET', '/assuntos/{codigo:\d+}/editar', $abrirAssunto],
        ['POST', '/assuntos/{codigo:\d+}', $salvarAssunto],
        ['POST', '/assuntos/{codigo:\d+}/excluir', $excluirAssunto],
    ]);

    return new TratadorDeErros($roteador, $paginaDeErro, new LogEmArquivo('php://stderr'));
})();
