# Configurar OPcache na VPS

Este guia foi feito para executar **uma etapa por vez**.

Depois de executar uma etapa, envie o resultado antes de continuar.

## Situação atual

Pelos comandos já executados, a VPS possui:

- PHP 8.3.6 instalado
- OPcache instalado
- PHP-FPM 8.3 instalado e habilitado
- OPcache ativo no PHP-FPM
- OPcache configurado para detectar alterações automaticamente

Não é necessário instalar o OPcache novamente.

## Etapa 1: Reiniciar o PHP-FPM

Execute somente este comando:

```bash
sudo systemctl restart php8.3-fpm
```

Envie o resultado ou informe se não apareceu nenhuma mensagem.

## Etapa 2: Confirmar se o serviço está ativo

Somente depois da Etapa 1, execute:

```bash
sudo systemctl is-active php8.3-fpm
```

O resultado esperado é:

```text
active
```

Se aparecer qualquer outro resultado, envie-o antes de continuar.

## Etapa 3: Verificar a configuração atual

Execute:

```bash
sudo php-fpm8.3 -i | grep -E 'opcache.enable|opcache.memory_consumption|opcache.validate_timestamps|opcache.max_accelerated_files'
```

A configuração atual já está adequada para começar:

```text
opcache.enable => On => On
opcache.enable_cli => Off => Off
opcache.max_accelerated_files => 10000 => 10000
opcache.memory_consumption => 128 => 128
opcache.validate_timestamps => On => On
```

## Etapa 4: Deploy da aplicação

Após confirmar que o PHP-FPM está `active`, entre na pasta do projeto:

```bash
cd /srv/apps/php/magna_gestao
```

Execute somente os comandos necessários para o deploy.

### Atualizar dependências

```bash
composer install --no-dev --optimize-autoloader
```

### Limpar e recriar os caches do Laravel

```bash
php artisan optimize:clear
```

```bash
php artisan optimize
```

### Executar migrations

Use este comando somente se o deploy possuir migrations novas:

```bash
php artisan migrate --force
```

### Limpar o OPcache após o deploy

```bash
sudo systemctl restart php8.3-fpm
```

## Etapa 5: Reiniciar workers da fila

Como o projeto usa filas, execute após o deploy:

```bash
php artisan queue:restart
```

## Configuração que não deve ser alterada agora

Mantenha:

```ini
opcache.validate_timestamps=1
```

Não use ainda:

```ini
opcache.validate_timestamps=0
```

Com `0`, todo deploy exige reiniciar o PHP-FPM manualmente e o sistema fica mais sensível a código antigo em memória.

## Resumo

Neste momento, execute apenas:

```bash
sudo systemctl restart php8.3-fpm
```

Depois envie o resultado.
