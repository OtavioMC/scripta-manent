# Decisões do projeto

## PHP sem framework

O desafio pede PHP puro. Usei as PSRs e só os componentes necessários pelo Composer: fast-route, nyholm/psr7, Twig e Predis. Laravel e Symfony completos ficaram de fora porque escondem rotas, sessão e banco, que são o que está em avaliação.

## Arquitetura

Quatro camadas: `Domain`, `Application`, `Infrastructure` e `Http`, com a dependência sempre apontando para dentro. O Deptrac confere isso no CI. A montagem fica em `config/aplicacao.php`, sem contêiner de injeção, e cada rota tem seu controller.

## Banco

PDO com SQL escrito à mão, porque o modelo é pequeno e fixo, e um ORM brigaria com nomes como `Codl` e `codAs`. O modelo segue o diagrama, com `Valor` e a view do relatório como únicos acréscimos. Um teste compara o banco real com ele.

## Interface

Twig escapa o HTML por padrão. O Bootstrap vem pelo Composer e é servido pelo nginx, para funcionar sem internet. O JavaScript é puro, e a busca instantânea troca só os resultados da própria página, sem precisar de React ou Vue.

## Relatório

Tela e PDF saem do mesmo template, e o PDF é gerado pelo Gotenberg. Dompdf e TCPDF não entendem o CSS do Bootstrap 5. O agrupamento fica no domínio, em funções puras, para testar sem banco a regra do total, em que o livro com dois autores conta uma vez.

## Cache

O Redis guarda listas, buscas e relatório. A chave leva a versão da coleção, e cada gravação incrementa essa versão depois do commit, sem varrer chaves. Com o Redis fora do ar, a leitura vai direto ao MySQL. Os números medidos estão no README.

## Login

A credencial fica no `.env`, porque o modelo não permite tabela de usuários. A sessão é a nativa do PHP e expira com 2 horas sem uso. A tela própria substituiu a senha do nginx para ter botão de sair, expiração e bloqueio crescente. Não há administrador separado.

## Deploy

O CI roda estilo, análise estática, camadas, testes e auditoria. A imagem só vai ao ar depois de aprovação manual, e a VPS busca a imagem sozinha, sem porta aberta para o GitHub. A entrada pública é um Cloudflare Tunnel.
