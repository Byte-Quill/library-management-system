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

if [ -n "${DB_URL:-}" ]; then
  echo "Using DB_URL from .env"
  python3 - "$DB_URL" <<'PY'
import sys
from urllib.parse import urlparse
u = urlparse(sys.argv[1])
print(f"DB_HOST={u.hostname}")
print(f"DB_PORT={u.port or 3306}")
print(f"DB_NAME={u.path.lstrip('/')}")
print(f"DB_USER={u.username}")
print(f"DB_PASS={u.password}")
PY
  exit 0
fi

# Create database if needed
if [ -n "$PASSWORD" ]; then
  MYSQL_CMD=(mysql -h "$HOST" -P "$PORT" -u "$USER" -p"$PASSWORD" -e "CREATE DATABASE IF NOT EXISTS \`$DATABASE\`;" )
else
  MYSQL_CMD=(mysql -h "$HOST" -P "$PORT" -u "$USER" -e "CREATE DATABASE IF NOT EXISTS \`$DATABASE\`;" )
fi
"${MYSQL_CMD[@]}"

if [ -n "$PASSWORD" ]; then
  mysql -h "$HOST" -P "$PORT" -u "$USER" -p"$PASSWORD" "$DATABASE" < database/001_schema.sql
  mysql -h "$HOST" -P "$PORT" -u "$USER" -p"$PASSWORD" "$DATABASE" < database/002_seed.sql
else
  mysql -h "$HOST" -P "$PORT" -u "$USER" "$DATABASE" < database/001_schema.sql
  mysql -h "$HOST" -P "$PORT" -u "$USER" "$DATABASE" < database/002_seed.sql
fi

echo "Database bootstrap complete."
