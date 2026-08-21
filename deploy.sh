#!/usr/bin/env bash
###############################################################################
# deploy.sh — First-time deployment of the Glamon POS (Jewellery_POS) Laravel
#             app onto cPanel shared hosting (no system Composer, no Node).
#
# Usage (cPanel Terminal / SSH):
#   GITHUB_TOKEN=ghp_xxx bash deploy.sh
#
# This script is idempotent and safe to re-run:
#   * an existing clone is hard-reset to origin/<branch>
#   * the seeders use firstOrCreate(), so data is not duplicated
###############################################################################
set -euo pipefail

# ── Configuration ────────────────────────────────────────────────────────────
GITHUB_USER="APPLANTICS"
REPO_NAME="Jewellery_POS"
BRANCH="glamon_pos"

# The token is read from the environment (NOT committed to the repo, so GitHub
# secret-scanning will not revoke it). Pass it on the command line:
#   GITHUB_TOKEN=ghp_xxx bash deploy.sh
GITHUB_TOKEN="${GITHUB_TOKEN:?GITHUB_TOKEN is required, e.g. GITHUB_TOKEN=ghp_xxx bash deploy.sh}"

# PHP binary. cPanel's default `php` may be too old for Laravel 12 (needs
# PHP >= 8.2). If `php -v` shows an older version, point this at a newer one,
# e.g.  PHP_BIN=/opt/cpanel/ea-php83/root/usr/bin/php bash deploy.sh
PHP_BIN="${PHP_BIN:-php}"

# Paths (cPanel: ~ is the account home, public_html is the doc root).
APP_DIR="$HOME/$REPO_NAME"
PUBLIC_HTML="$HOME/public_html"
COMPOSER="$APP_DIR/composer.phar"
REPO_URL="https://${GITHUB_TOKEN}@github.com/${GITHUB_USER}/${REPO_NAME}.git"

echo "==> Deploying ${GITHUB_USER}/${REPO_NAME} (${BRANCH})"
echo "    App dir     : ${APP_DIR}"
echo "    public_html : ${PUBLIC_HTML}"
echo "    PHP         : $(${PHP_BIN} -v | head -n1)"

# ── 1. Clone (or hard-update) the repository into ~/Jewellery_POS ─────────────
if [ -d "${APP_DIR}/.git" ]; then
    echo "==> Repository already present — resetting to origin/${BRANCH}"
    git -C "${APP_DIR}" remote set-url origin "${REPO_URL}"
    git -C "${APP_DIR}" fetch origin "${BRANCH}"
    git -C "${APP_DIR}" checkout "${BRANCH}"
    git -C "${APP_DIR}" reset --hard "origin/${BRANCH}"
else
    echo "==> Cloning into ${APP_DIR}"
    git clone --branch "${BRANCH}" "${REPO_URL}" "${APP_DIR}"
fi

cd "${APP_DIR}"

# ── 2. Install Composer locally (cPanel has no system-wide Composer) ──────────
if [ ! -f "${COMPOSER}" ]; then
    echo "==> Installing Composer -> ${COMPOSER}"
    ${PHP_BIN} -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    ${PHP_BIN} composer-setup.php --quiet --install-dir="${APP_DIR}" --filename=composer.phar
    rm -f composer-setup.php
fi

# ── 3. Install PHP dependencies (vendor/ is intentionally not in the repo) ─────
echo "==> composer install (production)"
${PHP_BIN} -d memory_limit=-1 "${COMPOSER}" install \
    --no-dev \
    --prefer-dist \
    --optimize-autoloader \
    --no-interaction \
    --ignore-platform-req=ext-fileinfo

# ── 4. Environment file ───────────────────────────────────────────────────────
echo "==> Preparing .env from .env.production"
cp "${APP_DIR}/.env.production" "${APP_DIR}/.env"
sed -i 's/\r$//' "${APP_DIR}/.env"     # strip Windows CRLF line endings
chmod 600 "${APP_DIR}/.env"            # keep DB credentials private on shared host

# ── 5. Application key (lives in .env — we deliberately never config:cache) ────
echo "==> Generating APP_KEY"
${PHP_BIN} artisan key:generate --force

# ── 6. Wire public_html -> the Laravel app ────────────────────────────────────
echo "==> Publishing public/ assets and front controller to public_html"
mkdir -p "${PUBLIC_HTML}"
rm -f "${PUBLIC_HTML}/index.html"                      # drop cPanel default page

# Symlink EVERY static asset from the app's public/ folder into public_html
# (css, js, images, fonts, build, favicon.ico, robots.txt, ...) so nothing 404s.
# index.php and .htaccess are handled specially below; storage is linked to the
# storage disk; build.zip / hot are dev leftovers we don't serve.
for path in "${APP_DIR}/public/"*; do
    name="$(basename "${path}")"
    case "${name}" in
        index.php|.htaccess|storage|hot|build.zip) continue ;;
    esac
    rm -rf "${PUBLIC_HTML:?}/${name}"
    ln -sfn "${path}" "${PUBLIC_HTML}/${name}"
done

# Front controller (points public_html at ~/Jewellery_POS) and rewrite rules.
cp "${APP_DIR}/cpanel-index.php" "${PUBLIC_HTML}/index.php"
cp "${APP_DIR}/public/.htaccess" "${PUBLIC_HTML}/.htaccess"

# Public storage disk (equivalent of `php artisan storage:link`).
rm -rf "${PUBLIC_HTML}/storage"
ln -sfn "${APP_DIR}/storage/app/public" "${PUBLIC_HTML}/storage"

# ── 7. Permissions (755 for app/dirs, 775 for writable paths) ─────────────────
echo "==> Setting permissions"
chmod -R 755 "${APP_DIR}"
chmod -R 775 "${APP_DIR}/storage" "${APP_DIR}/bootstrap/cache"
chmod 600 "${APP_DIR}/.env"
chmod -R go-rwx "${APP_DIR}/.git" 2>/dev/null || true   # hide token in .git/config
chmod 700 "${APP_DIR}/deploy.sh" "${APP_DIR}/update.sh" 2>/dev/null || true

# ── 8. Database: migrate + seed ───────────────────────────────────────────────
echo "==> Running migrations"
${PHP_BIN} artisan migrate --force
echo "==> Seeding database"
${PHP_BIN} artisan db:seed --force

# ── 9. Caches: clear everything, then cache views (+ routes if possible) ───────
# IMPORTANT: we never run `config:cache`. On shared hosting the cached config
# is generated before APP_KEY is read from .env, which breaks encryption/login.
echo "==> Clearing caches"
${PHP_BIN} artisan config:clear
${PHP_BIN} artisan cache:clear || true
${PHP_BIN} artisan route:clear
${PHP_BIN} artisan view:clear

echo "==> Caching views"
${PHP_BIN} artisan view:cache
echo "==> Caching routes"
# routes/web.php uses closure-based routes which cannot be serialized, so
# route:cache may fail here — that is expected and non-fatal.
${PHP_BIN} artisan route:cache || echo "    (route:cache skipped — closure routes present)"

echo ""
echo "✅ Deployment complete — visit https://glamon.possystem.lk/"
echo "   Future updates: GITHUB_TOKEN not required, just run:  bash ${APP_DIR}/update.sh"
