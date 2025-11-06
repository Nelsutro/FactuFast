#!/bin/bash
set -euo pipefail

# -----------------------------------------------------------------------------
# FactuFast cPanel deployment helper
# -----------------------------------------------------------------------------
# This script is intended to be used as the "Deploy" command inside the
# Git Version Control feature provided by cPanel. It performs the following:
#   1. Installs PHP and Node dependencies when needed.
#   2. Ensures Laravel storage/bootstrap permissions are writable.
#   3. Runs the database migrations in production mode.
#   4. Builds the Angular frontend and publishes it to the public_html folder.
#
# Notes:
#   • Run this script from the repository root.
#   • Make sure the environment variables below match your account paths.
#   • The first execution requires that api/.env already exists with the
#     correct production credentials (DB, mail, APP_URL, etc.).
# -----------------------------------------------------------------------------

# ---- Configuration ----------------------------------------------------------
: "${ACCOUNT_HOME:=$HOME}"                           # Override if needed.
: "${PUBLIC_HTML:=$ACCOUNT_HOME/public_html}"        # Target for the SPA build.
: "${REPO_ROOT:=$(pwd)}"                             # Assumes script executed from repo root.
API_DIR="$REPO_ROOT/api"
SITE_DIR="$REPO_ROOT/site"

# PHP binary from cPanel's PHP Selector (adjust version if required).
: "${PHP_BIN:=php}"
: "${COMPOSER_BIN:=composer}"

# Node binary (ensure Node 18+ is selected in cPanel).
: "${NODE_BIN:=node}"
: "${NPM_BIN:=npm}"

# ---- Helper functions ------------------------------------------------------
log()  { printf '\n[%s] %s\n' "$(date '+%Y-%m-%d %H:%M:%S')" "$*"; }

section() {
  printf '\n============================================================\n'
  printf '%s\n' "$*"
  printf '============================================================\n\n'
}

ensure_directory() {
  if [ ! -d "$1" ]; then
    mkdir -p "$1"
  fi
}

# ---- Laravel API -----------------------------------------------------------
section "Installing Laravel dependencies"
cd "$API_DIR"

if [ ! -f composer.lock ]; then
  log "composer.lock not found; generating via composer install"
fi

$COMPOSER_BIN install --no-dev --optimize-autoloader --prefer-dist

if [ ! -f .env ]; then
  log "No .env found; copying from .env.example (remember to edit the credentials!)"
  cp .env.example .env
  $PHP_BIN artisan key:generate --force
fi

log "Updating storage symlink"
$PHP_BIN artisan storage:link || true

log "Setting storage/bootstrap permissions"
find storage -type d -exec chmod 775 {} \;
find storage -type f -exec chmod 664 {} \;
find bootstrap/cache -type d -exec chmod 775 {} \;
find bootstrap/cache -type f -exec chmod 664 {} \;

section "Migrating database"
$PHP_BIN artisan migrate --force

section "Caching Laravel configuration"
$PHP_BIN artisan config:cache
$PHP_BIN artisan route:cache
$PHP_BIN artisan view:cache

# ---- Angular Frontend ------------------------------------------------------
if [ -d "$SITE_DIR" ]; then
  section "Building Angular frontend"
  cd "$SITE_DIR"

  if [ -f package-lock.json ]; then
    $NPM_BIN ci
  else
    $NPM_BIN install
  fi

  $NPM_BIN run build -- --configuration=production

  section "Publishing Angular build to ${PUBLIC_HTML}"
  ensure_directory "$PUBLIC_HTML"

  rsync -a --delete "${SITE_DIR}/dist/site/browser/" "$PUBLIC_HTML/"
else
  log "Angular site directory not found; skipping frontend build"
fi

# ---- Wrap up ---------------------------------------------------------------
section "Deployment completed successfully"
