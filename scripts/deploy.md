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
