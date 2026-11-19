#!/usr/bin/env python3
"""Reads latest posts of Instagram pages listed in sources.txt, using the saved login (instagram_state.json).
Usage:  python ig_check.py            -> test: first 3 pages only
        python ig_check.py --all      -> all pages (daily job)
        python ig_check.py --n 5      -> only the first 5 pages (can combine with --all or alone)
        python ig_check.py --first    -> FIRST-TIME read: last 10 posts per page, all kept (file social-instagram-DATE-first.json)
Daily mode: opens posts newest-first and STOPS as soon as it is clear nothing newer follows
(pinned posts are max 3 and come first, so a post number 4+ that is older than yesterday means nothing newer follows). Keeps posts from today/yesterday.
Memory: instagram_seen.json remembers the post links already seen per page. After a page has a baseline, only NEW links are opened
(a page with nothing new costs one profile load, about 10 s). A page without history gets the baseline run (up to 6 posts, keeps today/yesterday).
Every step is logged with a time stamp. Results are saved after every page."""
import datetime, json, os, random, re, subprocess, sys, threading, time
from playwright.sync_api import sync_playwright

TODAY = datetime.date.today()
STATE = "instagram_state.json"
FIRST = "--first" in sys.argv
ALL = "--all" in sys.argv or FIRST
N = int(sys.argv[sys.argv.index("--n") + 1]) if "--n" in sys.argv else None
MAXLINKS = 10 if FIRST else 6
BUDGET = 2100   # seconds; stop cleanly before run.sh's timeout so results are always saved
STALL = 150     # watchdog: no log line for this many seconds = hung -> save and exit
T0 = time.time()
LAST = [time.time()]
RESULTS = []
SEEN_FILE = "instagram_seen.json"

def code(link):
    m = re.search(r"/(?:p|reel)/([A-Za-z0-9_-]+)", link)
    return m.group(1) if m else link

def load_seen():
    try: return json.load(open(SEEN_FILE, encoding="utf-8"))
    except Exception: return {}

SEEN = load_seen()

def save_seen():
    tmp = SEEN_FILE + ".tmp"
    with open(tmp, "w", encoding="utf-8") as f: json.dump(SEEN, f, indent=0)
    os.replace(tmp, SEEN_FILE)

def log(msg):
    LAST[0] = time.time()
    print("[%4ds] %s" % (time.time() - T0, msg), flush=True)

def watchdog():
    # Playwright calls can hang on this low-RAM VPS (no swap). If nothing is logged for STALL s,
    # save what we have, kill our browser and exit, so the daily job never hangs.
    while True:
        time.sleep(10)
        if time.time() - LAST[0] > STALL:
            log("!! WATCHDOG: no progress for %ds - saving and exiting" % STALL)
            try: save(RESULTS)
            except Exception as e: log("   save failed: %s" % e)
            subprocess.run(["pkill", "-u", str(os.getuid()), "-f", "ms-playwright/firefox"])
            subprocess.run(["pkill", "-u", str(os.getuid()), "-f", "playwright/driver"])
            sys.stdout.flush(); os._exit(2)

def load_state():
    # Load ONLY the cookies (they hold the login). The saved localStorage (~330 KB of Instagram
    # web-app cache) makes Firefox's new_context() hang for minutes on this VPS.
    d = json.load(open(STATE, encoding="utf-8"))
    return {"cookies": d.get("cookies", []), "origins": []}

def save_cookies(ctx):
    # Refresh cookies in the session file, keep the rest of the file as it is.
    d = json.load(open(STATE, encoding="utf-8"))
    d["cookies"] = ctx.cookies()
    tmp = STATE + ".tmp"
    with open(tmp, "w", encoding="utf-8") as f: json.dump(d, f)
    os.chmod(tmp, 0o600); os.replace(tmp, STATE)

def pages():
    out, sec = [], False
    for line in open("sources.txt", encoding="utf-8"):
        s = line.strip()
        if s.startswith("#"):
            sec = s.lower().startswith("# instagram"); continue
        if sec and s.startswith("http"): out.append(s)
    if N: return out[:N]
    return out if ALL else out[:3]

def pause(a, b, why=""):
    d = random.uniform(a, b); log("  wait %.1fs %s" % (d, why)); time.sleep(d)

def logged_in(page):
    u = page.url
    return "/accounts/login" not in u and "challenge" not in u

def age(dt):
    return (TODAY - datetime.date.fromisoformat(dt)).days if dt else 99

def save(results):
    os.makedirs("findings", exist_ok=True)
    keep = results if (FIRST or not ALL) else [r for r in results if r["recent"]]
    name = "findings/social-instagram-%s%s.json" % (TODAY, "-first" if FIRST else "")
    json.dump(keep, open(name, "w", encoding="utf-8"), ensure_ascii=False, indent=1)

def main():
    if not os.path.exists(STATE): sys.exit("missing " + STATE)
    results, rows = RESULTS, []
    threading.Thread(target=watchdog, daemon=True).start()
    with sync_playwright() as p:
        log("starting headless Firefox")
        b = p.firefox.launch(headless=True, timeout=60000)
        log("browser up, loading session cookies")
        ctx = b.new_context(storage_state=load_state(), locale="en-US", viewport={"width": 1280, "height": 1600})
        ctx.set_default_timeout(30000); ctx.set_default_navigation_timeout(45000)
        log("opening tab")
        pg = ctx.new_page()
        todo = pages()
        log("%d pages to check%s" % (len(todo), " (FIRST mode: 10 posts each)" if FIRST else ""))
        for i, url in enumerate(todo, 1):
            if time.time() - T0 > BUDGET:
                rows.append({"page": "(rest)", "status": "time budget used - rest tomorrow", "posts": 0, "recent": 0}); log("time budget used, stopping"); break
            name = url.rstrip("/").split("/")[-1]
            row = {"page": name, "status": "", "posts": 0, "recent": 0}
            log("PAGE %d/%d %s: opening profile" % (i, len(todo), name))
            try:
                pg.goto(url, wait_until="domcontentloaded", timeout=45000)
                pause(3, 6, "(let profile render)")
                if not logged_in(pg):
                    row["status"] = "LOGIN/CHALLENGE - stop"; rows.append(row); log("!! LOGIN/CHALLENGE at " + pg.url + " - STOP"); break
                links = []
                for a in pg.query_selector_all('a[href*="/p/"], a[href*="/reel/"]'):
                    href = a.get_attribute("href") or ""
                    if a.query_selector('svg[aria-label="Pinned"]'): continue
                    full = "https://www.instagram.com" + href.split("?")[0] if href.startswith("/") else href.split("?")[0]
                    if ("/%s/" % name.lower()) not in full.lower():
                        continue    # other account (collab/suggested) - not this page's own post
                    if full not in links: links.append(full)
                    if len(links) == MAXLINKS: break
                log("  found %d post links on profile" % len(links))
                if not links:
                    try:
                        body = (pg.inner_text("body") or "").replace("\n", " ")[:160]
                        log("  no posts found. url=%s title=%r text=%r" % (pg.url, pg.title()[:60], body))
                    except Exception: pass
                    row["status"] = "no posts found"; rows.append(row); save(results); pause(3, 6, "(next page)"); continue
                seen_list = SEEN.get(name)
                known_page = seen_list is not None and not FIRST
                if known_page:
                    todo_links = [l for l in links if code(l) not in seen_list]
                    log("  %d of %d links are NEW (not seen before)" % (len(todo_links), len(links)))
                    if not todo_links:
                        SEEN[name] = seen_list; save_seen()
                        row["status"] = "no new posts"; rows.append(row)
                        log("DONE %s: no new posts" % name); save(results); pause(2, 4, "(before next page)"); continue
                else:
                    todo_links = links
                    log("  no history for this page: baseline run")
                posts, seen_recent = [], False
                for k, l in enumerate(todo_links, 1):
                    pause(2, 4, "(before post %d/%d)" % (k, len(todo_links)))
                    log("  opening post %d/%d %s" % (k, len(todo_links), l))
                    pg.goto(l, wait_until="domcontentloaded", timeout=45000); pause(1, 2, "(render)")
                    t = pg.query_selector("time[datetime]")
                    dt = (t.get_attribute("datetime") if t else "")[:10]
                    og = lambda key: (pg.query_selector('meta[property="%s"]' % key).get_attribute("content") if pg.query_selector('meta[property="%s"]' % key) else "")
                    posts.append({"page": name, "post_url": l, "posted": dt, "caption": og("og:description")[:2500], "image": og("og:image"), "source": "instagram"})
                    log("    posted %s (%s days old)" % (dt or "?", age(dt) if dt else "?"))
                    if not FIRST and not known_page and dt:
                        if age(dt) <= 1:
                            seen_recent = True
                        elif k >= 4 and not seen_recent:
                            log("  post %d (after up to 3 pinned) is old -> nothing newer follows, stop this page" % k); break
                        elif k >= 4 and seen_recent and age(dt) > 1:
                            log("  post %d is old after a recent one -> stop this page" % k); break
                posts.sort(key=lambda x: x["posted"], reverse=True)
                for post in (posts if (FIRST or known_page) else posts[:3]):
                    row["posts"] += 1
                    if known_page:   # new to us: keep it unless clearly old (an old post that only reappeared in the grid)
                        post["recent"] = (not post["posted"]) or age(post["posted"]) <= 7
                    else:
                        post["recent"] = bool(post["posted"]) and age(post["posted"]) <= 1
                    row["recent"] += post["recent"]
                    results.append(post)
                row["status"] = "ok" if (row["recent"] or FIRST) else ("new posts, all old" if known_page else "no post today/yesterday")
                SEEN[name] = list(dict.fromkeys([code(l) for l in links] + (seen_list or [])))[:60]; save_seen()
            except Exception as e:
                row["status"] = "error: " + str(e)[:80]
                log("  ERROR " + str(e)[:120])
            rows.append(row)
            log("DONE %s: %s (%d posts, %d recent)" % (name, row["status"], row["posts"], row["recent"]))
            save(results)
            pause(3, 6, "(before next page)")
        try:
            save_cookies(ctx); log("session cookies refreshed")   # keep the session fresh
        except Exception as e: log("cookie save failed: %s" % str(e)[:80])
        log("closing browser")
        b.close()
    save(results)
    print("| Page | Status | Posts read | Recent (today/yesterday) |\n|---|---|---|---|")
    for r in rows: print("| %(page)s | %(status)s | %(posts)s | %(recent)s |" % r)

if __name__ == "__main__":
    try:
        main()
    finally:
        sys.stdout.flush(); os._exit(0)
