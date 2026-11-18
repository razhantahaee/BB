# tackproc - work trace (resume from here if the VPS hangs)

Task (from user): make the daily Instagram job reliable.
1. Kill leftovers (ig_check.py, playwright/driver, firefox)
2. git pull --rebase --autostash (keep local findings)
3. ig_check.py must contain "def log"
4. Run `.venv/bin/python ig_check.py --all --n 5 2>&1 | tee ~/ig_test.log`; if a step waits >90 s, stop, find cause, fix (add time limits)
5. LOGIN/CHALLENGE -> stop and tell user. Never log in from the VPS.
6. When 5 pages work: report table + time per page. No cron changes, no sudo, no secrets committed.

Notes for whoever resumes:
- Claude runs as root; repo is owned by bb. Run git/python as `runuser -u bb -- ...` (git refuses root: "dubious ownership").
- `pkill -f firefox` from a shell whose command line contains "firefox" kills that shell too (exit 144) - harmless.
- VPS: ~1.8 GB RAM, no swap; VS Code server uses ~1 GB, leaves ~250-500 MB free.

## Log
- 2026-11-18 21:3x  Step 1 done (no leftovers left).
- Step 2 done: "Already up to date". Untracked findings/social-instagram-2026-11-18.json kept.
- Step 3 done: `def log` present (line 22), HEAD 4238552.
- Step 4 run #1: HUNG after "[0s] starting headless Firefox" for >5 min (no "pages to check" line).
  Firefox processes were running -> hang is in new_context(storage_state) or new_page, not launch.
  Killed. Next: time launch/new_context/new_page separately, add timeouts + watchdog.
