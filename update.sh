#!/usr/bin/env bash
###############################################################################
# update.sh — Pull the latest code and refresh the live Glamon POS site.
#
# Usage (cPanel Terminal / SSH):
#   bash ~/Jewellery_POS/update.sh
#
# Unlike deploy.sh this does NOT re-generate APP_KEY (would log everyone out /
# break encrypted data) and does NOT re-seed the database.
###############################################################################
set -euo pipefail

# ── Configuration ────────────────────────────────────────────────────────────
REPO_NAME="Jewellery_POS"
BRANCH="glamon_pos"
PHP_BIN="${PHP_BIN:-php}"

APP_DIR="$HOME/$REPO_NAME"
PUBLIC_HTML="$HOME/public_html"
COMPOSER="$APP_DIR/composer.phar"

cd "${APP_DIR}"

# ── 1. Pull latest code (build files are committed, so they come down too) ────
echo "==> Pulling latest ${BRANCH}"
git fetch origin "${BRANCH}"
git reset --hard "origin/${BRANCH}"

# ── 2. Update PHP dependencies in case composer.json/lock changed ─────────────
echo "==> composer install (production)"
${PHP_BIN} -d memory_limit=-1 "${COMPOSER}" install \
    --no-dev \
    --prefer-dist \
    --optimize-autoloader \
    --no-interaction \
    --ignore-platform-req=ext-fileinfo

# ── 3. Re-sync public_html: static assets, front controller, symlinks ─────────
echo "==> Syncing public_html"
rm -f "${PUBLIC_HTML}/index.html"

# Symlink every static asset from public/ (css, js, images, build, ...) so new
# or renamed assets always get served. Specials are handled explicitly below.
for path in "${APP_DIR}/public/"*; do
    name="$(basename "${path}")"
    case "${name}" in
        index.php|.htaccess|storage|hot|build.zip) continue ;;
    esac
    rm -rf "${PUBLIC_HTML:?}/${name}"
    ln -sfn "${path}" "${PUBLIC_HTML}/${name}"
done

cp "${APP_DIR}/cpanel-index.php" "${PUBLIC_HTML}/index.php"
cp "${APP_DIR}/public/.htaccess" "${PUBLIC_HTML}/.htaccess"
rm -rf "${PUBLIC_HTML}/storage"
ln -sfn "${APP_DIR}/storage/app/public" "${PUBLIC_HTML}/storage"

# ── 4. Database migrations (no seeding on updates) ────────────────────────────
echo "==> Running migrations"
${PHP_BIN} artisan migrate --force

# ── 5. Refresh caches (clear all; cache views, and routes if possible) ────────
echo "==> Refreshing caches"
${PHP_BIN} artisan config:clear
${PHP_BIN} artisan cache:clear || true
${PHP_BIN} artisan route:clear
${PHP_BIN} artisan view:clear
${PHP_BIN} artisan view:cache
${PHP_BIN} artisan route:cache || echo "    (route:cache skipped — closure routes present)"

# ── 6. Writable permissions ───────────────────────────────────────────────────
chmod -R 775 "${APP_DIR}/storage" "${APP_DIR}/bootstrap/cache"

echo ""
echo "✅ Update complete — https://glamon.possystem.lk/"
