# Deploy VPS

Depois de fazer `git pull` na VPS, rode:

```bash
bash scripts/deploy.sh
```

O script faz:

- `composer install --optimize-autoloader`
- `php artisan migrate --force`
- `php artisan optimize:clear`
- `php artisan filament:upgrade`
- `php artisan queue:restart`

Observacoes:

- Nao usei `route:cache` porque o projeto tem rotas com closures.
- Se houver workers rodando por Supervisor, o `queue:restart` faz o reload gracioso.
- Se a VPS tambem compila frontend, esse passo pode ser incluido depois com `npm ci && npm run build`.

## Supervisor

Template sugerido:

```bash
cp scripts/supervisor/laravel-worker.conf.example /etc/supervisor/conf.d/magna_gestao.conf
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl restart magna_gestao-queue:*
sudo supervisorctl restart magna_gestao-schedule:*
```

Filas contempladas no worker:

- `mail-receive`
- `mail-process`
- `mail-trip`
- `default`

## Permissoes

Se houver erro de escrita em `storage/logs` ou `storage/app`, rode:

```bash
bash scripts/fix-storage-permissions.sh www-data www-data
```

Se o Supervisor estiver rodando com outro usuario/grupo, substitua os parametros.
