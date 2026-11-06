# Decisões do projeto

## Por que sem framework

Escolhi fazer em PHP 8.4 sem framework completo, seguindo as PSRs, pra deixar à vista como rota, sessão e banco funcionam. Peguei pelo Composer só o que eu ia usar: fast-route pras rotas, nyholm/psr7 pra requisição e resposta, Twig pros templates e Predis pro Redis.

## Como organizei o código

Usei Clean Architecture numa versão pragmática, com as camadas `Domain`, `Application`, `Infrastructure` e `Http`. A dependência sempre aponta pra dentro: a regra do livro não sabe que existe MySQL, e as interfaces ficam em `Application`, com a implementação em `Infrastructure`. Foi isso que me deixou colocar o Redis depois sem mexer nos serviços nem nos controllers. O Deptrac vigia essa regra no CI.

## Banco

Escrevi o SQL na mão com PDO. O modelo é pequeno e não pode mudar, e um ORM ia brigar com nomes como `Codl` e `codAs`. Fora a coluna `Valor` e a view do relatório, está igual ao diagrama, e um teste quebra o CI se alguém mexer. Por isso também não tem tabela de usuários nem controle de estoque. Num sistema real eu separaria a obra do exemplar.

## Tela

Twig já escapa o HTML por padrão, o que tira boa parte do risco de XSS. O Bootstrap vem pelo Composer e o próprio nginx serve, pra apresentação funcionar mesmo sem internet. No JavaScript fiquei no puro: a busca instantânea pede a mesma página e troca só os resultados, então React ou Vue não fariam falta.

## Relatório

Crystal e ReportViewer são do mundo .NET, então em PHP montei com template e gerador de PDF Gotenberg que consegue ler melhor Bootstrap 5

## Cache

O Redis guarda as listas, as buscas e o relatório. Cada coleção tem um número de versão que entra na chave, e toda gravação sobe esse número, então o cache velho deixa de ser lido na hora sem eu precisar apagar nada. Se o Redis cair, o sistema segue pelo MySQL. Com 12 livros quase não muda nada, mas com 5 mil a busca no relatório caiu de 89 ms pra 5 ms.

## Login

Como não dava pra criar tabela de usuários, a credencial ficou no `.env`. Comecei com a senha no nginx, mas troquei por uma tela própria porque a janela do navegador não tem botão de sair, não expira sessão e não deixa bloquear quem erra demais. Não fiz um administrador separado: quem entra pode restaurar os dados de exemplo, com confirmação e registro no log.

## Deploy

O CI roda estilo, análise estática, camadas, testes e auditoria das dependências. Nada vai ao ar sem eu aprovar, e é a VPS que busca a imagem nova, então o GitHub não precisa de acesso ao servidor. O site chega na internet por um Cloudflare Tunnel, sem porta web aberta.
