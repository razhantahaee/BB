#!/bin/bash
set -e
source ~/.config/bb/env
cd ~/BB
git pull --rebase
python3 fetch_sites.py            # step 5
claude -p "$(cat prompts/daily.txt)" \
  --allowedTools "Read,Write,Edit,Bash(git:*),Bash(python3:*)" \
  >> ~/bb.log 2>&1
git add -A
git commit -m "daily run $(date +%F)" || true
git push
