# Configuração CaptainHook

1. Adicionei o CaptainHook e o instalador de hooks

   ```bash
   docker compose run --rm php composer config allow-plugins.captainhook/hook-installer true
   docker compose run --rm php composer require --dev captainhook/captainhook:5.29.2 captainhook/hook-installer:1.0.5
   ```

2. Criei o `captainhook.json`

   ```json
   {
     "config": {
       "run": {
         "mode": "docker",
         "exec": "MSYS_NO_PATHCONV=1 docker compose run --rm -T php",
         "path": "vendor/bin/captainhook"
       }
     },
     "pre-commit": {
       "enabled": true,
       "actions": [
         {
           "action": "\\CaptainHook\\App\\Hook\\PHP\\Action\\Linting"
         },
         {
           "action": "vendor/bin/phpcs {$STAGED_FILES|of-type:php}",
           "conditions": [
             {
               "exec": "\\CaptainHook\\App\\Hook\\Condition\\FileStaged\\OfType",
               "args": ["php"]
             }
           ]
         },
         {
           "action": "vendor/bin/phpstan analyse --memory-limit=512M --no-progress",
           "conditions": [
             {
               "exec": "\\CaptainHook\\App\\Hook\\Condition\\FileStaged\\OfType",
               "args": ["php"]
             }
           ]
         }
       ]
     },
     "commit-msg": {
       "enabled": true,
       "actions": [
         {
           "action": "\\CaptainHook\\App\\Hook\\Message\\Action\\Regex",
           "options": {
             "regex": "/^(?!(?i:.*co-authored-by))(?!(?i:.*signed-off-by))(?!.*[\\x{1F000}-\\x{1FAFF}\\x{2600}-\\x{27BF}])(build|chore|ci|docs|feat|fix|refactor|style|test)(\\([a-z0-9-]+\\))?: \\p{Ll}/su",
             "error": "A mensagem precisa seguir o formato tipo(escopo): descrição, com a descrição em minúscula, sem coautoria, sem assinatura e sem emoji."
           }
         },
         {
           "action": "\\CaptainHook\\App\\Hook\\Message\\Action\\Rules",
           "options": [
             "\\CaptainHook\\App\\Hook\\Message\\Rule\\MsgNotEmpty",
             ["\\CaptainHook\\App\\Hook\\Message\\Rule\\LimitSubjectLength", [72]],
             "\\CaptainHook\\App\\Hook\\Message\\Rule\\NoPeriodOnSubjectEnd",
             "\\CaptainHook\\App\\Hook\\Message\\Rule\\SeparateSubjectFromBodyWithBlankLine"
           ]
         }
       ]
     },
     "pre-push": {
       "enabled": true,
       "actions": [
         {
           "action": "composer testes"
         },
         {
           "action": "composer camadas"
         }
       ]
     }
   }
   ```

3. Instalei as dependências de novo, e o instalador colocou os hooks no `.git/hooks`

   ```bash
   docker compose run --rm php composer install
   ```

   Num clone novo basta esse `composer install`. Se o `captainhook.json` mudar, reinstalo pelo PowerShell com

   ```powershell
   docker compose run --rm -T php vendor/bin/captainhook install --force --configuration=/app/captainhook.json
   ```
