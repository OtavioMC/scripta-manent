<?php

declare(strict_types=1);

use Nyholm\Psr7\Factory\Psr17Factory;
use Predis\Client;
use Psr\Http\Server\RequestHandlerInterface;
use ScriptaManent\Application\Assunto\CadastroDeAssuntos;
use ScriptaManent\Application\Autor\CadastroDeAutores;
use ScriptaManent\Application\Cache\Medicoes;
use ScriptaManent\Application\Cache\SemVersoes;
use ScriptaManent\Application\Livro\CadastroDeLivros;
use ScriptaManent\Http\Acesso\Credencial;
use ScriptaManent\Http\Acesso\ExigeLogin;
use ScriptaManent\Http\Acesso\LimiteDeTentativas;
use ScriptaManent\Http\Acesso\SessaoDeAcesso;
use ScriptaManent\Http\Apresentador\FormularioDeAssuntoApresentador;
use ScriptaManent\Http\Apresentador\FormularioDeAutorApresentador;
use ScriptaManent\Http\Apresentador\FormularioDeLivroApresentador;
use ScriptaManent\Http\Controller\Acesso\EntrarController;
use ScriptaManent\Http\Controller\Acesso\SairController;
use ScriptaManent\Http\Controller\Acesso\TelaDeLoginController;
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
use ScriptaManent\Http\Controller\RelatorioController;
use ScriptaManent\Http\Controller\RelatorioPdfController;
use ScriptaManent\Http\Controller\RestaurarDadosDeExemploController;
use ScriptaManent\Http\Controller\VersaoController;
use ScriptaManent\Http\Controller\VersoesController;
use ScriptaManent\Http\PaginaDeErro;
use ScriptaManent\Http\Roteador;
use ScriptaManent\Http\Sessao;
use ScriptaManent\Http\TempoDeResposta;
use ScriptaManent\Http\TokenCsrf;
use ScriptaManent\Http\TratadorDeErros;
use ScriptaManent\Http\VerificadorDeCsrf;
use ScriptaManent\Http\Visao;
use ScriptaManent\Infrastructure\Banco\Conexao;
use ScriptaManent\Infrastructure\Banco\FonteDoRelatorioPdo;
use ScriptaManent\Infrastructure\Banco\RepositorioDeAssuntosPdo;
use ScriptaManent\Infrastructure\Banco\RepositorioDeAutoresPdo;
use ScriptaManent\Infrastructure\Banco\RepositorioDeLivrosPdo;
use ScriptaManent\Infrastructure\Banco\RestauradorDeDadosPdo;
use ScriptaManent\Infrastructure\Cache\ArmazemRedis;
use ScriptaManent\Infrastructure\Cache\FonteDoRelatorioEmCache;
use ScriptaManent\Infrastructure\Cache\LeituraEmCache;
use ScriptaManent\Infrastructure\Cache\RepositorioDeAssuntosEmCache;
use ScriptaManent\Infrastructure\Cache\RepositorioDeAutoresEmCache;
use ScriptaManent\Infrastructure\Cache\RepositorioDeLivrosEmCache;
use ScriptaManent\Infrastructure\Cache\RestauradorDeDadosEmCache;
use ScriptaManent\Infrastructure\Log\LogEmArquivo;
use ScriptaManent\Infrastructure\Pdf\GeradorDePdfGotenberg;
use Twig\Environment;
use Twig\TwigFunction;

return static function (Sessao $sessao): RequestHandlerInterface {
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
    $relogio = static fn (): int => time();
    $acesso = new SessaoDeAcesso($sessao, $relogio);
    $credencial = getenv('NGINX_HTPASSWD');
    $log = new LogEmArquivo('php://stderr');
    $medicoes = new Medicoes();
    $redis = getenv('REDIS_URL');
    $cache = $redis === false || $redis === '' ? null : new LeituraEmCache(
        new ArmazemRedis(new Client($redis . '?timeout=0.3&read_write_timeout=0.3')),
        $medicoes,
        $log,
        300,
    );
    $versoes = $cache ?? new SemVersoes();
    $gotenberg = getenv('GOTENBERG_URL');
    $geradorDePdf = new GeradorDePdfGotenberg($gotenberg === false ? '' : $gotenberg, 20);
    $fusoDeExibicao = new DateTimeZone('America/Sao_Paulo');
    $lerArquivo = static function (string $caminho): string {
        $conteudo = file_get_contents($caminho);
        if ($conteudo === false) {
            throw new RuntimeException(sprintf('O arquivo %s não pôde ser lido.', $caminho));
        }

        return $conteudo;
    };

    $twig = require __DIR__ . '/twig.php';
    if (!$twig instanceof Environment) {
        throw new LogicException('config/twig.php precisa devolver um Twig\Environment.');
    }
    $tokenCsrf = new TokenCsrf($sessao);
    $twig->addGlobal('csrf', $tokenCsrf);
    $twig->addFunction(new TwigFunction(
        'versaoDe',
        static fn (string $colecao): ?int => $versoes->atuais()[$colecao] ?? null,
    ));
    $visao = new Visao($twig, $respostas);
    $paginaDeErro = new PaginaDeErro($visao);
    $protegido = static fn (RequestHandlerInterface $acao): RequestHandlerInterface => new VerificadorDeCsrf(
        $acao,
        $tokenCsrf,
        $paginaDeErro,
        $log,
    );
    $fonteDoRelatorio = new FonteDoRelatorioPdo($conexao);
    $repositorioDeAutores = new RepositorioDeAutoresPdo($conexao);
    $repositorioDeAssuntos = new RepositorioDeAssuntosPdo($conexao);
    $repositorioDeLivros = new RepositorioDeLivrosPdo($conexao);
    $restaurador = new RestauradorDeDadosPdo($conexao, __DIR__ . '/../banco/003-dados-exemplo.sql');
    if ($cache !== null) {
        $fonteDoRelatorio = new FonteDoRelatorioEmCache($fonteDoRelatorio, $cache);
        $repositorioDeAutores = new RepositorioDeAutoresEmCache($repositorioDeAutores, $cache);
        $repositorioDeAssuntos = new RepositorioDeAssuntosEmCache($repositorioDeAssuntos, $cache);
        $repositorioDeLivros = new RepositorioDeLivrosEmCache($repositorioDeLivros, $cache);
        $restaurador = new RestauradorDeDadosEmCache($restaurador, $cache);
    }
    $autores = new CadastroDeAutores($repositorioDeAutores);
    $formularioDeAutor = new FormularioDeAutorApresentador($visao, $paginaDeErro);
    $abrirAutor = new FormularioDeAutorController($formularioDeAutor, $autores);
    $salvarAutor = new SalvarAutorController($formularioDeAutor, $autores, $sessao, $visao);
    $excluirAutor = new ExcluirAutorController($formularioDeAutor, $autores, $sessao, $visao);
    $assuntos = new CadastroDeAssuntos($repositorioDeAssuntos);
    $formularioDeAssunto = new FormularioDeAssuntoApresentador($visao, $paginaDeErro);
    $abrirAssunto = new FormularioDeAssuntoController($formularioDeAssunto, $assuntos);
    $salvarAssunto = new SalvarAssuntoController($formularioDeAssunto, $assuntos, $sessao, $visao);
    $excluirAssunto = new ExcluirAssuntoController($formularioDeAssunto, $assuntos, $sessao, $visao);
    $livros = new CadastroDeLivros($repositorioDeLivros);
    $formularioDeLivro = new FormularioDeLivroApresentador($visao, $paginaDeErro, $autores, $assuntos);
    $abrirLivro = new FormularioDeLivroController($formularioDeLivro, $livros);
    $salvarLivro = new SalvarLivroController($formularioDeLivro, $livros, $sessao, $visao);
    $excluirLivro = new ExcluirLivroController($formularioDeLivro, $livros, $sessao, $visao);

    $roteador = new Roteador($paginaDeErro, [
        ['GET', '/', new InicioController($visao)],
        ['GET', '/versao', new VersaoController($respostas, $versao === false ? 'dev' : $versao)],
        ['GET', '/versoes', new VersoesController($respostas, $versoes)],
        ['GET', '/login', new TelaDeLoginController($visao, $acesso, $sessao)],
        ['POST', '/login', $protegido(new EntrarController(
            $visao,
            Credencial::doFormatoHtpasswd($credencial === false ? '' : $credencial),
            $acesso,
            $sessao,
            new LimiteDeTentativas(sys_get_temp_dir() . '/scripta-manent-login', $relogio),
            $log,
        ))],
        ['POST', '/sair', $protegido(new SairController($visao, $acesso, $log))],
        ['POST', '/dados-de-exemplo/restaurar', $protegido(new RestaurarDadosDeExemploController(
            $restaurador,
            $sessao,
            $visao,
            $log,
        ))],
        ['GET', '/relatorio', new RelatorioController(
            $visao,
            $fonteDoRelatorio,
            $fusoDeExibicao,
            $geradorDePdf,
            $sessao,
        )],
        ['GET', '/relatorio/pdf', new RelatorioPdfController(
            $visao,
            $fonteDoRelatorio,
            $geradorDePdf,
            $respostas,
            $sessao,
            $log,
            $fusoDeExibicao,
            [
                'bootstrap.min.css' => $lerArquivo(__DIR__ . '/../vendor/twbs/bootstrap/dist/css/bootstrap.min.css'),
                'aplicacao.css' => $lerArquivo(__DIR__ . '/../public/assets/css/aplicacao.css'),
            ],
        )],
        ['GET', '/livros', new ListaDeLivrosController($visao, $livros, $sessao, $formularioDeLivro)],
        ['GET', '/livros/novo', $abrirLivro],
        ['POST', '/livros', $protegido($salvarLivro)],
        ['GET', '/livros/{codigo:\d+}/editar', $abrirLivro],
        ['POST', '/livros/{codigo:\d+}', $protegido($salvarLivro)],
        ['POST', '/livros/{codigo:\d+}/excluir', $protegido($excluirLivro)],
        ['GET', '/autores', new ListaDeAutoresController($visao, $autores, $sessao, $formularioDeAutor)],
        ['GET', '/autores/novo', $abrirAutor],
        ['POST', '/autores', $protegido($salvarAutor)],
        ['GET', '/autores/{codigo:\d+}/editar', $abrirAutor],
        ['POST', '/autores/{codigo:\d+}', $protegido($salvarAutor)],
        ['POST', '/autores/{codigo:\d+}/excluir', $protegido($excluirAutor)],
        ['GET', '/assuntos', new ListaDeAssuntosController($visao, $assuntos, $sessao, $formularioDeAssunto)],
        ['GET', '/assuntos/novo', $abrirAssunto],
        ['POST', '/assuntos', $protegido($salvarAssunto)],
        ['GET', '/assuntos/{codigo:\d+}/editar', $abrirAssunto],
        ['POST', '/assuntos/{codigo:\d+}', $protegido($salvarAssunto)],
        ['POST', '/assuntos/{codigo:\d+}/excluir', $protegido($excluirAssunto)],
    ]);

    return new TempoDeResposta(
        new TratadorDeErros(new ExigeLogin($roteador, $acesso, $sessao, $visao), $paginaDeErro, $log),
        $medicoes,
    );
};
