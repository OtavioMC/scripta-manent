# Decisões do projeto

Por que cada coisa foi escolhida e o que ficou de fora.

## PHP sem framework

O desafio pede PHP puro. Usei PHP 8.4 com as PSRs de requisição, resposta, middleware e log, e peguei só os componentes que precisava pelo Composer: fast-route para rotas, nyholm/psr7 para requisição e resposta, Twig para templates. Cada um resolve uma coisa e pode ser trocado sem mexer no resto.

Laravel e Symfony completos ficaram de fora porque escondem rotas, sessão, acesso ao banco e tratamento de erros escritos à vista, que são bons itens de avaliação.

## Arquitetura em camadas

O código fica em `Domain`, `Application`, `Infrastructure` e `Http`, e a dependência sempre aponta para dentro. A regra do livro não sabe que existe MySQL, e o controller não sabe que existe Redis. Quem garante isso no CI é o Deptrac.

Mantive a versão enxuta: um controller por rota e um serviço por cadastro, sem objeto de entrada e saída para cada operação. Em um cadastro desse tamanho, isso só criaria arquivo.

A montagem fica toda em `config/aplicacao.php`, sem contêiner de injeção. Dá para achar qualquer dependência com uma busca no código.

## PDO sem ORM

As consultas são SQL escrito à mão, com parâmetros. O modelo é pequeno e fixo, e o relatório depende de uma view. Um ORM traria convenções de nome que brigam com o modelo do desafio, como `Codl` e `codAs`.

## MySQL e modelo de dados

O modelo segue o diagrama do desafio, com a coluna `Valor` e a view do relatório como únicos acréscimos. Um teste compara o banco real com o modelo e quebra o CI se algo mudar.

Por isso não existe tabela de usuários nem coluna de estoque. Em um sistema real eu separaria obra de exemplar.

## Twig e Bootstrap

Twig escapa o HTML por padrão, o que tira uma classe inteira de erro de XSS. O Bootstrap vem pelo Composer e é servido pelo nginx, então a apresentação funciona sem internet.

O JavaScript é puro, em módulos pequenos. A busca instantânea pede a mesma página ao servidor e troca só os resultados, então não precisei de React nem de Vue, e sem JavaScript tudo continua funcionando.

## Relatório

O desafio pede um componente de relatório e cita Crystal e ReportViewer, que são do mundo .NET. Em PHP, o equivalente é um template mais um gerador de PDF: a tela e o PDF saem do mesmo Twig, e o PDF é gerado pelo Gotenberg, um Chromium em contêiner.

Dompdf e TCPDF ficaram de fora porque não entendem o CSS do Bootstrap 5, e eu teria que manter dois layouts. Se o Gotenberg cair, a prévia na tela continua funcionando.

O agrupamento fica no domínio, em funções puras. A view só faz as junções. Assim dá para testar a regra do total, em que o livro com dois autores conta uma vez, sem banco.

## Redis

O cache guarda listas, buscas e relatório. A chave leva o número de versão da coleção, e cada gravação incrementa esse número depois do commit. Assim o cache velho para de ser lido na hora, sem varrer chaves.

Decoradores envolvem os repositórios, então serviço e controller não mudam com o cache ligado ou desligado. Com o Redis fora do ar, a leitura vai direto ao MySQL.

Com 12 livros o ganho é pequeno. Com 5 mil, a busca no relatório cai de 89 ms para 5 ms. Os números estão no README.

## Login sem tabela de usuários

A credencial fica no `.env`, no formato `usuario:hash` do `htpasswd`, porque o modelo não permite uma tabela nova. A sessão é a nativa do PHP, em arquivo, e expira com 2 horas sem uso.

A senha saiu do nginx porque a janela do navegador não tem botão de sair, não expira sessão e não permite limitar tentativas com bloqueio crescente.

Ficou de fora o usuário administrador separado. Quem passou pelo login pode restaurar os dados de exemplo, sempre com confirmação e registro no log.

## Deploy

O CI roda estilo, análise estática, camadas, testes e auditoria de dependências. A imagem vai para o GitHub Container Registry e só é promovida depois de aprovação manual. A VPS busca a imagem nova sozinha, então nenhuma porta fica aberta para o GitHub e o GitHub não guarda acesso ao servidor. A entrada pública é um Cloudflare Tunnel.

## Sobre uso de IA.

Não foi utilizado IA no central do projeto, fora ferramenta de auto-complete e agilização de mensagens de commit pelo copilot, o domínio inteiro foi feito a mão. Acabei não me atentando fora quando já era tarde e isso ajudou na minha produtividade com códigos repetitivos.