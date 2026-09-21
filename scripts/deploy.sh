#!/usr/bin/env bash
set -Eeuo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

GIT_REMOTE="${GIT_REMOTE:-origin}"
PHP_BIN="${PHP_BIN:-php}"
COMPOSER_BIN="${COMPOSER_BIN:-composer}"
NPM_BIN="${NPM_BIN:-npm}"
DEPLOY_BUILD_ASSETS="${DEPLOY_BUILD_ASSETS:-1}"
DEPLOY_RUN_MIGRATIONS="${DEPLOY_RUN_MIGRATIONS:-1}"
DEPLOY_RESTART_PHP_FPM="${DEPLOY_RESTART_PHP_FPM:-auto}"
PHP_FPM_SERVICE="${PHP_FPM_SERVICE:-php8.3-fpm}"
DEPLOY_SUPERVISOR="${DEPLOY_SUPERVISOR:-auto}"
DEPLOY_LOCK_FILE="${DEPLOY_LOCK_FILE:-/tmp/magna_gestao_deploy.lock}"

log_step() {
    printf '\n==> %s\n' "$1"
}

log_warning() {
    printf 'Aviso: %s\n' "$1" >&2
}

die() {
    printf 'Erro: %s\n' "$1" >&2
    exit 1
}

on_error() {
    printf '\nFalha no deploy na linha %s.\n' "$1" >&2
}

trap 'on_error "$LINENO"' ERR

is_enabled() {
    case "${1,,}" in
        1|true|yes|sim)
            return 0
            ;;
        0|false|no|nao)
            return 1
            ;;
        *)
            die "Valor invalido para uma opcao booleana: $1"
            ;;
    esac
}

require_command() {
    command -v "$1" >/dev/null 2>&1 || die "Comando nao encontrado: $1"
}

can_run_privileged() {
    if (( EUID == 0 )); then
        return 0
    fi

    command -v sudo >/dev/null 2>&1 && sudo -n true >/dev/null 2>&1
}

run_privileged() {
    if (( EUID == 0 )); then
        "$@"
        return
    fi

    command -v sudo >/dev/null 2>&1 || return 127
    sudo -n "$@"
}

artisan() {
    "$PHP_BIN" "$ROOT_DIR/artisan" "$@"
}

refresh_supervisor() {
    case "${DEPLOY_SUPERVISOR,,}" in
        0|false|no|nao)
            return
            ;;
        auto|1|true|yes|sim)
            ;;
        *)
            die "Valor invalido para DEPLOY_SUPERVISOR: $DEPLOY_SUPERVISOR"
            ;;
    esac

    if ! command -v supervisorctl >/dev/null 2>&1; then
        if [[ "${DEPLOY_SUPERVISOR,,}" == "auto" ]]; then
            log_warning "supervisorctl nao encontrado; a fila foi sinalizada pelo Artisan."
            return
        fi

        die "DEPLOY_SUPERVISOR esta habilitado, mas supervisorctl nao foi encontrado."
    fi

    if ! can_run_privileged; then
        if [[ "${DEPLOY_SUPERVISOR,,}" == "auto" ]]; then
            log_warning "Sem permissao sudo nao-interativa para atualizar o Supervisor."
            return
        fi

        die "DEPLOY_SUPERVISOR esta habilitado, mas o usuario nao pode executar supervisorctl."
    fi

    if ! run_privileged supervisorctl reread; then
        if [[ "${DEPLOY_SUPERVISOR,,}" == "auto" ]]; then
            log_warning "Nao foi possivel executar supervisorctl reread."
            return
        fi

        die "Falha ao executar supervisorctl reread."
    fi

    if ! run_privileged supervisorctl update; then
        if [[ "${DEPLOY_SUPERVISOR,,}" == "auto" ]]; then
            log_warning "Nao foi possivel executar supervisorctl update."
            return
        fi

        die "Falha ao executar supervisorctl update."
    fi
}

restart_php_fpm() {
    case "${DEPLOY_RESTART_PHP_FPM,,}" in
        0|false|no|nao)
            return
            ;;
        auto|1|true|yes|sim)
            ;;
        *)
            die "Valor invalido para DEPLOY_RESTART_PHP_FPM: $DEPLOY_RESTART_PHP_FPM"
            ;;
    esac

    if ! command -v systemctl >/dev/null 2>&1; then
        if [[ "${DEPLOY_RESTART_PHP_FPM,,}" == "auto" ]]; then
            log_warning "systemctl nao encontrado; PHP-FPM nao foi reiniciado."
            return
        fi

        die "DEPLOY_RESTART_PHP_FPM esta habilitado, mas systemctl nao foi encontrado."
    fi

    if ! systemctl cat "$PHP_FPM_SERVICE" >/dev/null 2>&1; then
        if [[ "${DEPLOY_RESTART_PHP_FPM,,}" == "auto" ]]; then
            log_warning "Servico $PHP_FPM_SERVICE nao encontrado; PHP-FPM nao foi reiniciado."
            return
        fi

        die "Servico PHP-FPM nao encontrado: $PHP_FPM_SERVICE"
    fi

    if ! systemctl is-active --quiet "$PHP_FPM_SERVICE" && ! systemctl is-enabled --quiet "$PHP_FPM_SERVICE"; then
        if [[ "${DEPLOY_RESTART_PHP_FPM,,}" == "auto" ]]; then
            log_warning "Servico $PHP_FPM_SERVICE nao esta ativo/habilitado; PHP-FPM nao foi reiniciado."
            return
        fi
    fi

    if ! can_run_privileged; then
        if [[ "${DEPLOY_RESTART_PHP_FPM,,}" == "auto" ]]; then
            log_warning "Sem permissao sudo nao-interativa para reiniciar $PHP_FPM_SERVICE."
            return
        fi

        die "DEPLOY_RESTART_PHP_FPM esta habilitado, mas o usuario nao pode reiniciar $PHP_FPM_SERVICE."
    fi

    run_privileged systemctl restart "$PHP_FPM_SERVICE" || die "Falha ao reiniciar $PHP_FPM_SERVICE."
}

require_command git
require_command flock
require_command "$PHP_BIN"
require_command "$COMPOSER_BIN"

if is_enabled "$DEPLOY_BUILD_ASSETS"; then
    require_command "$NPM_BIN"
fi

LOCK_DIR="$(dirname "$DEPLOY_LOCK_FILE")"
mkdir -p "$LOCK_DIR"
exec 9>"$DEPLOY_LOCK_FILE"
flock -n 9 || die "Ja existe outro deploy em andamento."

git rev-parse --is-inside-work-tree >/dev/null 2>&1 || die "Este diretorio nao e um repositorio Git."
[[ -f "$ROOT_DIR/.env" ]] || die "Arquivo .env nao encontrado na raiz do projeto."

CURRENT_BRANCH="$(git branch --show-current)"
[[ -n "$CURRENT_BRANCH" ]] || die "O repositorio esta em detached HEAD; selecione um branch de deploy."
DEPLOY_BRANCH="${DEPLOY_BRANCH:-$CURRENT_BRANCH}"
[[ "$CURRENT_BRANCH" == "$DEPLOY_BRANCH" ]] || die "O branch atual e '$CURRENT_BRANCH', mas DEPLOY_BRANCH e '$DEPLOY_BRANCH'."

if [[ -n "$(git status --porcelain --untracked-files=no)" ]]; then
    git status --short --untracked-files=no
    die "Existem alteracoes locais rastreadas; o deploy foi interrompido."
fi

log_step "Atualizando codigo Git ($GIT_REMOTE/$DEPLOY_BRANCH)"
git fetch --prune "$GIT_REMOTE" "$DEPLOY_BRANCH"
git show-ref --verify --quiet "refs/remotes/$GIT_REMOTE/$DEPLOY_BRANCH" || die "Branch remoto nao encontrado: $GIT_REMOTE/$DEPLOY_BRANCH"
git merge --ff-only "$GIT_REMOTE/$DEPLOY_BRANCH"

log_step "Instalando dependencias PHP"
"$COMPOSER_BIN" install --no-dev --no-interaction --prefer-dist --optimize-autoloader

if is_enabled "$DEPLOY_BUILD_ASSETS"; then
    log_step "Instalando dependencias Node e compilando assets"
    "$NPM_BIN" ci --include=dev
    "$NPM_BIN" run build
fi

if is_enabled "$DEPLOY_RUN_MIGRATIONS"; then
    log_step "Aplicando migrations"
    artisan migrate --force
fi

log_step "Atualizando assets do Filament"
artisan filament:upgrade

if [[ ! -e "$ROOT_DIR/public/storage" && ! -L "$ROOT_DIR/public/storage" ]]; then
    log_step "Criando link publico do storage"
    artisan storage:link
fi

log_step "Limpando e recriando caches seguros"
artisan optimize:clear
artisan config:cache
artisan event:cache
artisan view:cache

log_step "Reiniciando workers"
artisan queue:restart
artisan schedule:interrupt
refresh_supervisor

if [[ "${DEPLOY_RESTART_PHP_FPM,,}" != "0" && "${DEPLOY_RESTART_PHP_FPM,,}" != "false" && "${DEPLOY_RESTART_PHP_FPM,,}" != "no" && "${DEPLOY_RESTART_PHP_FPM,,}" != "nao" ]]; then
    log_step "Reiniciando PHP-FPM"
    restart_php_fpm
fi

printf '\nDeploy concluido com sucesso no commit %s.\n' "$(git rev-parse --short HEAD)"
