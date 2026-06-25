#!/usr/bin/env bash
# Restore local pk_mar_ts_l from a dev-server mysqldump OR MySQL binlog (point-in-time).
#
# Usage:
#   ./scripts/restore-local-database.sh --from-dump /path/to/backup.sql
#   ./scripts/restore-local-database.sh --from-dev   # prints SSH/mysqldump steps
#   ./scripts/restore-local-database.sh --from-binlog # needs sudo to read binlog files
#
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

DB_NAME="${DB_NAME:-pk_mar_ts_l}"
MYSQL_USER="${MYSQL_USER:-root}"
MYSQL_PASS="${MYSQL_PASS:-}"
BINLOG_STOP="${BINLOG_STOP:-232963}"  # position before first migrate:fresh DROP on 2026-06-25
DATADIR="${DATADIR:-/usr/local/mysql/data}"

mysql_cmd() {
  if [[ -n "$MYSQL_PASS" ]]; then
    mysql -u "$MYSQL_USER" -p"$MYSQL_PASS" "$@"
  else
    mysql -u "$MYSQL_USER" "$@"
  fi
}

restore_from_dump() {
  local dump="$1"
  if [[ ! -f "$dump" ]]; then
    echo "ERROR: dump file not found: $dump" >&2
    exit 1
  fi
  echo "Restoring $DB_NAME from $dump ..."
  mysql_cmd -e "DROP DATABASE IF EXISTS \`$DB_NAME\`; CREATE DATABASE \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
  mysql_cmd "$DB_NAME" < "$dump"
  echo "Done. Verify with: mysql -u $MYSQL_USER $DB_NAME -e \"SELECT COUNT(*) FROM categories;\""
}

print_dev_steps() {
  cat <<'EOF'
=== Restore from DEV server (recommended) ===

Dev admin may have backups at:
  Admin → Business Settings → Database backup → Download

Or SSH to Hostinger and dump MySQL (replace credentials from dev .env):

  ssh u397782854@YOUR_HOSTINGER_HOST
  cd ~/domains/panunkaergar.com/dev   # or public_html/subdomains/dev
  grep ^DB_ .env
  mysqldump -u DB_USER -p DB_NAME > ~/pk_dev_backup.sql
  exit

Copy to your Mac, then:

  ./scripts/restore-local-database.sh --from-dump ~/pk_dev_backup.sql

EOF
}

restore_from_binlog() {
  echo "=== Binlog point-in-time restore (before PHPUnit wipe) ==="
  echo "Requires sudo to read $DATADIR/binlog.*"
  echo ""
  cat <<EOF
Run these commands manually (enter your Mac password when sudo asks):

  sudo mysqlbinlog --stop-position=$BINLOG_STOP \\
    $DATADIR/binlog.000201 $DATADIR/binlog.000202 \\
    | mysql -u $MYSQL_USER -p $DB_NAME

Note: binlog only contains changes since those logs started. If row counts are still low,
use --from-dev instead (full mysqldump from dev server).
EOF
}

case "${1:-}" in
  --from-dump)
    shift
    restore_from_dump "${1:?path to .sql dump required}"
    ;;
  --from-dev)
    print_dev_steps
    ;;
  --from-binlog)
    restore_from_binlog
    ;;
  *)
    echo "Restore local database ($DB_NAME)"
    echo ""
    echo "  $0 --from-dump /path/to/backup.sql"
    echo "  $0 --from-dev"
    echo "  $0 --from-binlog"
    ;;
esac
