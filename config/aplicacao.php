<?php

declare(strict_types=1);

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Server\RequestHandlerInterface;
use ScriptaManent\Application\Autor\CadastroDeAutores;
use ScriptaManent\Http\Controller\Autor\ExcluirAutorController;
use ScriptaManent\Http\Controller\Autor\FormularioDeAutorController;
use ScriptaManent\Http\Controller\Autor\ListaDeAutoresController;
use ScriptaManent\Http\Controller\Autor\SalvarAutorController;
use ScriptaManent\Http\Controller\Autor\TelaDeFormularioDeAutor;
use ScriptaManent\Http\Controller\InicioController;
use ScriptaManent\Http\Controller\VersaoController;
use ScriptaManent\Http\PaginaDeErro;
use ScriptaManent\Http\Roteador;
use ScriptaManent\Http\SessaoNativa;
use ScriptaManent\Http\TratadorDeErros;
use ScriptaManent\Http\Visao;
use ScriptaManent\Infrastructure\Banco\Conexao;
use ScriptaManent\Infrastructure\Banco\RepositorioDeAutoresPdo;
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
    $formularioDeAutor = new TelaDeFormularioDeAutor($visao, $paginaDeErro);
    $abrirAutor = new FormularioDeAutorController($formularioDeAutor, $autores);
    $salvarAutor = new SalvarAutorController($formularioDeAutor, $autores, $sessao, $visao);
    $excluirAutor = new ExcluirAutorController($formularioDeAutor, $autores, $sessao, $visao);

    $roteador = new Roteador($paginaDeErro, [
        ['GET', '/', new InicioController($visao)],
        ['GET', '/versao', new VersaoController($respostas, $versao === false ? 'dev' : $versao)],
        ['GET', '/autores', new ListaDeAutoresController($visao, $autores, $sessao)],
        ['GET', '/autores/novo', $abrirAutor],
        ['POST', '/autores', $salvarAutor],
        ['GET', '/autores/{codigo:\d+}/editar', $abrirAutor],
        ['POST', '/autores/{codigo:\d+}', $salvarAutor],
        ['POST', '/autores/{codigo:\d+}/excluir', $excluirAutor],
    ]);

    return new TratadorDeErros($roteador, $paginaDeErro, new LogEmArquivo('php://stderr'));
})();
