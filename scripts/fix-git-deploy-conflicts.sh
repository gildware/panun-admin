#!/usr/bin/env bash
# Remove untracked deploy files that block Hostinger/git "pull" (would be overwritten by merge).
# Run once on the server via SSH, then redeploy from hPanel.
#
# Usage:
#   cd /home/u397782854/domains/panunkaergar.com/dev
#   bash scripts/fix-git-deploy-conflicts.sh
#   git pull origin dev

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

if [[ ! -d .git ]]; then
  echo "Error: not a git repository: $ROOT"
  exit 1
fi

# Paths that were copied manually before landing in git; safe to drop if still untracked.
CONFLICT_PATHS=(
  deploy/soketi/keep-soketi-alive.sh
)

removed=0
for rel in "${CONFLICT_PATHS[@]}"; do
  if [[ -f "$rel" ]] && ! git ls-files --error-unmatch "$rel" &>/dev/null; then
    echo "==> Removing untracked file blocking pull: $rel"
    rm -f "$rel"
    removed=$((removed + 1))
  fi
done

if [[ "$removed" -eq 0 ]]; then
  echo "==> No known untracked conflicts found. If pull still fails, check:"
  git status --short
  exit 0
fi

echo "==> Removed $removed file(s). Run: git pull origin dev"
echo "==> Then redeploy from Hostinger or continue with your deploy script."
