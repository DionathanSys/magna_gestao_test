# Deploy VPS

O deploy e executado pelo script versionado em `scripts/deploy.sh`. Na VPS, a
configuracao inicial deve ser feita uma vez:

```bash
cd /srv/apps/php/magna_gestao
git checkout main
chmod +x scripts/deploy.sh
```

Mantenha o `.env` somente na VPS. Ele nao deve ser versionado. O usuario que
executa o deploy precisa ter acesso de leitura ao repositorio e escrita em
`storage` e `bootstrap/cache`. Para reiniciar servicos automaticamente, execute
como `root` ou configure `sudo` sem senha para `systemctl` e `supervisorctl`.

## Uso

Para atualizar a aplicacao:

```bash
cd /srv/apps/php/magna_gestao
./scripts/deploy.sh
```

O script:

1. Impede dois deploys simultaneos com `flock`.
2. Confirma que o `.env` existe e recusa deploy se houver alteracoes locais rastreadas.
3. Executa `git fetch` e atualiza o branch atual com `git merge --ff-only`.
4. Executa `composer install --no-dev --optimize-autoloader`.
5. Executa `npm ci --include=dev` e `npm run build`.
6. Executa `php artisan migrate --force`.
7. Atualiza os assets do Filament e o link `public/storage`.
8. Limpa caches, recria configuracao/eventos e compila as views Blade.
9. Sinaliza o reinicio gracioso das filas e do scheduler.
10. Tenta atualizar o Supervisor e reiniciar o PHP-FPM, quando disponivel.

O script nao executa `route:cache` nem `php artisan optimize`, pois o projeto
possui rotas com closures.

## Configuracao

Os valores abaixo sao opcionais. O branch atual e usado por padrao:

```bash
DEPLOY_BRANCH=main \
PHP_BIN=/usr/bin/php8.3 \
COMPOSER_BIN=/usr/local/bin/composer \
PHP_FPM_SERVICE=php8.3-fpm \
./scripts/deploy.sh
```

Variaveis disponiveis:

- `DEPLOY_BRANCH`: branch que sera atualizado. Padrao: branch atual.
- `GIT_REMOTE`: remoto Git. Padrao: `origin`.
- `PHP_BIN`: executavel PHP usado pelo Artisan. Padrao: `php`.
- `COMPOSER_BIN`: executavel Composer. Padrao: `composer`.
- `NPM_BIN`: executavel npm. Padrao: `npm`.
- `DEPLOY_BUILD_ASSETS`: use `0` somente se os assets forem compilados fora da VPS. Padrao: `1`.
- `DEPLOY_RUN_MIGRATIONS`: use `0` apenas em uma operacao excepcional. Padrao: `1`.
- `DEPLOY_RESTART_PHP_FPM`: `auto`, `1` ou `0`. Padrao: `auto`.
- `PHP_FPM_SERVICE`: nome do servico PHP-FPM. Padrao: `php8.3-fpm`.
- `DEPLOY_SUPERVISOR`: `auto`, `1` ou `0`. Padrao: `auto`.
- `SUPERVISOR_CONFIG_SOURCE`: arquivo versionado da configuracao do Supervisor.
- `SUPERVISOR_CONFIG_PATH`: destino da configuracao no sistema.
- `DEPLOY_LOCK_FILE`: arquivo do lock. Padrao: `storage/framework/cache/magna_gestao_deploy.lock`.

O modo `auto` nao falha quando `sudo`, Supervisor ou PHP-FPM nao estao
disponiveis; nesses casos o script exibe um aviso. Se esses servicos forem
obrigatorios no ambiente, use `DEPLOY_RESTART_PHP_FPM=1` e
`DEPLOY_SUPERVISOR=1` para transformar a ausencia/falha em erro.

Quando o Supervisor estiver acessivel, o deploy instala automaticamente
`scripts/supervisor/magna_gestao.conf` em `/etc/supervisor/conf.d/magna_gestao.conf`
antes de executar `reread` e `update`. Sem permissao sudo, um administrador
precisa executar essa etapa manualmente.

## Supervisor

O `queue:restart` sinaliza os workers para terminarem o job atual e sairem;
com `autorestart=true`, o Supervisor inicia os processos novamente. O
`schedule:interrupt` faz o mesmo para o scheduler. O script instala a
configuracao versionada e executa `supervisorctl reread` e
`supervisorctl update` quando consegue usar o Supervisor.

Se o deploy ainda nao tiver permissao para instalar a configuracao, execute uma
vez como administrador, ajustando o caminho do projeto se necessario:

```bash
sudo cp scripts/supervisor/magna_gestao.conf /etc/supervisor/conf.d/magna_gestao.conf
sudo supervisorctl reread
sudo supervisorctl update
```

Filas contempladas no worker:

- `automation`
- `automation-import`
- `integracoes`
- `mail-receive`
- `mail-process`
- `mail-trip`
- `mail-cte-return`
- `default`

## Permissoes

Se houver erro de escrita em `storage/logs`, `storage/app` ou
`bootstrap/cache`, execute uma vez com o usuario/grupo do PHP e do Supervisor:

```bash
bash scripts/fix-storage-permissions.sh www-data www-data
```
