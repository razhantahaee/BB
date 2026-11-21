#!/bin/bash
# Manual one-shot, WEBSITES only: check the websites, clean with Claude, send the candidates to BOTH approval queues (local test site + main site).
# No Apify (no credit used). Nothing goes live: events land in each site's pending queue and Hosein approves them in the admin panel.
# Usage:  ./sync_websites.sh            (normal)
#         ./sync_websites.sh --pull     (git pull first)
#         ./sync_websites.sh --deploy   (also upload site files to Strato at the end)
export PATH="$HOME/.local/bin:$PATH"
cd ~/BB || exit 1
PY=~/BB/.venv/bin/python
LOCAL_ENV=~/.config/bb/env
MAIN_ENV=~/.config/bb/env.main
PULL=0; DEPLOY=0
for a in "$@"; do case "$a" in --pull) PULL=1;; --deploy) DEPLOY=1;; esac; done

say() { echo; echo "=== $*"; }
# run a command with the settings of one site (in a subshell, so the two sites never mix)
with_env() { local f="$1"; shift; ( source "$f" && "$@" ); }

[ -f "$LOCAL_ENV" ] || { echo "missing $LOCAL_ENV"; exit 1; }
HAVE_MAIN=0; [ -f "$MAIN_ENV" ] && HAVE_MAIN=1
[ "$HAVE_MAIN" = 1 ] || echo "note: $MAIN_ENV not found - the main site will be skipped"

if [ "$PULL" = 1 ]; then say "0) git pull"; git pull --rebase --autostash -q 2>&1 | grep -v -i "moved\|razhantahaee"; fi

say "1) archive finished events (local, main)"
with_env "$LOCAL_ENV" timeout -k 5 90 $PY site_sync.py cleanup || echo "local cleanup skipped/failed"
[ "$HAVE_MAIN" = 1 ] && { with_env "$MAIN_ENV" timeout -k 5 90 $PY site_sync.py cleanup || echo "main cleanup skipped/failed"; }

say "2) refresh the dedup list (taken from the main site when available)"
if [ "$HAVE_MAIN" = 1 ]; then with_env "$MAIN_ENV" timeout -k 5 90 $PY site_sync.py export || echo "export skipped/failed"
else with_env "$LOCAL_ENV" timeout -k 5 90 $PY site_sync.py export || echo "export skipped/failed"; fi

say "3) check the websites (volek, javantik, globalsounds, discotehran)"
timeout -k 10 900 $PY -u fetch_sites.py || echo "website check had problems (see above)"
TODAY=$(TZ=Europe/Stockholm date +%F)
[ -f "findings/website-$TODAY.json" ] || { echo "no findings/website-$TODAY.json - stopping"; exit 1; }

say "4) Claude cleaning (a few minutes)"
timeout -k 10 900 claude -p "$(cat prompts/daily.txt)" --allowedTools "Read,Write,Edit,Bash(git:*),Bash(python3:*)" > /tmp/sync_websites_claude.log 2>&1 || echo "claude step failed - see /tmp/sync_websites_claude.log"
CHK="findings/website-checked-$TODAY.json"
[ -f "$CHK" ] || { echo "no $CHK - stopping"; tail -5 /tmp/sync_websites_claude.log; exit 1; }
echo "candidates in $CHK: $($PY -c "import json,sys;print(len(json.load(open('$CHK'))))")"

say "5) send to the LOCAL queue"
with_env "$LOCAL_ENV" timeout -k 5 120 $PY site_sync.py import
if [ "$HAVE_MAIN" = 1 ]; then say "6) send to the MAIN queue (bezanberim.eu)"; with_env "$MAIN_ENV" timeout -k 5 120 $PY site_sync.py import; fi

if [ "$DEPLOY" = 1 ]; then say "7) upload site files to Strato"; ./deploy_strato.sh; fi
pkill -u "$USER" -f 'playwright/driver' 2>/dev/null || true
echo; echo "Done. Now open the admin panel (local and main) and approve or reject the pending events."
