#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."

if [ ! -f .env ]; then
  echo ".env not found. Copy .env.example to .env and fill in your database values first."
  exit 1
fi

if ! command -v mysql >/dev/null 2>&1; then
  echo "mysql client not found. Install MySQL/MariaDB client tools first."
  exit 1
fi

# Load environment variables for DB connection info
set -a
source .env
set +a

HOST="${DB_HOST:-127.0.0.1}"
PORT="${DB_PORT:-3306}"
DATABASE="${DB_NAME:-digital_library}"
USER="${DB_USER:-root}"
PASSWORD="${DB_PASS:-}"

# If DB_URL is provided, parse it into the connection variables.
if [ -n "${DB_URL:-}" ]; then
  echo "Using DB_URL from .env"
  eval "$(python3 - "$DB_URL" <<'PY'
import sys
from urllib.parse import urlparse
u = urlparse(sys.argv[1])
print(f"HOST={u.hostname or '127.0.0.1'}")
print(f"PORT={u.port or 3306}")
print(f"DATABASE={u.path.lstrip('/')}")
print(f"USER={u.username or ''}")
print(f"PASSWORD={u.password or ''}")
PY
)"
fi

if [ -z "$DATABASE" ]; then
  echo "Could not determine database name. Set DB_NAME or DB_URL in .env."
  exit 1
fi

run_mysql() {
  if [ -n "$PASSWORD" ]; then
    mysql -h "$HOST" -P "$PORT" -u "$USER" -p"$PASSWORD" "$@"
  else
    mysql -h "$HOST" -P "$PORT" -u "$USER" "$@"
  fi
}

# Create database if needed
run_mysql -e "CREATE DATABASE IF NOT EXISTS \`$DATABASE\`;"

# Import schema and seed data
run_mysql "$DATABASE" < database/001_schema.sql
run_mysql "$DATABASE" < database/002_seed.sql

echo "Database bootstrap complete."
