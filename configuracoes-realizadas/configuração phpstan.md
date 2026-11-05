# Configuração PHPStan

1. Adicionei o PHPStan

   ```bash
   docker compose run --rm php composer require --dev phpstan/phpstan:2.3.5
   ```

2. No `composer.json` criei o script

   ```json
   "analise": "phpstan analyse --memory-limit=512M"
   ```

3. Criei o `phpstan.neon.dist`

   ```neon
   parameters:
     level: max
     paths:
       - config
       - public
       - src
       - tests
   ```

4. A análise roda com

   ```bash
   docker compose run --rm php composer analise
   ```
