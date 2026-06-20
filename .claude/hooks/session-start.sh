#!/bin/bash
set -euo pipefail

# Only run in remote Claude Code environments
if [ "${CLAUDE_CODE_REMOTE:-}" != "true" ]; then
  exit 0
fi

cd "$CLAUDE_PROJECT_DIR"

# Install PHP dependencies
composer install --no-interaction --prefer-dist --optimize-autoloader

# Set up .env if missing
if [ ! -f .env ]; then
  cp .env.example .env
  php artisan key:generate --ansi
fi

# Create SQLite database file if needed (for local DB_CONNECTION=sqlite)
if grep -q "^DB_CONNECTION=sqlite" .env 2>/dev/null && ! grep -q "^DB_DATABASE=:memory:" .env 2>/dev/null; then
  touch database/database.sqlite 2>/dev/null || true
fi

# Install JS dependencies
npm install --ignore-scripts
