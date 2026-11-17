#!/bin/sh
# ============================================================
# Entrypoint PatriGest — déploiement mono-conteneur (Render)
# 1. Génère les .env de chaque service depuis les variables d'env
# 2. Attend PostgreSQL, crée les bases si absentes
# 3. Migrations + seed (idempotent) des deux services
# 4. Démarre supervisord (nginx + 2 php-fpm)
# ============================================================
set -e

AUTH_DIR=/var/www/auth
ASSET_DIR=/var/www/asset
DB_HOST="${DB_HOST:-localhost}"
DB_PORT="${DB_PORT:-5432}"
DB_USERNAME="${DB_USERNAME:-patri_user}"
DB_PASSWORD="${DB_PASSWORD:-}"
APP_URL_PUBLIC="${APP_URL:-http://localhost:10000}"
ASSET_URL="${ASSET_SERVICE_URL:-http://localhost:10000}"
AUTH_URL="${AUTH_SERVICE_URL:-http://localhost:10000}"
APP_ENV_DEPLOY="${APP_ENV:-production}"
APP_DEBUG_DEPLOY="${APP_DEBUG:-false}"

log() { echo "[entrypoint] $*"; }

# ---------- 1. Fichiers .env par service ----------
write_env() {
    dir="$1"; db="$2"; key="$3"; session_driver="$4"; extra="$5"
    cat > "$dir/.env" <<EOF
APP_NAME="PatriGest"
APP_ENV=$APP_ENV_DEPLOY
APP_KEY=$key
APP_DEBUG=$APP_DEBUG_DEPLOY
APP_URL=$APP_URL_PUBLIC
APP_LOCALE=fr
APP_FALLBACK_LOCALE=fr
APP_FAKER_LOCALE=fr_FR
APP_TIMEZONE=Africa/Lome
BCRYPT_ROUNDS=12
LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=${LOG_LEVEL:-info}
DB_CONNECTION=pgsql
DB_HOST=$DB_HOST
DB_PORT=$DB_PORT
DB_DATABASE=$db
DB_USERNAME=$DB_USERNAME
DB_PASSWORD=$DB_PASSWORD
SESSION_DRIVER=$session_driver
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
CACHE_STORE=file
MAIL_MAILER=log
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="PatriGest"
$extra
EOF
    chown www-data:www-data "$dir/.env"
}

# Génère une clé si absente (persistée dans le .env du conteneur)
log "Génération des fichiers .env"
# .env préliminaires pour permettre à artisan de démarrer
write_env "$AUTH_DIR" auth_service "${APP_KEY_AUTH:-${APP_KEY:-base64:placeholder}}" database "ASSET_SERVICE_URL=$ASSET_URL"
write_env "$ASSET_DIR" asset_db "${APP_KEY_ASSET:-${APP_KEY:-base64:placeholder}}" file "AUTH_SERVICE_URL=$AUTH_URL"

# Si les clés sont des placeholders, on les génère réellement
if [ -z "${APP_KEY_AUTH:-${APP_KEY:-}}" ]; then
    log "Génération APP_KEY auth-service"
    KEY_AUTH=$(cd "$AUTH_DIR" && su -s /bin/sh www-data -c "php artisan key:generate --show" | tail -1)
    write_env "$AUTH_DIR" auth_service "$KEY_AUTH" database "ASSET_SERVICE_URL=$ASSET_URL"
fi
if [ -z "${APP_KEY_ASSET:-${APP_KEY:-}}" ]; then
    log "Génération APP_KEY asset-service"
    KEY_ASSET=$(cd "$ASSET_DIR" && su -s /bin/sh www-data -c "php artisan key:generate --show" | tail -1)
    write_env "$ASSET_DIR" asset_db "$KEY_ASSET" file "AUTH_SERVICE_URL=$AUTH_URL"
fi

# ---------- 2. Attente PostgreSQL ----------
log "Attente de PostgreSQL ($DB_HOST:$DB_PORT)…"
i=0
until php -r '
    $h=getenv("DB_HOST") ?: "localhost";
    $p=getenv("DB_PORT") ?: "5432";
    $u=getenv("DB_USERNAME") ?: "patri_user";
    $w=getenv("DB_PASSWORD") ?: "";
    try { new PDO("pgsql:host=$h;port=$p;dbname=postgres", $u, $w); exit(0); }
    catch (Throwable $e) { exit(1); }
' 2>/dev/null; do
    i=$((i+1)); [ $i -gt 60 ] && { log "ERREUR: PostgreSQL injoignable"; exit 1; }
    sleep 2
done
log "PostgreSQL disponible"

# ---------- 3. Création des bases si absentes ----------
php -r '
    $h=getenv("DB_HOST") ?: "localhost";
    $p=getenv("DB_PORT") ?: "5432";
    $u=getenv("DB_USERNAME") ?: "patri_user";
    $w=getenv("DB_PASSWORD") ?: "";
    $pdo = new PDO("pgsql:host=$h;port=$p;dbname=postgres", $u, $w);
    foreach (["auth_service","asset_db"] as $db) {
        $exists = $pdo->query("SELECT 1 FROM pg_database WHERE datname=".$pdo->quote($db))->fetchColumn();
        if (!$exists) { $pdo->exec("CREATE DATABASE \"$db\""); echo "base $db créée\n"; }
    }
'

# ---------- 4. Migrations + seed ----------
log "Migrations auth-service"
(cd "$AUTH_DIR" && su -s /bin/sh www-data -c "php artisan migrate --force")
log "Migrations asset-service"
(cd "$ASSET_DIR" && su -s /bin/sh www-data -c "php artisan migrate --force")
log "Seed auth-service (utilisateurs de démo)"
(cd "$AUTH_DIR" && su -s /bin/sh www-data -c "php artisan db:seed --force") || log "Seed ignoré (déjà présent ou erreur)"

# ---------- 5. Démarrage ----------
log "Démarrage de supervisord (nginx + php-fpm auth/asset)"
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
