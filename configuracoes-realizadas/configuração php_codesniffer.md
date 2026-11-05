# Configuração PHP_CodeSniffer

1. Adicionei o PHP_CodeSniffer

   ```bash
   docker compose run --rm php composer require --dev squizlabs/php_codesniffer:4.0.4
   ```

2. No `composer.json` criei o script

   ```json
   "estilo": "phpcs"
   ```

3. Criei o `phpcs.xml.dist`

   ```xml
   <?xml version="1.0" encoding="UTF-8"?>
   <ruleset name="scripta-manent">
       <arg name="extensions" value="php"/>
       <arg name="colors"/>
       <arg value="sp"/>

       <file>config</file>
       <file>public</file>
       <file>src</file>
       <file>tests</file>

       <rule ref="PSR12"/>
   </ruleset>
   ```

4. A verificação roda com

   ```bash
   docker compose run --rm php composer estilo
   ```
