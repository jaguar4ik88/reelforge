#!/usr/bin/env bash
#
# ReelForge production deploy (run on the server after code update).
# Usage: from repo root — bash scripts/deploy.sh
#
# Environment (optional):
#   SKIP_GIT_PULL=1     — do not fetch/merge git (e.g. CI already synced files)
#   USE_DOCKER=1        — run `docker compose` restart for app, nginx, queue (see below)
#   COPY_FRONTEND_TO=   — if set, rsync frontend/dist/ to this directory (trailing slash ok)
#
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

log() { printf '\n[%s] %s\n' "$(date -u +%Y-%m-%dT%H:%M:%SZ)" "$*"; }

if [[ "${SKIP_GIT_PULL:-0}" != "1" ]]; then
  log "Git: fetch and align with origin/main"
  git fetch origin main
  git checkout -B main "origin/main"
else
  log "Git: skipped (SKIP_GIT_PULL=1)"
fi

log "Backend: composer install"
cd "$ROOT/backend"
composer install --no-dev --optimize-autoloader --no-interaction

log "Frontend: npm ci and build"
cd "$ROOT/frontend"
if [[ ! -f .env ]] && [[ -f .env.production ]]; then
  log "Note: copy .env.production to .env or export VITE_* before build if needed"
fi
npm ci
npm run build

log "Copy frontend dist → backend/public (SPA + Laravel same host)"
cp -a "${ROOT}/frontend/dist/." "${ROOT}/backend/public/"

if [[ -n "${COPY_FRONTEND_TO:-}" ]]; then
  log "Copy frontend dist → ${COPY_FRONTEND_TO}"
  mkdir -p "$COPY_FRONTEND_TO"
  rsync -a --delete "${ROOT}/frontend/dist/" "${COPY_FRONTEND_TO%/}/"
fi

log "Laravel: migrate and optimize"
cd "$ROOT/backend"
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart

if [[ "${USE_DOCKER:-0}" == "1" ]]; then
  log "Docker: restart services"
  cd "$ROOT"
  if docker compose version &>/dev/null; then
    docker compose restart app nginx queue 2>/dev/null || docker compose restart
  elif command -v docker-compose &>/dev/null; then
    docker-compose restart app nginx queue 2>/dev/null || docker-compose restart
  else
    log "WARN: USE_DOCKER=1 but docker compose not found"
  fi
fi

log "Deploy finished OK"
