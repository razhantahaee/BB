#!/bin/bash
# Manual one-shot, INSTAGRAM (+ Facebook when its reader exists): Apify -> Claude cleaning -> LOCAL queue + MAIN queue.
# Uses Apify credit (the script itself only asks each page every 3 days and keeps the monthly budget).
# Complete events are published automatically on both sites; incomplete ones wait in the admin queue.
# Usage:  ./sync_social.sh            (normal)
#         ./sync_social.sh --dry      (show what would be asked from Apify, spend nothing, stop)
#         ./sync_social.sh --pull     (git pull first)
export PATH="$HOME/.local/bin:$PATH"
cd ~/BB || exit 1
PY=~/BB/.venv/bin/python
LOCAL_ENV=~/.config/bb/env
MAIN_ENV=~/.config/bb/env.main
PULL=0; DRY=0
for a in "$@"; do case "$a" in --pull) PULL=1;; --dry) DRY=1;; esac; done
say() { echo; echo "=== $*"; }
with_env() { local f="$1"; shift; ( source "$f" && "$@" ); }

[ -f "$LOCAL_ENV" ] || { echo "missing $LOCAL_ENV"; exit 1; }
HAVE_MAIN=0; [ -f "$MAIN_ENV" ] && HAVE_MAIN=1
[ "$HAVE_MAIN" = 1 ] || echo "note: $MAIN_ENV not found - the main site will be skipped"
grep -q '^export APIFY_TOKEN=' "$LOCAL_ENV" || { echo "APIFY_TOKEN is not set in $LOCAL_ENV - stopping"; exit 1; }

if [ "$PULL" = 1 ]; then say "0) git pull"; git pull --rebase --autostash -q 2>&1 | grep -v -i "moved\|razhantahaee"; fi

say "1) refresh pages + dedup list (from the main site when available)"
if [ "$HAVE_MAIN" = 1 ]; then with_env "$MAIN_ENV" timeout -k 5 90 $PY site_sync.py export || echo "export skipped/failed"
else with_env "$LOCAL_ENV" timeout -k 5 90 $PY site_sync.py export || echo "export skipped/failed"; fi

if [ "$DRY" = 1 ]; then say "2) Instagram (dry run, no credit)"; with_env "$LOCAL_ENV" $PY apify_ig.py --dry; exit 0; fi

say "2) Instagram through Apify"
with_env "$LOCAL_ENV" timeout -k 10 600 $PY -u apify_ig.py || { echo "Apify step failed - stopping"; exit 1; }
TODAY=$(TZ=Europe/Stockholm date +%F)
SRC="findings/social-instagram-$TODAY.json"
if [ ! -f "$SRC" ]; then echo "no new Instagram posts today ($SRC missing) - nothing to clean"; exit 0; fi

# Facebook: no reader yet. When one exists it will write findings/social-facebook-<date>.json and be added here.

say "3) Claude cleaning (a few minutes)"
timeout -k 10 900 claude -p "$(cat prompts/social.txt)" --allowedTools "Read,Write,Edit,Bash(git:*),Bash(python3:*)" > /tmp/sync_social_claude.log 2>&1 || echo "claude step failed - see /tmp/sync_social_claude.log"
CHK="findings/social-checked-$TODAY.json"
[ -f "$CHK" ] || { echo "no $CHK - stopping"; tail -5 /tmp/sync_social_claude.log; exit 1; }
echo "events found in posts: $($PY -c "import json;print(len(json.load(open('$CHK'))))")"

say "4) send to the LOCAL queue"
with_env "$LOCAL_ENV" timeout -k 5 120 $PY site_sync.py import "$CHK"
if [ "$HAVE_MAIN" = 1 ]; then say "5) send to the MAIN queue (bezanberim.eu)"; with_env "$MAIN_ENV" timeout -k 5 120 $PY site_sync.py import "$CHK"; fi
pkill -u "$USER" -f 'playwright/driver' 2>/dev/null || true
echo; echo "Done. Complete events were published automatically; incomplete ones wait in the admin queue (local and main)."
