# scripta-manent
Sistema web em PHP 8.4 para gestão de acervo de livros.

Cadastro de livros, autores e assuntos, com um relatório dos livros agrupados por autor. Um livro pode ter vários autores e vários assuntos.

O sistema está no ar em https://scriptamanent.com.br, com login e senha enviados pelo devido canal de comunicação.

O nome vem do provérbio latino "verba volant, scripta manent", "as palavras voam, o que está escrito permanece". Uma conversa se perde no ar, mas um livro atravessa séculos na estante. Achei justo que um sistema feito para guardar livros carregasse essa ideia.

As decisões de arquitetura e tecnologia estão em [docs/decisoes.md](docs/decisoes.md).

## Tecnologias

| O quê | Escolha |
|---|---|
| Linguagem | PHP 8.4, sem framework completo |
| Banco | MySQL 8.4 LTS |
| Acesso ao banco | PDO com consultas parametrizadas |
| Rotas | nikic/fast-route |
| Requisição e resposta | nyholm/psr7, nyholm/psr7-server e laminas/laminas-httphandlerrunner (PSR-7 e PSR-15) |
| Templates | Twig |
| Interface | Bootstrap 5, instalado pelo Composer |
| PDF do relatório | Gotenberg |
| Cache | Redis |
| Servidor | nginx e PHP-FPM |
| Testes | PHPUnit |
| Qualidade | PHPStan, PHP_CodeSniffer (PSR-12) e Deptrac |
| Git hooks | CaptainHook |
| Ambiente | Docker Compose |
| CI/CD | GitHub Actions |
| Hospedagem | VPS na Hetzner, com Cloudflare na frente |

### Relatório

Os dados vêm de uma view no banco, com uma linha por autor e livro e os assuntos juntos numa coluna só. Livro com dois autores aparece nos dois grupos, mas conta uma vez no total. No fim tem uma seção com os livros em coautoria, agrupados pelos autores.

O PDF sai do mesmo template da tela e é gerado pelo Gotenberg, um Chromium rodando em contêiner. Escolhi ele porque o Dompdf não entende o CSS do Bootstrap 5.

### Cache

Listas, buscas e relatório ficam em cache no Redis por até 5 minutos. Cada coleção tem um número de versão, que entra na chave do cache. Toda gravação incrementa a versão depois do commit, então o cache antigo deixa de ser lido na hora, sem precisar apagar nada. O botão de restaurar os dados de exemplo incrementa as quatro coleções.

Se alguém mudar os dados enquanto você está com a página aberta, aparece um aviso pra atualizar. A página guarda a versão com que abriu e consulta `/versoes` a cada 15 segundos.

Com `REDIS_URL` vazio, ou com o Redis fora do ar, tudo funciona direto no MySQL, só sem o aviso. A queda vai pro log.

O cabeçalho `Server-Timing` mostra de onde veio cada leitura e quanto tempo levou, e dá pra ver na aba Network do DevTools. Medi a mediana de 30 requisições no Docker local:

| Cenário | Leitura no MySQL | Leitura no Redis | Página sem cache | Página com cache |
|---|---|---|---|---|
| Relatório, 12 livros | 7,4 ms | 5,1 ms | 70,5 ms | 53,1 ms |
| Lista de livros, 12 livros | 6,4 ms | 4,1 ms | 78,5 ms | 55,5 ms |
| Busca no relatório, 5 mil livros | 89,0 ms | 5,0 ms | 154,8 ms | 49,5 ms |
| Busca em livros, 5 mil livros | 36,7 ms | 4,4 ms | 109,7 ms | 62,1 ms |

Com poucos livros o ganho é pequeno, porque o MySQL já é rápido. Com volume ele aparece, principalmente na busca, que precisa varrer a tabela.

### Paginação

Livros, autores e assuntos aparecem em páginas de 20, em ordem alfabética. O banco devolve só a página pedida, com `LIMIT` e `OFFSET`, e um `COUNT` com o mesmo filtro da busca dá o total. A página fica no endereço, como em `/livros?busca=machado&pagina=2`.


## Arquitetura

Clean Architecture no backend

```
src/
├── Domain/          entidades, valores (Dinheiro, AnoPublicacao) e regras puras
├── Application/     casos de uso e as interfaces de que eles precisam
├── Infrastructure/  repositórios PDO, cache Redis e PDF Gotenberg
└── Http/            controllers, middlewares, rotas e apresentadores
```

O domínio não depende da tecnologia de banco, HTTP nem Twig. As interfaces ficam em `Application` e as implementações em `Infrastructure`, ligadas em um único arquivo de configuração. O Deptrac confere no CI se alguma camada de dentro passou a depender de uma de fora.

No front não utilizei framework de componentização JavaScript. Foi utilizado Twig com Bootstrap, organizado em layout, macros e componentes, mais um pouco de JavaScript puro em módulos pequenos (máscara de moeda, modal de exclusão, aviso de alteração, tema escuro). Os dados chegam formatados pros templates, que só exibem.

Os formulários já validam no navegador, com os atributos do próprio HTML e os mesmos limites do domínio, evitando que sejam enviadas requisições incorretas a mais pro servidor desnecessariamente.

Depois de enviar, o botão fica travado até a próxima página carregar, pra um duplo clique não gravar a mesma coisa duas vezes. Se a validação ou a confirmação cancelarem o envio, o botão continua livre.

## Banco

As tabelas seguem o modelo à risca, com os mesmos nomes, tipos, chaves e índices. Os únicos acréscimos são a coluna `Valor` no livro e a view do relatório. Tudo nasce dos scripts da pasta `banco/`, rodados no MySQL durante a subida.

- `000-usuario-aplicacao.sh` cria o usuário da aplicação, que lê e grava dados mas não cria nem altera tabelas
- `001-tabelas.sql` cria as tabelas, com chaves estrangeiras sem cascata, então autor ou assunto com livro vinculado não pode ser excluído
- `002-view-relatorio.sql` cria a `vw_livros_por_autor`, com uma linha por autor e livro, incluindo os livros sem autor
- `003-dados-exemplo.sql` carrega 62 livros de exemplo, de 43 autores e 13 assuntos, com casos de coautoria, livro sem autor, livro sem assunto, autor sem livro e assunto sem livro

Um teste de integração compara o banco real com o modelo pelo `information_schema`. Esse teste precisa ser atualizado para qualquer adição ou remoção de tabelas no banco, do contrário o CI barrará a alteração.

### Erros do banco

Os erros do MySQL são identificados pelo SQLSTATE e pelo código, e cada um vira uma resposta própria.

| Erro | Código | O que a pessoa vê |
|---|---|---|
| Excluir autor ou assunto com livro | 1451 | Modal com os livros vinculados |
| Vincular autor ou assunto que acabou de ser excluído | 1452 | Mensagem no formulário |
| Banco fora do ar ou conexão perdida no meio da operação | 2002, 2006 e 2013 | Página 503, sistema indisponível |
| Deadlock ou espera por bloqueio esgotada | 1213 e 1205 | Página 503 dizendo que nada foi salvo e que dá pra enviar de novo |

O log guarda o erro original do MySQL junto, pra dar pra saber o que aconteceu.

## Testes

Testes com PHPUnit, em três níveis. As regras do domínio e o agrupamento do relatório foram feitos em TDD, com o teste commitado antes do código.

- Unidade, para o domínio e os casos de uso, com implementações falsas.
- Integração, contra um MySQL em contêiner, para repositórios, view e estrutura do banco.
- Funcionais, simulando requisições pela aplicação inteira.

## Implantação

VPS da Hetzner com Docker Compose, atrás da Cloudflare (DNS, HTTPS e um tunnel, então o servidor não tem porta web aberta). Banco, Redis e Gotenberg só existem na rede interna.

O GitHub Actions roda lint, PHPStan, Deptrac, testes e `composer audit` em todo push. Nos pushes na `main`, gera a imagem e publica no GitHub Container Registry. Depois que eu aprovo, o servidor percebe a imagem nova em até um minuto, baixa e reinicia sozinho, sem nenhuma porta aberta para o GitHub.

O Dependabot confere toda semana as versões dos pacotes PHP, das imagens Docker e das actions, e abre um pull request agrupado por tipo quando sai versão nova. Cada pull request passa pelo mesmo CI antes de eu decidir se entra. PHP e MySQL só recebem correções dentro da 8.4.

O sistema pede login numa tela própria. A credencial fica na variável `NGINX_HTPASSWD` do `.env`, no formato `usuario:hash` gerado pelo `htpasswd`, e a sessão expira depois de 2 horas sem uso. Não tem tabela de usuários porque eu não queria mexer no modelo.

O passo a passo do domínio, da VPS, do tunnel e do pipeline está na pasta [configuracoes-realizadas](configuracoes-realizadas).

## Como rodar localmente

Só precisa de Git e Docker.

1. Clone o repositório

   ```bash
   git clone https://github.com/OtavioMC/scripta-manent.git
   cd scripta-manent
   ```

2. Gere o `.env`. No final, o comando mostra o usuário e a senha para entrar no sistema. Anote a senha, porque ela não aparece de novo

   ```bash
   sh bin/gerar-env
   ```

3. Instale as dependências

   ```bash
   docker compose run --rm php composer install
   ```

4. Suba os contêineres

   ```bash
   docker compose up -d --build
   ```

5. Abra http://localhost:8080 e entre com o usuário e a senha do passo 2

Os scripts do banco só rodam com o volume vazio. Pra recriar o banco do zero, com os dados de exemplo

```bash
docker compose down -v
docker compose up -d
```

Verificações

```bash
docker compose run --rm php composer sintaxe
docker compose run --rm php composer estilo
docker compose run --rm php composer analise
docker compose run --rm php composer camadas
docker compose run --rm php composer testes
```

### Depurar com o Xdebug

O contêiner de desenvolvimento traz o Xdebug 3.5.3 desligado. A imagem de produção não tem a extensão.

1. No VS Code, instale a extensão PHP Debug (`xdebug.php-debug`)
2. Crie o arquivo `.vscode/launch.json`

   ```json
   {
     "version": "0.2.0",
     "configurations": [
       {
         "name": "Xdebug no Docker",
         "type": "php",
         "request": "launch",
         "port": 9003,
         "pathMappings": { "/app": "${workspaceFolder}" }
       }
     ]
   }
   ```

3. No `.env`, troque para `XDEBUG_MODE=debug` e recrie o contêiner

   ```bash
   docker compose up -d php
   ```

4. No VS Code, marque um ponto de parada e aperte F5
5. Abra a página com `XDEBUG_TRIGGER=1` no endereço, como em http://localhost:8080/livros?XDEBUG_TRIGGER=1. Nos testes:

   ```bash
   docker compose exec -e XDEBUG_TRIGGER=1 php vendor/bin/phpunit --filter PaginaTest
   ```

6. Para desligar, volte para `XDEBUG_MODE=off` e rode `docker compose up -d php` de novo

## Versões

### 1.3.0

Pagina as listas de livros, autores e assuntos.

- 20 itens por página, com números de página, setas e o intervalo mostrado
- A busca continua no endereço ao trocar de página, e uma página além da última redireciona para a última
- Cada página fica no cache do Redis e é invalidada a cada gravação
- Os dados de exemplo passam a ter 62 livros, 43 autores e 13 assuntos

### 1.2.0

Evita o duplo envio dos formulários.

- O botão de envio trava enquanto o formulário é enviado, em todas as telas e no login
- Os botões de salvar, entrar e restaurar mostram "Salvando...", "Entrando..." e "Restaurando..." durante o envio

### 1.1.0

Trata os erros temporários do banco.

- Conexão perdida no meio da operação responde 503, como o banco fora do ar, em vez de 500
- Deadlock e espera por bloqueio esgotada mostram uma página própria, dizendo que nada foi salvo
- O log registra o erro original do MySQL junto da exceção

### 1.0.1

Resume as decisões em `docs/decisoes.md`.

### 1.0.0

Versão entregue.

- Página 503 quando o banco está fora do ar
- Decisões de arquitetura e tecnologia em `docs/decisoes.md`
- Guia numerado para rodar localmente

### 0.9.0

Adiciona o cache no Redis e a busca instantânea.

- Listas, buscas e relatório em cache no Redis, invalidado a cada gravação e ao restaurar os dados de exemplo
- Aviso na tela quando os registros mudam depois que a página abriu
- Cabeçalho `Server-Timing` com a origem e o tempo de cada leitura
- Busca que atualiza os resultados enquanto a pessoa digita

### 0.8.0

Troca a senha do navegador por uma tela de login.

- Tela de login própria, com sessão de 2 horas sem uso e botão para sair
- Todas as páginas pedem login, menos o login e o `/versao`, e depois de entrar a pessoa volta para a página que tinha pedido
- Mesma mensagem para usuário inexistente e senha errada, com bloqueio crescente depois de 5 tentativas erradas
- Login, falhas e saídas ficam registrados no log de auditoria
- Ícone do site

### 0.7.0

Adiciona a busca e protege os formulários.

- Busca nas listas de livros, autores e assuntos e no relatório, sem diferenciar maiúsculas nem acentos
- Livros são encontrados pelo título, editora, ano e pelos nomes de autores e assuntos
- No relatório, a busca traz o livro com todos os autores, e o PDF sai com o mesmo filtro
- O modal de exclusão bloqueada ganha o botão "Mostrar títulos", que abre os livros do autor ou assunto
- Todo formulário que grava dados leva um token CSRF, e envio sem token é recusado

### 0.6.0

Cria o relatório de livros por autor.

- Relatório na tela, lido da view `vw_livros_por_autor`, com um grupo por autor, os livros sem autor num grupo próprio e subtotal em cada grupo
- Total geral contando cada livro uma vez, mesmo com mais de um autor
- Seção "Livros com mais de um autor", agrupada pelo conjunto completo de autores
- Botão "Baixar PDF", gerado pelo Gotenberg a partir do mesmo template da tela; se o Gotenberg estiver fora do ar, a prévia continua e aparece um aviso

### 0.5.0

Adiciona a restauração dos dados de exemplo.

- Botão "Restaurar dados de exemplo" na tela inicial, com confirmação, que apaga os cadastros e recarrega o script `banco/003-dados-exemplo.sql` numa transação
- Cada restauração fica registrada no log de auditoria, com a origem da requisição

### 0.4.0

Cria os cadastros de livros, autores e assuntos.

- Lista, cadastro, edição e exclusão de livros, autores e assuntos, com o formulário abrindo em modal na própria lista
- Valida no navegador e no servidor com os mesmos limites do banco, dizendo o limite na mensagem
- Grava o livro e os vínculos com autores e assuntos numa única transação
- Bloqueia a exclusão de autor ou assunto com livros e mostra os livros vinculados num modal
- Valor em reais com máscara e ano com 4 dígitos

### 0.3.0

Cria o layout e a tela inicial.

- Adiciona o Twig e o Bootstrap 5
- Cria o layout base, com menu, tema claro e escuro e o provérbio na barra
- Cria a tela inicial, com carrossel de largura total, atalhos para as telas e uma seção sobre o sistema
- Troca as respostas de erro 404 e 405 por páginas no layout do site, dizendo o que aconteceu e o que fazer
- Adiciona o tratador global de erros, que mostra a página 500 sem detalhe tecnico e registra a falha em log JSON


### 0.2.0

Cria o banco de dados.

- Adiciona o MySQL 8.4 no Docker, no desenvolvimento e na produção
- Cria as tabelas seguindo o modelo, com um teste que compara o banco real com ele
- Cria a view do relatório, agrupada por autor e incluindo os livros sem autor
- Carrega dados de exemplo para a demonstração
- Separa um usuário para a aplicação, sem permissão para criar ou alterar tabelas

### 0.1.0

Inicia a base do projeto.

- Configura o ambiente de desenvolvimento em Docker
- Adiciona testes, análise estática e verificação de estilo e de camadas, rodando nos git hooks e no CI
- Cria a base da aplicação, com roteamento e a rota `/versao`
- Coloca o site no ar na VPS, com deploy a partir da `main` depois de aprovação manual
- Protege o site online com usuário e senha

## Licença

MIT. Veja o arquivo [LICENSE](LICENSE).
