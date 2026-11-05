# Configuração Deptrac

1. Adicionei o Deptrac

   ```bash
   docker compose run --rm php composer require --dev deptrac/deptrac:4.7.2
   ```

2. No `composer.json` criei o script

   ```json
   "camadas": "deptrac analyse --no-progress"
   ```

3. Criei o `deptrac.yaml`

   ```yaml
   deptrac:
     paths:
       - ./src
     layers:
       - name: Domain
         collectors:
           - type: directory
             value: src/Domain/.*
       - name: Application
         collectors:
           - type: directory
             value: src/Application/.*
       - name: Infrastructure
         collectors:
           - type: directory
             value: src/Infrastructure/.*
       - name: Http
         collectors:
           - type: directory
             value: src/Http/.*
     ruleset:
       Domain: ~
       Application:
         - Domain
       Infrastructure:
         - Domain
         - Application
       Http:
         - Domain
         - Application
   ```

4. Coloquei `/.deptrac.cache` no `.gitignore`

5. A verificação roda com

   ```bash
   docker compose run --rm php composer camadas
   ```
