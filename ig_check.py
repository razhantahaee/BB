#!/usr/bin/env python3
"""Reads the latest 3 posts of Instagram pages listed in sources.txt, using the saved login (instagram_state.json).
Usage:  python ig_check.py            -> test: first 3 pages only
        python ig_check.py --all      -> all pages
Output: findings/social-instagram-YYYY-MM-DD.json  (candidates, raw: the Claude step cleans them)
Rules: the 3 newest posts per page (reads 6, sorts by date, so pinned posts do not count); skip a page if no post from today/yesterday."""
import datetime, json, os, random, re, sys, time
from playwright.sync_api import sync_playwright

TODAY = datetime.date.today()
STATE = "instagram_state.json"
ALL = "--all" in sys.argv

def pages():
    out, sec = [], False
    for line in open("sources.txt", encoding="utf-8"):
        s = line.strip()
        if s.startswith("#"):
            sec = s.lower().startswith("# instagram"); continue
        if sec and s.startswith("http"): out.append(s)
    return out if ALL else out[:3]

def pause(a=4, b=9): time.sleep(random.uniform(a, b))

def logged_in(page):
    u = page.url
    return "/accounts/login" not in u and "challenge" not in u

def main():
    if not os.path.exists(STATE): sys.exit("missing " + STATE)
    results, rows = [], []
    with sync_playwright() as p:
        b = p.firefox.launch(headless=True)
        ctx = b.new_context(storage_state=STATE, locale="en-US", viewport={"width": 1280, "height": 1600})
        pg = ctx.new_page()
        for url in pages():
            name = url.rstrip("/").split("/")[-1]
            row = {"page": name, "status": "", "posts": 0, "recent": 0}
            try:
                pg.goto(url, wait_until="domcontentloaded", timeout=45000); pause(3, 6)
                if not logged_in(pg):
                    row["status"] = "LOGIN/CHALLENGE - stop"; rows.append(row); break
                links = []
                for a in pg.query_selector_all('a[href*="/p/"], a[href*="/reel/"]'):
                    href = a.get_attribute("href") or ""
                    if a.query_selector('svg[aria-label="Pinned"]'): continue
                    full = "https://www.instagram.com" + href.split("?")[0] if href.startswith("/") else href.split("?")[0]
                    if full not in links: links.append(full)
                    if len(links) == 6: break
                if not links:
                    row["status"] = "no posts found"; rows.append(row); continue
                posts = []
                for l in links:
                    pause()
                    pg.goto(l, wait_until="domcontentloaded", timeout=45000); pause(2, 4)
                    t = pg.query_selector("time[datetime]")
                    dt = (t.get_attribute("datetime") if t else "")[:10]
                    og = lambda k: (pg.query_selector('meta[property="%s"]' % k).get_attribute("content") if pg.query_selector('meta[property="%s"]' % k) else "")
                    post = {"page": name, "post_url": l, "posted": dt, "caption": og("og:description")[:2500], "image": og("og:image"), "source": "instagram"}
                    posts.append(post)
                # pinned posts are old but show first: take the 3 NEWEST of the 6 read
                posts.sort(key=lambda x: x["posted"], reverse=True)
                for post in posts[:3]:
                    row["posts"] += 1
                    dt = post["posted"]
                    if dt and (TODAY - datetime.date.fromisoformat(dt)).days <= 1:
                        row["recent"] += 1; post["recent"] = True
                    else:
                        post["recent"] = False
                    results.append(post)
                row["status"] = "ok" if row["recent"] else "no post today/yesterday"
            except Exception as e:
                row["status"] = "error: " + str(e)[:80]
            rows.append(row)
            pause(5, 12)
        ctx.storage_state(path=STATE)   # keep the session fresh
        b.close()
    os.makedirs("findings", exist_ok=True)
    keep = [r for r in results if r["recent"]] if ALL else results
    json.dump(keep, open("findings/social-instagram-%s.json" % TODAY, "w", encoding="utf-8"), ensure_ascii=False, indent=1)
    print("| Page | Status | Posts read | Recent (today/yesterday) |\n|---|---|---|---|")
    for r in rows: print("| %(page)s | %(status)s | %(posts)s | %(recent)s |" % r)

if __name__ == "__main__":
    main()
