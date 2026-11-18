#!/usr/bin/env python3
"""Daily website reader for BezanBerim. Python standard library only.
Reads volek, javantik, kurdish.events, globalsounds, discotehran and writes
findings/website-YYYY-MM-DD.json  (candidates)  +  findings/website-YYYY-MM-DD.summary.txt
The Claude step (prompts/daily.txt) then cleans the candidates."""
import base64, datetime, html, json, os, random, re, sys, time, urllib.error, urllib.parse, urllib.request

TODAY = datetime.date.today().isoformat()
UA = "Mozilla/5.0 (X11; Linux x86_64; rv:128.0) Gecko/20100101 Firefox/128.0"
DELAY = 1.0
EUROPE = set("al ad at ba be bg by ch cy cz de dk ee es fi fr gb ge gr hr hu ie is it li lt lu lv mc md me mk mt nl no pl pt ro rs se si sk sm ua va xk".split())

_PW = {}
def bget(url):
    """Fetch with a real headless Firefox (used when a site refuses plain requests)."""
    from playwright.sync_api import sync_playwright
    if "b" not in _PW:
        _PW["p"] = sync_playwright().start()
        _PW["b"] = _PW["p"].firefox.launch(headless=True)
        _PW["pg"] = _PW["b"].new_page(locale="en-US")
    pg = _PW["pg"]
    r = pg.goto(url, timeout=60000, wait_until="domcontentloaded")
    if r is not None and r.status >= 400:
        raise urllib.error.HTTPError(url, r.status, "browser", None, None)
    pg.wait_for_timeout(2500)
    return pg.content()

def bclose():
    try:
        if "b" in _PW: _PW["b"].close(); _PW["p"].stop()
    except Exception:
        pass

def get(url, accept="text/html,*/*"):
    if url in BROWSER_ONLY_HOSTS_SEEN_BLOCKED(url):
        return bget(url)
    return _get(url, accept)

BLOCKED_HOSTS = set()
def BROWSER_ONLY_HOSTS_SEEN_BLOCKED(url):
    h = urllib.parse.urlparse(url).netloc
    return [url] if h in BLOCKED_HOSTS else []

def _get(url, accept="text/html,*/*"):
    last = None
    for n in range(2):
        try:
            req = urllib.request.Request(url, headers={"User-Agent": UA, "Accept": accept, "Accept-Language": "en"})
            with urllib.request.urlopen(req, timeout=30) as r:
                return r.read().decode("utf-8", "replace")
        except urllib.error.HTTPError as e:
            last = e
            if e.code in (404, 410): raise          # gone: do not retry
            if e.code in (454, 429, 403) and "text/html" in accept:
                BLOCKED_HOSTS.add(urllib.parse.urlparse(url).netloc)   # from now on use the real browser for this site
                log("blocked (%s) -> using browser for %s" % (e.code, urllib.parse.urlparse(url).netloc))
                return bget(url)
            time.sleep(10 * (n + 1))
        except Exception as e:
            last = e
            time.sleep(3 * (n + 1))
    raise last

def log(*a):
    print(*a, file=sys.stderr, flush=True)

def getj(url):
    return json.loads(get(url, "application/json"))

def clean(s):
    return re.sub(r"\s+", " ", html.unescape(re.sub(r"<[^>]+>", " ", s or ""))).strip()

def meta(page, prop):
    m = re.search(r'<meta[^>]+(?:property|name)=["\']%s["\'][^>]+content=["\']([^"\']*)' % re.escape(prop), page, re.I) \
        or re.search(r'<meta[^>]+content=["\']([^"\']*)["\'][^>]+(?:property|name)=["\']%s["\']' % re.escape(prop), page, re.I)
    return html.unescape(m.group(1)) if m else ""

def jsonld_events(page):
    out = []
    for blk in re.findall(r'<script[^>]+application/ld\+json[^>]*>(.*?)</script>', page, re.S | re.I):
        try:
            d = json.loads(blk)
        except Exception:
            continue
        stack = d if isinstance(d, list) else [d]
        while stack:
            x = stack.pop()
            if isinstance(x, dict):
                if "@graph" in x:
                    stack.extend(x["@graph"])
                t = x.get("@type")
                if t == "Event" or (isinstance(t, list) and "Event" in t):
                    out.append(x)
            elif isinstance(x, list):
                stack.extend(x)
    return out

def from_ld(ev):
    loc = ev.get("location") or {}
    if isinstance(loc, list): loc = loc[0] if loc else {}
    addr = loc.get("address") or {}
    if isinstance(addr, str): addr = {"streetAddress": addr}
    img = ev.get("image")
    if isinstance(img, list): img = img[0] if img else ""
    if isinstance(img, dict): img = img.get("url", "")
    offers = ev.get("offers") or {}
    if isinstance(offers, list): offers = offers[0] if offers else {}
    start = str(ev.get("startDate") or "")
    return {
        "title": clean(ev.get("name", "")),
        "date": start[:10],
        "time": start[11:16] if len(start) > 15 else "",
        "venue": clean(loc.get("name", "")),
        "address": clean(addr.get("streetAddress", "")),
        "city": clean(addr.get("addressLocality", "")),
        "country": (addr.get("addressCountry") if isinstance(addr.get("addressCountry"), str) else (addr.get("addressCountry") or {}).get("name", "")) or "",
        "price": str(offers.get("price", "") or ""),
        "currency": str(offers.get("priceCurrency", "") or ""),
        "image": img or "",
        "ticket_url": (offers.get("url") or ""),
    }

# ---------- existing events (dedup) ----------
def norm(s):
    return re.sub(r"[^a-z0-9]+", "", (s or "").lower())

def load_existing():
    keys = set()
    try:
        sec = False
        for line in open("sources.txt", encoding="utf-8"):
            if line.startswith("# Existing events"):
                sec = True; continue
            if sec and "|" in line and not line.startswith("#"):
                t, d, c = [p.strip() for p in line.split("|")[:3]]
                keys.add((norm(t), d, norm(c)))
    except Exception:
        pass
    return keys

def is_existing(c, keys):
    for (t, d, city) in keys:
        if d == c.get("date") and (t == norm(c.get("title")) or (t in norm(c.get("title")) and len(t) > 8) or (norm(c.get("title")) in t and len(norm(c.get("title"))) > 8)):
            return True
    return False

def load_known():
    out = set()
    for f in ("known_urls.txt", "seen_urls.txt"):   # known = already on site; seen = already looked at by this script
        try:
            out |= set(l.strip().rstrip("/") for l in open(f, encoding="utf-8") if l.strip() and not l.startswith("#"))
        except Exception:
            pass
    return out
KNOWN = load_known()
SEEN_SKIP = set()
EXTRA_SEEN = []
SK = [0]
def known(u):
    k = u.rstrip("/") in KNOWN
    if k: SK[0] += 1
    return k

# ---------- sites ----------
def site_volek():
    out, page = [], 1
    urls = []
    while True:
        try:
            items = getj("https://volek.events/wp-json/wp/v2/event_listing?per_page=100&page=%d" % page)
        except Exception:
            break
        if not items: break
        urls += [(clean(i["title"]["rendered"]), i["link"]) for i in items]
        page += 1
    urls = [(t, l) for (t, l) in urls if not known(l)]   # only new events need a page request
    log("volek: %d new events to read" % len(urls))
    fails, t0 = 0, time.time()
    for idx, (title, link) in enumerate(urls):
        if fails >= 3 or time.time() - t0 > 900:
            log("volek: stopping early (blocked or too slow); rest will be tried tomorrow"); break
        time.sleep(random.uniform(3, 6))
        log("volek %d/%d %s" % (idx + 1, len(urls), link))
        c = {"title": title, "url": link, "ticket_url": link, "source": "volek.events"}
        try:
            pg = get(link)
            if "access code to open the event page" in pg:
                EXTRA_SEEN.append(link); log("  private event (access code) - skipped"); fails = 0; continue
            lds = jsonld_events(pg)
            if lds: c.update({k: v for k, v in from_ld(lds[0]).items() if v})
            c["ticket_url"] = link  # volek sells the ticket itself
            if not c.get("image"): c["image"] = meta(pg, "og:image")
            c["title"] = c.get("title") or title
            fails = 0
        except Exception as e:
            c["error"] = str(e)[:120]; fails += 1
            if fails >= 3: SEEN_SKIP.add(link)
            continue          # not read: do not remember it, try again tomorrow
        out.append(c)
    return out

def site_javantik():
    out = []
    pg = get("https://www.javantik.com/events")
    ids = []
    for m in re.finditer(r'href=["\'](?:https://www\.javantik\.com)?/event/(\d+)', pg):
        if m.group(1) not in ids: ids.append(m.group(1))
    ids = [i for i in ids if not known("https://www.javantik.com/event/" + i)]
    for i in ids:
        time.sleep(DELAY)
        url = "https://www.javantik.com/event/" + i
        c = {"title": "", "url": url, "ticket_url": url, "source": "javantik.com"}
        try:
            p = get(url)
            lds = jsonld_events(p)
            if lds: c.update({k: v for k, v in from_ld(lds[0]).items() if v})
            c["title"] = c.get("title") or clean(meta(p, "og:title") or (re.search(r"<title>(.*?)</title>", p, re.S) or [None, ""])[1])
            m = re.search(r'https://www\.javantik\.com/cdn/events-image/[\w/.-]+\.(?:webp|jpe?g|png)', p)
            if m: c["image"] = m.group(0)
            b = re.search(r'href=["\'](?:https://www\.javantik\.com)?/link\?url=([^"\'&]+)', p)
            if b:
                raw = urllib.parse.unquote(b.group(1))
                try:
                    c["ticket_url"] = base64.b64decode(raw + "=" * (-len(raw) % 4)).decode()
                except Exception:
                    c["ticket_url_raw"] = raw
            if not c.get("date"):
                c["page_text"] = clean(re.sub(r"<(script|style)[^>]*>.*?</\1>", " ", p, flags=re.S))[:1500]
        except Exception as e:
            c["error"] = str(e)[:120]
        out.append(c)
    return out

def generic(base, source, link_re):
    out = []
    pg = get(base)
    seen = []
    for m in re.finditer(link_re, pg):
        u = urllib.parse.urljoin(base, m.group(1))
        if u not in seen: seen.append(u)
    for u in [x for x in seen if not known(x)][:60]:
        time.sleep(DELAY)
        c = {"title": "", "url": u, "ticket_url": u, "source": source}
        try:
            p = get(u)
            lds = jsonld_events(p)
            if lds: c.update({k: v for k, v in from_ld(lds[0]).items() if v})
            c["title"] = c.get("title") or clean(meta(p, "og:title") or (re.search(r"<title>(.*?)</title>", p, re.S) or [None, ""])[1])
            if not c.get("image"):
                c["image"] = meta(p, "og:image")
                if not c["image"]:
                    m = re.search(r'<img[^>]+src=["\']([^"\']+(?:posters|upload|event)[^"\']*\.(?:webp|jpe?g|png))', p, re.I)
                    if m: c["image"] = urllib.parse.urljoin(u, m.group(1))
            if not c.get("date"):
                c["page_text"] = clean(re.sub(r"<(script|style)[^>]*>.*?</\1>", " ", p, flags=re.S))[:1500]
        except Exception as e:
            c["error"] = str(e)[:120]
        out.append(c)
    return out

SV_MONTHS = {"januari":1,"februari":2,"mars":3,"april":4,"maj":5,"juni":6,"juli":7,"augusti":8,"september":9,"oktober":10,"november":11,"december":12,
             "january":1,"february":2,"march":3,"may":5,"june":6,"july":7,"august":8,"october":10}

def parse_discotehran(pg, base="https://www.discotehran.se/", today=None):
    """discotehran.se is ONE page (Next.js) with event cards; no per-event URLs.
    Only cards in the 'Upcoming Parties' block count ('More Nights Coming' = no date, 'Past Parties' = over)."""
    today = today or datetime.date.today()
    a = pg.find("Upcoming Parties")
    if a < 0: return []
    b = pg.find("Past Parties", a)
    block = pg[a:b if b > 0 else len(pg)]
    out = []
    for art in re.findall(r"<article\b.*?</article>", block, re.S):
        if "event-soon-card" in art or "event-card-past" in art: continue      # no date yet / already over
        h = re.search(r"<h4[^>]*>(.*?)</h4>", art, re.S)
        if not h: continue
        title = clean(h.group(1))
        dm = re.search(r'<span[^>]*>(\d{1,2})</span>\s*<span[^>]*>([^<]+)</span>', art)
        date = ""
        if dm and dm.group(2).strip().lower() in SV_MONTHS:
            day, mon = int(dm.group(1)), SV_MONTHS[dm.group(2).strip().lower()]
            for y in (today.year, today.year + 1):          # the card shows no year: take the next such date
                try:
                    d = datetime.date(y, mon, day)
                except ValueError:
                    continue
                if d >= today: date = d.isoformat(); break
        def line(emoji):
            m = re.search(r"<p[^>]*>\s*%s\s*(?:<!-- -->)?\s*(.*?)</p>" % emoji, art, re.S)
            return clean(m.group(1)) if m else ""
        tm = re.search(r"(\d{1,2}:\d{2})", line("⏰"))
        place = line("📍")
        parts = [x.strip() for x in place.split(",")]
        img = re.search(r'<img[^>]+src="([^"]+)"', art)
        im = ""
        if img:
            q = urllib.parse.parse_qs(urllib.parse.urlparse(html.unescape(img.group(1))).query)
            im = (q.get("url") or [""])[0] or urllib.parse.urljoin(base, html.unescape(img.group(1)))
        out.append({"title": title, "url": base + "#" + norm(title), "ticket_url": base, "source": "discotehran.se",
                    "date": date, "time": tm.group(1) if tm else "", "venue": parts[0] if parts else "",
                    "address": ", ".join(parts[1:]), "city": parts[-1] if len(parts) > 1 else "", "country": "SE",
                    "price": line("🎟️"), "lineup": line("🎧"), "image": im,
                    "page_text": clean(art)[:800]})
    return out

def site_discotehran():
    return [c for c in parse_discotehran(get("https://www.discotehran.se/")) if not known(c["url"])]

SITES = [
    ("volek.events", site_volek),
    ("javantik.com", site_javantik),
    ("globalsounds.se", lambda: generic("https://globalsounds.se/", "globalsounds.se", r'href=["\']((?:https://globalsounds\.se)?/program/[^"\'#?]+)')),
    ("discotehran.se", site_discotehran),
]

def main():
    keys = load_existing()
    cands, rows = [], []
    for name, fn in SITES:
        read = new = err = 0
        SK[0] = 0
        try:
            items = fn()
        except Exception as e:
            rows.append((name, "COULD NOT READ: %s" % str(e)[:80], 0, 0, 0, SK[0])); continue
        try:
            with open("seen_urls.txt", "a", encoding="utf-8") as sf:
                for c in items: sf.write(c["url"] + "\n")
                for u in EXTRA_SEEN: sf.write(u + "\n")
        except Exception:
            pass
        for c in items:
            read += 1
            if c.get("error"): err += 1
            d = c.get("date", "")
            if d and d < TODAY: continue                                  # past
            cc = (c.get("country") or "").strip().lower()
            if len(cc) == 2 and cc not in EUROPE: continue                # outside Europe
            if is_existing(c, keys): continue                             # already on the site
            c["found"] = TODAY
            cands.append(c); new += 1
        rows.append((name, "ok", read, new, err, SK[0]))
    os.makedirs("findings", exist_ok=True)
    fn_today = "findings/website-%s.json" % TODAY
    try:                                   # keep what earlier runs of today found (never overwrite with less)
        old = json.load(open(fn_today, encoding="utf-8"))
    except Exception:
        old = []
    byurl = {c["url"]: c for c in old}
    for c in cands: byurl[c["url"]] = c
    cands = list(byurl.values())
    json.dump(cands, open(fn_today, "w", encoding="utf-8"), ensure_ascii=False, indent=1)
    lines = ["| Website | Status | New events read | New candidates | Page errors | Already known (skipped) |", "|---|---|---|---|---|---|"]
    for r in rows:
        r = list(r) + [0] * (6 - len(r))
        lines.append("| %s | %s | %s | %s | %s | %s |" % tuple(r))
    open("findings/website-%s.summary.txt" % TODAY, "w").write("\n".join(lines) + "\n")
    print("\n".join(lines))

if __name__ == "__main__":
    try:
        main()
    finally:
        bclose()
