#!/bin/bash
# Daily job, started by cron at 06:00. Log: ~/bb.log
export PATH="$HOME/.local/bin:$PATH"
source ~/.config/bb/env
cd ~/BB || exit 1
if [ "$BB_NO_PUSH" != "1" ]; then git pull --rebase --autostash -q || { echo "$(date -Is) git pull FAILED" >> ~/bb.log; exit 1; }; fi
echo "$(date -Is) start" >> ~/bb.log
# 0) remove finished events from the site (archive); skipped if BB_API_URL/BB_TOKEN are not set
timeout -k 5 90 ~/BB/.venv/bin/python site_sync.py cleanup >> ~/bb.log 2>&1 || echo "site cleanup skipped/failed" >> ~/bb.log
# 0b) refresh sources.txt from the site: pages approved by Hosein are added, rejected ones removed; upcoming events -> dedup list
timeout -k 5 90 ~/BB/.venv/bin/python site_sync.py export >> ~/bb.log 2>&1 || echo "site export skipped/failed" >> ~/bb.log
timeout -k 10 900 ~/BB/.venv/bin/python -u fetch_sites.py >> ~/bb.log 2>&1
# Instagram step: Apify first (fast, no login); the browser reader ig_check.py is the fallback.
# Apify runs when APIFY_TOKEN is set in ~/.config/bb/env. ig_check.py runs only if .ig_on exists.
IG_DONE=0
if [ -n "$APIFY_TOKEN" ]; then
  timeout -k 10 600 ~/BB/.venv/bin/python -u apify_ig.py >> ~/bb.log 2>&1 && IG_DONE=1 || echo "apify step failed" >> ~/bb.log
fi
if [ "$IG_DONE" = "0" ] && [ -f .ig_on ] && [ -f instagram_state.json ]; then
  timeout -k 10 2400 ~/BB/.venv/bin/python ig_check.py --all >> ~/bb.log 2>&1 || echo "instagram step failed" >> ~/bb.log
fi
if [ -f prompts/daily.txt ]; then
  timeout -k 10 900 claude -p "$(cat prompts/daily.txt)" \
    --allowedTools "Read,Write,Edit,Bash(git:*),Bash(python3:*)" \
    >> ~/bb.log 2>&1 || echo "claude step failed" >> ~/bb.log
fi
timeout -k 5 120 ~/BB/.venv/bin/python site_sync.py import >> ~/bb.log 2>&1 || echo "site import skipped/failed" >> ~/bb.log   # candidates -> approval queue only
pkill -u bb -f 'playwright/driver' 2>/dev/null || true   # no leftover browsers
echo "last run: $(date -Is)" > status.txt
if [ "$BB_NO_PUSH" != "1" ]; then   # BB_NO_PUSH=1 in ~/.config/bb/env: test machine, never commit/push
  git add -A
  git commit -qm "daily run $(date +%F)" || true
  git push -q
fi
echo "$(date -Is) done" >> ~/bb.log
