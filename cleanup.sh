#!/bin/bash
# Nightly job (cron 03:00 Europe/Stockholm): ONLY removes finished events from the site (archives them). Nothing else.
# An event is finished when its endDate/date is before today (Stockholm). Log: ~/cleanup.log
source ~/.config/bb/env
cd ~/BB || exit 1
echo "$(date -Is) cleanup start" >> ~/cleanup.log
timeout -k 5 90 ~/BB/.venv/bin/python site_sync.py cleanup >> ~/cleanup.log 2>&1 \
  && echo "$(date -Is) cleanup done" >> ~/cleanup.log \
  || echo "$(date -Is) cleanup FAILED" >> ~/cleanup.log
