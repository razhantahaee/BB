#!/bin/bash
# Daily job, started by cron at 06:00. Log: ~/bb.log
export PATH="$HOME/.local/bin:$PATH"
source ~/.config/bb/env
cd ~/BB || exit 1
git pull --rebase --autostash -q || { echo "$(date -Is) git pull FAILED" >> ~/bb.log; exit 1; }
echo "$(date -Is) start" >> ~/bb.log
# 0) remove finished events from the site (archive); skipped if BB_API_URL/BB_TOKEN are not set
timeout -k 5 90 ~/BB/.venv/bin/python site_sync.py cleanup >> ~/bb.log 2>&1 || echo "site cleanup skipped/failed" >> ~/bb.log
timeout -k 10 900 ~/BB/.venv/bin/python -u fetch_sites.py >> ~/bb.log 2>&1
# Instagram step: only runs if the switch file exists (create it with: touch ~/BB/.ig_on)
if [ -f .ig_on ] && [ -f instagram_state.json ]; then
  timeout -k 10 2400 ~/BB/.venv/bin/python ig_check.py --all >> ~/bb.log 2>&1 || echo "instagram step failed" >> ~/bb.log
fi
if [ -f prompts/daily.txt ]; then
  timeout -k 10 900 claude -p "$(cat prompts/daily.txt)" \
    --allowedTools "Read,Write,Edit,Bash(git:*),Bash(python3:*)" \
    >> ~/bb.log 2>&1 || echo "claude step failed" >> ~/bb.log
fi
pkill -u bb -f 'playwright/driver' 2>/dev/null || true   # no leftover browsers
echo "last run: $(date -Is)" > status.txt
git add -A
git commit -qm "daily run $(date +%F)" || true
git push -q
echo "$(date -Is) done" >> ~/bb.log
