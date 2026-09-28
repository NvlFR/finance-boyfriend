#!/usr/bin/env bash

set -Eeuo pipefail

readonly SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd -P)"
readonly DEPLOY_TARGET="${DEPLOY_TARGET:-axiomsystems@137.59.126.212}"
readonly DEPLOY_PATH="${DEPLOY_PATH:-/var/www/finance-couple-apps}"
readonly DEPLOY_URL="${DEPLOY_URL:-https://finance-couple-apps.axiomsystemsco.com}"
readonly PHP_FPM_SERVICE="${PHP_FPM_SERVICE:-php8.3-fpm}"

DRY_RUN=false
SKIP_CHECKS=false
ALLOW_DIRTY=false
ALLOW_SAME_VERSION=false
ASSUME_YES=false
REMOTE_ASSET_STAGING=""
DEPLOY_LOCK_ACQUIRED=false
DEPLOY_MUTATION_STARTED=false

log_info() {
    printf '[%s] INFO  %s\n' "$(date +'%Y-%m-%d %H:%M:%S')" "$*"
}

log_warn() {
    printf '[%s] WARN  %s\n' "$(date +'%Y-%m-%d %H:%M:%S')" "$*" >&2
}

log_error() {
    printf '[%s] ERROR %s\n' "$(date +'%Y-%m-%d %H:%M:%S')" "$*" >&2
}

handle_error() {
    local -r exit_code=$?

    log_error "Deployment gagal pada baris ${BASH_LINENO[0]}."
    if [[ "$DEPLOY_MUTATION_STARTED" == true ]]; then
        ssh -o BatchMode=yes "$DEPLOY_TARGET" bash -s -- "$DEPLOY_PATH" <<'REMOTE' || log_error "Gagal memastikan maintenance; periksa server secara manual."
set -Eeuo pipefail
readonly app_dir="$1"
[[ "$app_dir" =~ ^/var/www/[A-Za-z0-9._-]+$ ]]
cd "$app_dir"
php artisan down --retry=30 --no-ansi
REMOTE
    fi
    if [[ -n "$REMOTE_ASSET_STAGING" ]]; then
        log_warn "Folder aset staging ditinggalkan untuk pemeriksaan: $REMOTE_ASSET_STAGING"
    fi
    log_warn "Periksa backup di $DEPLOY_PATH/storage/app/backups sebelum melakukan rollback."
    log_warn "Jika maintenance sudah aktif, biarkan tetap aktif sampai kode, database, dan aset dinyatakan konsisten."

    exit "$exit_code"
}

trap handle_error ERR

cleanup() {
    if [[ "$DEPLOY_LOCK_ACQUIRED" == true ]]; then
        ssh -o BatchMode=yes "$DEPLOY_TARGET" bash -s -- "$DEPLOY_PATH" <<'REMOTE' >/dev/null 2>&1 || true
set -Eeuo pipefail
readonly app_dir="$1"

[[ "$app_dir" =~ ^/var/www/[A-Za-z0-9._-]+$ ]]
rmdir "$app_dir/storage/framework/deploy.lock"
REMOTE
    fi
}

trap cleanup EXIT

usage() {
    cat <<'EOF'
Deploy Couple Finance ke production dengan backup dan health check.

Penggunaan:
  ./deploy-production.sh [opsi]

Opsi:
  --dry-run             Tampilkan simulasi sinkronisasi tanpa mengubah server.
  --skip-checks         Lewati test, lint, dan type-check lokal.
  --allow-dirty         Izinkan deployment dari Git worktree yang belum bersih.
  --allow-same-version  Izinkan versi lokal sama dengan versi production.
  --yes                 Lewati konfirmasi interaktif.
  -h, --help            Tampilkan bantuan.

Environment opsional:
  DEPLOY_TARGET         Default: axiomsystems@137.59.126.212
  DEPLOY_PATH           Default: /var/www/finance-couple-apps
  DEPLOY_URL            Default: https://finance-couple-apps.axiomsystemsco.com
  PHP_FPM_SERVICE       Default: php8.3-fpm
EOF
}

parse_arguments() {
    while [[ $# -gt 0 ]]; do
        case "$1" in
            --dry-run)
                DRY_RUN=true
                ;;
            --skip-checks)
                SKIP_CHECKS=true
                ;;
            --allow-dirty)
                ALLOW_DIRTY=true
                ;;
            --allow-same-version)
                ALLOW_SAME_VERSION=true
                ;;
            --yes)
                ASSUME_YES=true
                ;;
            -h|--help)
                usage
                exit 0
                ;;
            *)
                log_error "Opsi tidak dikenal: $1"
                usage >&2
                exit 1
                ;;
        esac
        shift
    done
}

require_command() {
    local -r command_name="$1"

    command -v "$command_name" >/dev/null 2>&1 || {
        log_error "Command lokal tidak tersedia: $command_name"
        return 1
    }
}

validate_environment() {
    local command_name

    for command_name in bash curl git npm php rsync ssh; do
        require_command "$command_name"
    done

    [[ "$DEPLOY_PATH" =~ ^/var/www/[A-Za-z0-9._-]+$ ]] || {
        log_error "DEPLOY_PATH harus berupa satu direktori aplikasi langsung di bawah /var/www."
        return 1
    }
    [[ "$DEPLOY_URL" =~ ^https:// ]] || {
        log_error "DEPLOY_URL production wajib menggunakan HTTPS."
        return 1
    }
    [[ -f "$SCRIPT_DIR/artisan" && -f "$SCRIPT_DIR/config/releases.php" ]] || {
        log_error "Jalankan script dari repository Couple Finance yang lengkap."
        return 1
    }
}

read_local_version() {
    php -r '$release = require $argv[1]; echo $release["current_version"] ?? "";' \
        "$SCRIPT_DIR/config/releases.php"
}

read_remote_version() {
    ssh -o BatchMode=yes -o ConnectTimeout=15 "$DEPLOY_TARGET" bash -s -- "$DEPLOY_PATH" <<'REMOTE'
set -Eeuo pipefail
readonly app_dir="$1"

[[ -f "$app_dir/config/releases.php" ]] || {
    printf '0.0.0'
    exit 0
}

php -r '$release = require $argv[1]; echo $release["current_version"] ?? "0.0.0";' \
    "$app_dir/config/releases.php"
REMOTE
}

validate_release() {
    local -r local_version="$1"
    local -r remote_version="$2"
    local first_release_version

    [[ "$local_version" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]] || {
        log_error "Versi lokal tidak mengikuti Semantic Versioning: $local_version"
        return 1
    }

    first_release_version="$(php -r '$release = require $argv[1]; echo $release["items"][0]["version"] ?? "";' \
        "$SCRIPT_DIR/config/releases.php")"
    [[ "$first_release_version" == "$local_version" ]] || {
        log_error "Item rilis terbaru ($first_release_version) tidak sama dengan current_version ($local_version)."
        return 1
    }

    if [[ "$local_version" == "$remote_version" && "$ALLOW_SAME_VERSION" != true ]]; then
        log_error "Versi $local_version sudah aktif di production. Naikkan versi atau pakai --allow-same-version."
        return 1
    fi

    if [[ "$local_version" != "$remote_version" ]] && ! php -r 'exit(version_compare($argv[1], $argv[2], ">") ? 0 : 1);' \
        "$local_version" "$remote_version"; then
        log_error "Versi lokal $local_version tidak boleh lebih rendah dari production $remote_version."
        return 1
    fi
}

validate_git_state() {
    if [[ "$ALLOW_DIRTY" == true ]]; then
        log_warn "Deployment diizinkan dari worktree yang belum bersih."
        return 0
    fi

    if [[ -n "$(git -C "$SCRIPT_DIR" status --short)" ]]; then
        log_error "Git worktree belum bersih. Commit perubahan atau pakai --allow-dirty secara sadar."
        return 1
    fi
}

run_local_checks() {
    if [[ "$SKIP_CHECKS" == true ]]; then
        log_warn "Test, lint, dan type-check lokal dilewati."
        npm --prefix "$SCRIPT_DIR" run build
        return 0
    fi

    log_info "Menjalankan pemeriksaan lokal."
    (
        cd "$SCRIPT_DIR"
        composer lint:check
        composer types:check
        npm run lint:check
        npm run format:check
        npm run types:check
        php artisan test --compact
        npm run build
    )
}

run_remote_preflight() {
    ssh -o BatchMode=yes -o ConnectTimeout=15 "$DEPLOY_TARGET" bash -s -- "$DEPLOY_PATH" <<'REMOTE'
set -Eeuo pipefail
readonly app_dir="$1"

[[ "$app_dir" =~ ^/var/www/[A-Za-z0-9._-]+$ ]]
[[ -d "$app_dir" ]]
[[ -f "$app_dir/artisan" ]]
[[ -f "$app_dir/.env" ]]
[[ -d "$app_dir/storage" ]]
[[ -d "$app_dir/bootstrap/cache" ]]

for command_name in composer mysqldump php tar; do
    command -v "$command_name" >/dev/null 2>&1 || {
        printf 'Command remote tidak tersedia: %s\n' "$command_name" >&2
        exit 1
    }
done

cd "$app_dir"
php artisan about --only=environment --no-ansi >/dev/null
php artisan migrate:status --no-ansi >/dev/null
REMOTE
}

create_remote_backups() {
    ssh -o BatchMode=yes "$DEPLOY_TARGET" bash -s -- "$DEPLOY_PATH" <<'REMOTE'
set -Eeuo pipefail
readonly app_dir="$1"
readonly timestamp="$(date +%Y%m%d-%H%M%S)"
readonly backup_dir="$app_dir/storage/app/backups"

[[ "$app_dir" =~ ^/var/www/[A-Za-z0-9._-]+$ ]]
mkdir -p "$backup_dir"
cd "$app_dir"

DEPLOY_BACKUP_DIR="$backup_dir" DEPLOY_BACKUP_TIMESTAMP="$timestamp" php <<'PHP'
<?php

require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$connectionName = config('database.default');
$connection = config("database.connections.{$connectionName}");

if (($connection['driver'] ?? null) !== 'mysql') {
    throw new RuntimeException('Backup otomatis saat ini hanya mendukung MySQL.');
}

$backupPath = sprintf(
    '%s/pre-deploy-%s.sql',
    rtrim((string) getenv('DEPLOY_BACKUP_DIR'), '/'),
    (string) getenv('DEPLOY_BACKUP_TIMESTAMP'),
);
$backupHandle = fopen($backupPath, 'wb');

if ($backupHandle === false) {
    throw new RuntimeException("Tidak dapat membuat backup: {$backupPath}");
}

$command = [
    'mysqldump',
    '--single-transaction',
    '--quick',
    '--skip-lock-tables',
    '--host='.(string) ($connection['host'] ?? '127.0.0.1'),
    '--port='.(string) ($connection['port'] ?? 3306),
    '--user='.(string) ($connection['username'] ?? ''),
    (string) ($connection['database'] ?? ''),
];
$pipes = [];
$process = proc_open(
    $command,
    [STDIN, $backupHandle, STDERR],
    $pipes,
    null,
    [
        'MYSQL_PWD' => (string) ($connection['password'] ?? ''),
        'PATH' => (string) getenv('PATH'),
    ],
);

if (! is_resource($process)) {
    fclose($backupHandle);
    throw new RuntimeException('Gagal menjalankan mysqldump.');
}

$exitCode = proc_close($process);
fclose($backupHandle);

if ($exitCode !== 0 || ! is_file($backupPath) || filesize($backupPath) === 0) {
    throw new RuntimeException('Backup database gagal atau kosong.');
}

chmod($backupPath, 0600);
printf("database_backup=%s sha256=%s\n", basename($backupPath), hash_file('sha256', $backupPath));
PHP

code_backup="$backup_dir/code-pre-deploy-$timestamp.tar.gz"
tar \
    --exclude='./.env' \
    --exclude='./.git' \
    --exclude='./bootstrap/cache' \
    --exclude='./database/*.sqlite' \
    --exclude='./node_modules' \
    --exclude='./public/build' \
    --exclude='./storage' \
    --exclude='./vendor' \
    -czf "$code_backup" .
chmod 600 "$code_backup"
printf 'code_backup=%s sha256=%s\n' "$(basename "$code_backup")" "$(sha256sum "$code_backup" | cut -d' ' -f1)"
REMOTE
}

sync_application_source() {
    local -a rsync_options=(
        --archive
        --checksum
        --human-readable
        --exclude=.ai/
        --exclude=.env
        --exclude=.git/
        --exclude=.github/
        --exclude=.idea/
        --exclude=.vscode/
        --exclude=bootstrap/cache/
        --exclude='database/*.sqlite'
        --exclude=node_modules/
        --exclude=public/build/
        --exclude='public/build-deploy-*'
        --exclude=public/fonts-manifest.dev.json
        --exclude=public/hot
        --exclude=public/storage
        --exclude=storage/
        --exclude=tests/
        --exclude=vendor/
    )

    if [[ "$DRY_RUN" == true ]]; then
        rsync_options+=(--dry-run --itemize-changes)
    fi

    rsync "${rsync_options[@]}" "$SCRIPT_DIR/" "$DEPLOY_TARGET:$DEPLOY_PATH/"
}

upload_built_assets() {
    REMOTE_ASSET_STAGING="$(ssh -o BatchMode=yes "$DEPLOY_TARGET" bash -s -- "$DEPLOY_PATH" <<'REMOTE'
set -Eeuo pipefail
readonly app_dir="$1"

[[ "$app_dir" =~ ^/var/www/[A-Za-z0-9._-]+$ ]]
cd "$app_dir"
mktemp -d public/build-deploy-XXXXXX
REMOTE
)"

    [[ "$REMOTE_ASSET_STAGING" =~ ^public/build-deploy-[A-Za-z0-9]+$ ]]
    rsync --archive --checksum --human-readable \
        "$SCRIPT_DIR/public/build/" \
        "$DEPLOY_TARGET:$DEPLOY_PATH/$REMOTE_ASSET_STAGING/"
}

enable_remote_maintenance() {
    ssh -o BatchMode=yes "$DEPLOY_TARGET" bash -s -- "$DEPLOY_PATH" <<'REMOTE'
set -Eeuo pipefail
readonly app_dir="$1"
[[ "$app_dir" =~ ^/var/www/[A-Za-z0-9._-]+$ ]]
cd "$app_dir"
if [[ -f storage/framework/down ]]; then
    printf 'Aplikasi sudah maintenance; periksa deployment sebelumnya terlebih dahulu.\n' >&2
    exit 1
fi
php artisan down --retry=30 --no-ansi
REMOTE
}

finalize_remote_deployment() {
    ssh -o BatchMode=yes "$DEPLOY_TARGET" bash -s -- \
        "$DEPLOY_PATH" "$REMOTE_ASSET_STAGING" "$PHP_FPM_SERVICE" <<'REMOTE'
set -Eeuo pipefail
readonly app_dir="$1"
readonly asset_staging="$2"
readonly php_fpm_service="$3"
readonly timestamp="$(date +%Y%m%d-%H%M%S)"
readonly asset_backup="$app_dir/storage/app/backups/assets-pre-deploy-$timestamp"
[[ "$app_dir" =~ ^/var/www/[A-Za-z0-9._-]+$ ]]
[[ "$asset_staging" =~ ^public/build-deploy-[A-Za-z0-9]+$ ]]
cd "$app_dir"
[[ -s "$asset_staging/manifest.json" ]]

[[ -f storage/framework/down ]]

composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-progress
php artisan migrate --force --no-ansi

if [[ -d public/build ]]; then
    mv public/build "$asset_backup"
fi

if ! mv "$asset_staging" public/build; then
    if [[ -d "$asset_backup" && ! -d public/build ]]; then
        mv "$asset_backup" public/build
    fi
    exit 1
fi

php artisan optimize:clear --no-ansi
php artisan optimize --no-ansi
php artisan migrate:status --no-ansi >/dev/null
php artisan about --only=environment --no-ansi >/dev/null

if sudo -n systemctl reload "$php_fpm_service"; then
    printf 'php_fpm=%s reloaded\n' "$php_fpm_service"
else
    printf 'PHP-FPM tidak dapat direload tanpa password; lanjut dengan cache aplikasi baru.\n' >&2
fi

php artisan up --no-ansi

printf 'asset_backup=%s\n' "$(basename "$asset_backup")"
REMOTE

    REMOTE_ASSET_STAGING=""
}

run_health_checks() {
    local endpoint

    for endpoint in /up / /login; do
        curl --fail --silent --show-error --location --max-time 20 \
            --output /dev/null "$DEPLOY_URL$endpoint"
        log_info "Health check berhasil: $DEPLOY_URL$endpoint"
    done

    ssh -o BatchMode=yes "$DEPLOY_TARGET" bash -s -- "$DEPLOY_PATH" <<'REMOTE'
set -Eeuo pipefail
readonly app_dir="$1"

cd "$app_dir"
php artisan migrate:status --no-ansi >/dev/null
php artisan about --only=environment --no-ansi >/dev/null
REMOTE
}

confirm_deployment() {
    local -r local_version="$1"
    local -r remote_version="$2"
    local answer

    if [[ "$ASSUME_YES" == true ]]; then
        return 0
    fi

    if [[ ! -t 0 ]]; then
        log_error "Konfirmasi interaktif tidak tersedia. Gunakan --yes untuk automation."
        return 1
    fi

    printf '\nTarget       : %s\nPath         : %s\nURL          : %s\nVersi        : %s -> %s\n' \
        "$DEPLOY_TARGET" "$DEPLOY_PATH" "$DEPLOY_URL" "$remote_version" "$local_version"
    read -r -p 'Lanjutkan deployment production? [y/N] ' answer
    [[ "$answer" == 'y' || "$answer" == 'Y' ]]
}

acquire_deploy_lock() {
    ssh -o BatchMode=yes "$DEPLOY_TARGET" bash -s -- "$DEPLOY_PATH" <<'REMOTE'
set -Eeuo pipefail
readonly app_dir="$1"
readonly lock_dir="$app_dir/storage/framework/deploy.lock"

[[ "$app_dir" =~ ^/var/www/[A-Za-z0-9._-]+$ ]]
if ! mkdir "$lock_dir" 2>/dev/null; then
    printf 'Deployment lain sedang berjalan atau lock lama belum dibersihkan: %s\n' "$lock_dir" >&2
    exit 1
fi
REMOTE

    DEPLOY_LOCK_ACQUIRED=true
}

main() {
    local local_version
    local remote_version

    parse_arguments "$@"
    validate_environment
    cd "$SCRIPT_DIR"

    local_version="$(read_local_version)"
    remote_version="$(read_remote_version)"
    validate_release "$local_version" "$remote_version"
    validate_git_state

    log_info "Versi production: $remote_version; versi yang akan dirilis: $local_version."
    run_local_checks
    run_remote_preflight

    if [[ "$DRY_RUN" == true ]]; then
        log_info "Menjalankan simulasi rsync; server tidak akan diubah."
        sync_application_source
        log_info "Dry-run selesai. Tidak ada backup, migration, atau perubahan production."
        exit 0
    fi

    confirm_deployment "$local_version" "$remote_version"
    acquire_deploy_lock
    log_info "Mengaktifkan maintenance sebelum backup dan sinkronisasi kode."
    enable_remote_maintenance
    DEPLOY_MUTATION_STARTED=true
    log_info "Membuat backup database dan kode production."
    create_remote_backups
    log_info "Menyinkronkan source tanpa menyentuh data runtime."
    sync_application_source
    log_info "Mengunggah aset build ke staging."
    upload_built_assets
    log_info "Menjalankan migration dan aktivasi aset dalam maintenance singkat."
    finalize_remote_deployment
    log_info "Menjalankan health check production."
    run_health_checks
    log_info "Deployment versi $local_version berhasil."
}

if [[ "${BASH_SOURCE[0]}" == "$0" ]]; then
    main "$@"
fi
