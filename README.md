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

### Sem framework grande

Não usei Laravel nem Symfony. Queria demonstrar domínio da linguagem e seguir o modelo de dados à risca, com chave composta nas tabelas de ligação, coisa que o Eloquent não suporta. As consultas são SQL escrito à mão com PDO.

Pra rotas e HTTP usei componentes prontos, os mesmos que o Slim utiliza. Como eles seguem as PSRs, dá pra simular uma requisição inteira nos testes.

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

## Testes

TDD com PHPUnit, em três níveis.

- Unidade, para o domínio e os casos de uso, com implementações falsas.
- Integração, contra um MySQL em contêiner, para repositórios, view e estrutura do banco.
- Funcionais, simulando requisições pela aplicação inteira.

## Implantação

VPS da Hetzner com Docker Compose, atrás da Cloudflare (DNS, HTTPS e um tunnel, então o servidor não tem porta web aberta). Banco, Redis e Gotenberg só existem na rede interna.

O GitHub Actions roda lint, PHPStan, Deptrac, testes e `composer audit` em todo push. Nos pushes na `main`, gera a imagem e publica no GitHub Container Registry. Depois que eu aprovo, o servidor percebe a imagem nova em até um minuto, baixa e reinicia sozinho, sem nenhuma porta aberta para o GitHub.

O ambiente online pede usuário e senha. O administrador tem uma credencial própria no `.env`, guardada como hash, que libera remover vínculos e restaurar os dados de exemplo. Não tem tabela de usuários porque eu não queria mexer no modelo.

## Como configurar localmente

## Versões

## Licença

MIT. Veja o arquivo [LICENSE](LICENSE).
