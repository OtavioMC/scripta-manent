# Configuração Composer

1. Criei o `composer.json` na raiz do projeto

   ```json
   {
     "name": "otaviomc/scripta-manent",
     "description": "Cadastro de livros, autores e assuntos, com relatório agrupado por autor.",
     "type": "project",
     "license": "MIT",
     "require": {
       "php": "~8.4.0",
       "laminas/laminas-httphandlerrunner": "2.14.0",
       "nikic/fast-route": "1.3.1",
       "nyholm/psr7": "1.8.2",
       "nyholm/psr7-server": "1.1.0",
       "psr/http-message": "2.0",
       "psr/http-server-handler": "1.0.2",
       "psr/http-server-middleware": "1.0.2"
     },
     "autoload": {
       "psr-4": {
         "ScriptaManent\\": "src/"
       }
     },
     "autoload-dev": {
       "psr-4": {
         "ScriptaManent\\Tests\\": "tests/"
       }
     },
     "config": {
       "platform": {
         "php": "8.4.27"
       },
       "optimize-autoloader": true,
       "sort-packages": true
     }
   }
   ```

2. Gerei o `composer.lock` pelo Docker, no PowerShell, na pasta do projeto

   ```powershell
   docker run --rm -v "${PWD}:/app" -w /app composer:2.11.1 update --no-install --no-interaction
   ```
