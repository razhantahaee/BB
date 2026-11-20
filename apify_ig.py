#!/usr/bin/env python3
"""Instagram check through Apify (apify/instagram-scraper). Replaces the slow browser reader ig_check.py.
Reads the Instagram pages from sources.txt, asks Apify for the newest posts, keeps only posts we have not seen,
writes findings/social-instagram-DATE.json (same format as ig_check.py) and prints a table.

Usage:  python apify_ig.py             -> daily run (tiers + budget)
        python apify_ig.py --n 5       -> only the first 5 pages (test)
        python apify_ig.py --dry       -> show what would be asked, spend nothing
        python apify_ig.py --first     -> baseline: last 10 posts for pages that have no history yet (done automatically for new pages)

Needs APIFY_TOKEN in ~/.config/bb/env (never commit it). Cost control (all numbers can be changed with env vars):
- APIFY_MONTHLY_ITEMS (default 1800 result rows = about 5 USD at the free-plan price) is the monthly budget.
  Every returned row (also empty "no_items" rows) is counted, which is the safe assumption.
- Daily cap = remaining budget / remaining days of the month (never below 20 rows). It is sent to Apify as maxItems.
- Tiers: pages with a post in the last 14 days are checked daily; quiet pages (14-45 days) on Monday and Thursday;
  dead pages (45+ days without a post) on Monday only. New pages are always checked and get a baseline of 10 posts.
Exit code 1 on any failure, so run.sh can fall back to ig_check.py."""
import datetime, json, math, os, re, sys, time, urllib.error, urllib.request

try:
    from zoneinfo import ZoneInfo
    TODAY = datetime.datetime.now(ZoneInfo("Europe/Stockholm")).date()   # same "today" on every machine (the VM may run on UTC)
except Exception:
    TODAY = datetime.date.today()
NOW = datetime.datetime.now(datetime.timezone.utc)
STATE_FILE = "apify_state.json"          # ignored by git (*_state.json): budget + per-page memory, local to this machine
ACTOR = "apify~instagram-scraper"
TOKEN = os.environ.get("APIFY_TOKEN", "")
MONTHLY = int(os.environ.get("APIFY_MONTHLY_ITEMS", "1800"))
EVERY = max(1, int(os.environ.get("APIFY_EVERY_DAYS", "3")))   # each existing page is asked once per EVERY days
BATCH = 100                               # pages per Apify run
DRY = "--dry" in sys.argv
N = int(sys.argv[sys.argv.index("--n") + 1]) if "--n" in sys.argv else None
FORCE_FIRST = "--first" in sys.argv

def log(m): print("[apify] " + m, flush=True)

def load_state():
    try: return json.load(open(STATE_FILE, encoding="utf-8"))
    except Exception: return {"last_ok": None, "month": "", "used": 0, "pages": {}}

def save_state(st):
    tmp = STATE_FILE + ".tmp"
    with open(tmp, "w", encoding="utf-8") as f: json.dump(st, f, indent=1)
    os.chmod(tmp, 0o600); os.replace(tmp, STATE_FILE)

def pages():
    out, sec = [], False
    for line in open("sources.txt", encoding="utf-8"):
        s = line.strip()
        if s.startswith("#"):
            sec = s.lower().startswith("# instagram"); continue
        if sec and s.startswith("http"): out.append(s.rstrip("/") + "/")
    return out[:N] if N else out

def handle(url): return url.rstrip("/").split("/")[-1].lower()

def code(link):
    m = re.search(r"/(?:p|reel)/([A-Za-z0-9_-]+)", link or "")
    return m.group(1) if m else (link or "")

def tier_due(info):
    """info = per-page memory or None. Returns True if the page should be asked today."""
    if not info: return True                              # new page
    chk = info.get("checked")
    if chk and (TODAY - datetime.date.fromisoformat(chk)).days < EVERY: return False   # asked recently (every 3 days)
    last = info.get("last_post")                           # YYYY-MM-DD of newest post we ever saw
    if not last: return TODAY.weekday() in (0, 3)          # never saw a post: Monday/Thursday
    days = (TODAY - datetime.date.fromisoformat(last)).days
    if days <= 14: return True
    if days <= 45: return TODAY.weekday() in (0, 3)
    return TODAY.weekday() == 0

def call(urls, limit, since_days, cap):
    inp = {"resultsType": "posts", "directUrls": urls, "resultsLimit": limit, "addParentData": False}
    if since_days: inp["onlyPostsNewerThan"] = "%d days" % since_days
    api = "https://api.apify.com/v2/acts/%s/run-sync-get-dataset-items?maxItems=%d&timeout=240" % (ACTOR, cap)
    req = urllib.request.Request(api, data=json.dumps(inp).encode(), method="POST",
                                 headers={"Content-Type": "application/json", "Authorization": "Bearer " + TOKEN,
                                          "User-Agent": "bb-apify/1.0"})
    try:
        with urllib.request.urlopen(req, timeout=290) as r:
            return json.loads(r.read().decode())
    except urllib.error.HTTPError as e:
        raise RuntimeError("Apify answered HTTP %s: %s" % (e.code, e.read().decode()[:200]))

def main():
    if not TOKEN and not DRY: sys.exit("APIFY_TOKEN not set")
    st = load_state()
    month = TODAY.strftime("%Y-%m")
    if st.get("month") != month: st["month"], st["used"] = month, 0
    days_left = (datetime.date(TODAY.year + (TODAY.month == 12), TODAY.month % 12 + 1, 1) - TODAY).days
    remaining = MONTHLY - st["used"]
    cap_day = max(20, remaining // max(1, days_left))
    log("budget: %d of %d rows used this month, %d days left -> today's cap %d rows" % (st["used"], MONTHLY, days_left, cap_day))
    if remaining <= 0 and not DRY:
        log("monthly budget used up - skipping Instagram today"); sys.exit(1)

    allp = pages()
    todo_new, todo_daily, skipped = [], [], 0
    for u in allp:
        info = st["pages"].get(handle(u))
        if not info or FORCE_FIRST: todo_new.append(u)
        elif tier_due(info): todo_daily.append(u)
        else: skipped += 1
    log("%d pages in list: %d new (baseline), %d daily, %d skipped by tier today" % (len(allp), len(todo_new), len(todo_daily), skipped))
    if DRY: log("dry run - nothing sent"); return

    since = EVERY
    if st.get("last_ok"):
        hours = (NOW - datetime.datetime.fromisoformat(st["last_ok"])).total_seconds() / 3600
        since = max(EVERY, min(10, math.ceil(hours / 24)))      # a missed day is covered by a wider window
    seen_codes = {c for info in st["pages"].values() for c in info.get("seen", [])}

    posts, rows, spent, base_spent = [], [], 0, 0
    def run(urls, limit, since_days, label):
        nonlocal spent
        for i in range(0, len(urls), BATCH):
            chunk = urls[i:i + BATCH]
            # baseline (one-time, new pages) may use the whole month's budget; daily checks stay under the daily cap
            room = (remaining - spent) if label == "baseline" else (cap_day - (spent - base_spent))
            cap = max(1, min(room, len(chunk) * limit + len(chunk)))
            if room <= 0: log("cap reached - rest tomorrow"); return
            log("%s: asking Apify for %d pages (limit %d, window %s, cap %d rows)" % (label, len(chunk), limit, ("%d days" % since_days) if since_days else "none", cap))
            t0 = time.time(); items = call(chunk, limit, since_days, cap); spent += len(items)
            log("  got %d rows in %.0fs" % (len(items), time.time() - t0))
            answered = set()
            for it in items:
                owner_input = handle(it.get("inputUrl", ""))
                answered.add(owner_input)
                if it.get("error") and it.get("error") != "no_items" and (it.get("shortCode") or it.get("caption")):
                    log("  note for %s: %s" % (owner_input or it.get("ownerUsername", "?"), it.get("errorDescription", it["error"])))
                if it.get("error"):
                    if it["error"] != "no_items": log("  %s: %s" % (owner_input or it.get("url", "?"), it.get("errorDescription", it["error"])))
                    continue
                c = it.get("shortCode") or code(it.get("url", ""))
                ts = (it.get("timestamp") or "")[:10]
                info = st["pages"].setdefault(owner_input, {"seen": []})
                if c in info["seen"] or c in seen_codes: continue
                info["seen"] = (info["seen"] + [c])[-60:]
                if ts and (not info.get("last_post") or ts > info["last_post"]): info["last_post"] = ts
                if label == "baseline" and ts and (TODAY - datetime.date.fromisoformat(ts)).days > 30: continue
                posts.append({"page": owner_input, "post_url": it.get("url"), "posted": ts, "caption": (it.get("caption") or "")[:2500],
                              "image": it.get("displayUrl", ""), "source": "instagram", "recent": True,
                              "owner": it.get("ownerUsername", ""), "location": it.get("locationName", ""),
                              "pinned": bool(it.get("isPinned"))})
            for u in chunk:
                h = handle(u)
                if h not in answered:        # no row at all (cap reached): leave the page untouched, it stays "new" for tomorrow
                    rows.append((h, label + " (not answered)", 0)); continue
                info = st["pages"].setdefault(h, {"seen": []}); info["checked"] = TODAY.isoformat()
                rows.append((h, label, sum(1 for p in posts if p["page"] == h)))
    try:
        if todo_new:
            run(todo_new, 10, None, "baseline"); base_spent = spent
        if todo_daily: run(todo_daily, 3, since, "daily")
    except Exception as e:
        st["used"] += spent; save_state(st); log("FAILED: %s" % e); sys.exit(1)

    st["used"] += spent; st["last_ok"] = NOW.isoformat(timespec="seconds"); save_state(st)
    os.makedirs("findings", exist_ok=True)
    name = "findings/social-instagram-%s.json" % TODAY
    old = []
    if os.path.exists(name):
        try: old = json.load(open(name, encoding="utf-8"))
        except Exception: old = []
    keep = {code(p["post_url"]): p for p in old}; keep.update({code(p["post_url"]): p for p in posts})   # same post = same shortcode, whichever reader found it
    json.dump(list(keep.values()), open(name, "w", encoding="utf-8"), ensure_ascii=False, indent=1)
    log("done: %d new posts, %d rows billed (assumed), month %d/%d" % (len(posts), spent, st["used"], MONTHLY))
    print("| Page | Tier | New posts |\n|---|---|---|")
    for h, label, n in rows: print("| %s | %s | %d |" % (h, label, n))

if __name__ == "__main__":
    main()
