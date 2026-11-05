# Configuração PHPUnit

1. Adicionei o PHPUnit

   ```bash
   docker compose run --rm php composer require --dev phpunit/phpunit:13.4.6
   ```

2. No `composer.json` criei o script

   ```json
   "scripts": {
     "testes": "phpunit"
   }
   ```

3. Criei o `phpunit.xml.dist`

   ```xml
   <?xml version="1.0" encoding="UTF-8"?>
   <phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
            xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
            bootstrap="vendor/autoload.php"
            cacheDirectory=".phpunit.cache"
            colors="true"
            beStrictAboutOutputDuringTests="true"
            failOnDeprecation="true"
            failOnEmptyTestSuite="true"
            failOnNotice="true"
            failOnRisky="true"
            failOnWarning="true">
       <testsuites>
           <testsuite name="unidade">
               <directory>tests/Unidade</directory>
           </testsuite>
       </testsuites>
       <source>
           <include>
               <directory>src</directory>
           </include>
       </source>
   </phpunit>
   ```

4. Criei as pastas do projeto, cada uma com um arquivo provisório pra ser apagado depois

   - `src/Domain/Provisorio.php`, `src/Application/Provisorio.php`, `src/Infrastructure/Provisorio.php` e `src/Http/Provisorio.php`, todos assim, mudando só o namespace

     ```php
     <?php

     declare(strict_types=1);

     namespace ScriptaManent\Domain;

     final class Provisorio
     {
     }
     ```

   - `config/provisorio.php`

     ```php
     <?php

     declare(strict_types=1);

     return [];
     ```

   - `public/provisorio.php`

     ```php
     <?php

     declare(strict_types=1);
     ```

5. Criei o `tests/Unidade/EstruturaTest.php`, um teste provisório que não testa nada, só pra suíte rodar

   ```php
   <?php

   declare(strict_types=1);

   namespace ScriptaManent\Tests\Unidade;

   use PHPUnit\Framework\TestCase;

   final class EstruturaTest extends TestCase
   {
       public function testEstruturaDeTestesRoda(): void
       {
           $this->expectNotToPerformAssertions();
       }
   }
   ```

6. Os testes rodam com

   ```bash
   docker compose run --rm php composer testes
   ```

7. Acrescentei testes funcionais no `phpunit.xml.dist`

   ```xml
   <testsuite name="funcional">
       <directory>tests/Funcional</directory>
   </testsuite>
   ```
