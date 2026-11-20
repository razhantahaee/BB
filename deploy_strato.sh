#!/usr/bin/env bash
# Push ./site to the Strato web space over SFTP (lftp).  Usage: ./deploy_strato.sh [--dry]
# Needs ~/.config/bb/strato.env (never commit it):
#   STRATO_HOST=ssh.strato.de   STRATO_USER=...   STRATO_PASS=...   STRATO_DIR=/      (remote folder the domain points to)
set -euo pipefail
cd "$(dirname "$0")"
ENVF="$HOME/.config/bb/strato.env"
[ -f "$ENVF" ] || { echo "missing $ENVF"; exit 1; }
# read KEY=VALUE literally (no shell parsing, so $ ! & etc. in the password are fine)
while IFS= read -r line || [ -n "$line" ]; do
  case "$line" in ''|\#*) continue;; esac
  k="${line%%=*}"; v="${line#*=}"; v="${v%$'\r'}"
  case "$k" in STRATO_HOST|STRATO_USER|STRATO_PASS|STRATO_DIR) printf -v "$k" '%s' "$v";; esac
done < "$ENVF"
: "${STRATO_HOST:?}" "${STRATO_USER:?}" "${STRATO_PASS:?}"; STRATO_DIR="${STRATO_DIR:-/}"
command -v lftp >/dev/null || { echo "install lftp:  sudo apt install lftp"; exit 1; }
DRY=""; [ "${1:-}" = "--dry" ] && DRY="--dry-run"
# never overwrite/delete on the server: config.php (secrets), uploads (user icons)
lftp -u "$STRATO_USER","$STRATO_PASS" "sftp://$STRATO_HOST" 2>&1 <<EOF | sed -E 's#sftp://[^@ ]*@#sftp://***@#g'
set sftp:auto-confirm yes
set net:max-retries 2
set net:timeout 20
mirror -R --verbose $DRY \
  --exclude-glob api/config.php --exclude-glob uploads/ --exclude-glob CLAUDE.md --exclude-glob '*.zip' \
  site "$STRATO_DIR"
bye
EOF
echo "done${DRY:+ (dry run - nothing uploaded)}"
