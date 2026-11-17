#!/bin/bash
# Daily job, started by cron at 06:00. Log: ~/bb.log
export PATH="$HOME/.local/bin:$PATH"
source ~/.config/bb/env
cd ~/BB || exit 1
git pull --rebase -q || exit 1
echo "$(date -Is) start" >> ~/bb.log
~/BB/.venv/bin/python fetch_sites.py >> ~/bb.log 2>&1
# Instagram step: only runs if the switch file exists (create it with: touch ~/BB/.ig_on)
if [ -f .ig_on ] && [ -f instagram_state.json ]; then
  ~/BB/.venv/bin/python ig_check.py --all >> ~/bb.log 2>&1 || echo "instagram step failed" >> ~/bb.log
fi
if [ -f prompts/daily.txt ]; then
  claude -p "$(cat prompts/daily.txt)" \
    --allowedTools "Read,Write,Edit,Bash(git:*),Bash(python3:*)" \
    >> ~/bb.log 2>&1 || echo "claude step failed" >> ~/bb.log
fi
echo "last run: $(date -Is)" > status.txt
git add -A
git commit -qm "daily run $(date +%F)" || true
git push -q
echo "$(date -Is) done" >> ~/bb.log
