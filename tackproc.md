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
- CAUSE FOUND: instagram_state.json has ~327 KB / 84 items of Instagram localStorage (web-app cache).
  Firefox new_context() with it hangs >150 s (even 64 small items = 53 s). Cookies only: context 0.4 s.
  Login lives in the 8 cookies.
- Backup of session: instagram_backup_state.json (600, gitignored via *_state.json).
- .gitignore had leftover merge-conflict markers -> rewritten: *.pyc .venv/ __pycache__/ .ig_on *_state.json
- FIX in ig_check.py: load_state() = cookies only; save_cookies() refreshes only cookies (no more
  storage_state(path=...) re-bloating the file); launch timeout 60 s; default timeout 30 s / nav 45 s;
  watchdog thread: no log line for 150 s -> save results, pkill own firefox/driver, exit 2.
- Step 4 run #2 started (same command).
- Run #2 (22:12): NO HANG any more - finished by itself in ~6 min. But all 5 pages: Page.goto timeout 45 s
  (abadansweden, askfumea, atishevents, byhouseofrumi, club_ofelia). No LOGIN/CHALLENGE seen.
- REAL ROOT CAUSE: memory thrashing. 1 CPU, 1.8 GB RAM, NO swap. VS Code server (~1 GB) + Firefox don't fit:
  kswapd busy, 52% iowait, ~230 MB/s disk reads, load avg ~10. Same thing froze the VPS earlier tonight.
  Probe: even with images/fonts blocked, commit took 18 s and DOMContentLoaded never came in 45 s;
  with single-process Firefox prefs it was worse (no commit in 45 s).
- Reliability fixes in ig_check.py are KEPT (cookies-only, timeouts, watchdog). Not committed yet.
- WAITING FOR USER DECISION: (a) add a 2 GB swapfile (system change, needs OK),
  (b) test with VS Code disconnected (cron at 06:00 normally runs without it), or
  (c) replace the browser with plain HTTP requests using the cookies (light, but different method).
