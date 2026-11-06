# scripta-manent
Sistema web em PHP 8.4 para gestão de acervo de livros.

Cadastro de livros, autores e assuntos, com um relatório dos livros agrupados por autor. Um livro pode ter vários autores e vários assuntos.

O nome vem do provérbio latino "verba volant, scripta manent", "as palavras voam, o que está escrito permanece". Uma conversa se perde no ar, mas um livro atravessa séculos na estante. Achei justo que um sistema feito para guardar livros carregasse essa ideia.

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

Listas e relatório ficam em cache no Redis. Se alguém mudar os dados enquanto você está com a página aberta, aparece um aviso pra recarregar. Com o Redis desligado tudo funciona direto no MySQL, só sem o aviso.

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

## Banco

As tabelas seguem o modelo à risca, com os mesmos nomes, tipos, chaves e índices. Os únicos acréscimos são a coluna `Valor` no livro e a view do relatório. Tudo nasce dos scripts da pasta `banco/`, rodados no MySQL durante a subida.

- `000-usuario-aplicacao.sh` cria o usuário da aplicação, que lê e grava dados mas não cria nem altera tabelas
- `001-tabelas.sql` cria as tabelas, com chaves estrangeiras sem cascata, então autor ou assunto com livro vinculado não pode ser excluído
- `002-view-relatorio.sql` cria a `vw_livros_por_autor`, com uma linha por autor e livro, incluindo os livros sem autor
- `003-dados-exemplo.sql` carrega 12 livros de exemplo, com casos de coautoria, livro sem autor e livro sem assunto

Um teste de integração compara o banco real com o modelo pelo `information_schema`. Esse teste precisa ser atualizado para qualquer adição ou remoção de tabelas no banco, do contrário o CI barrará a alteração.

## Testes

TDD com PHPUnit, em três níveis.

- Unidade, para o domínio e os casos de uso, com implementações falsas.
- Integração, contra um MySQL em contêiner, para repositórios, view e estrutura do banco.
- Funcionais, simulando requisições pela aplicação inteira.

## Implantação

VPS da Hetzner com Docker Compose, atrás da Cloudflare (DNS, HTTPS e um tunnel, então o servidor não tem porta web aberta). Banco, Redis e Gotenberg só existem na rede interna.

O GitHub Actions roda lint, PHPStan, Deptrac, testes e `composer audit` em todo push. Nos pushes na `main`, gera a imagem e publica no GitHub Container Registry. Depois que eu aprovo, o servidor percebe a imagem nova em até um minuto, baixa e reinicia sozinho, sem nenhuma porta aberta para o GitHub.

O ambiente online pede usuário e senha. O administrador tem uma credencial própria no `.env`, guardada como hash, que libera remover vínculos e restaurar os dados de exemplo. Não tem tabela de usuários porque eu não queria mexer no modelo.

O passo a passo do domínio, da VPS, do tunnel e do pipeline está na pasta [configuracoes-realizadas](configuracoes-realizadas).

## Como configurar localmente

Pré-Requisitos: Instalar Git e Docker.

```bash
git clone https://github.com/OtavioMC/scripta-manent.git
cd scripta-manent
sh bin/gerar-env
docker compose up -d --build
docker compose run --rm php composer install
```

Site em http://localhost:8080.

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

## Versões

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
