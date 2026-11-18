#!/usr/bin/env python3
"""Daily website reader for BezanBerim. Python standard library only.
Reads volek, javantik, kurdish.events, globalsounds, discotehran and writes
findings/website-YYYY-MM-DD.json  (candidates)  +  findings/website-YYYY-MM-DD.summary.txt
The Claude step (prompts/daily.txt) then cleans the candidates."""
import base64, datetime, html, json, os, re, sys, time, urllib.error, urllib.parse, urllib.request

TODAY = datetime.date.today().isoformat()
UA = "Mozilla/5.0 (X11; Linux x86_64; rv:128.0) Gecko/20100101 Firefox/128.0"
DELAY = 1.0
EUROPE = set("al ad at ba be bg by ch cy cz de dk ee es fi fr gb ge gr hr hu ie is it li lt lu lv mc md me mk mt nl no pl pt ro rs se si sk sm ua va xk".split())

def get(url, accept="text/html,*/*"):
    last = None
    for n in range(2):
        try:
            req = urllib.request.Request(url, headers={"User-Agent": UA, "Accept": accept, "Accept-Language": "en"})
            with urllib.request.urlopen(req, timeout=30) as r:
                return r.read().decode("utf-8", "replace")
        except urllib.error.HTTPError as e:
            last = e
            if e.code in (404, 410): raise          # gone: do not retry
            time.sleep(10 * (n + 1))                # 454/429/403/5xx: short wait, then give up
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
def known(u): return u.rstrip("/") in KNOWN

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
        if fails >= 3 or time.time() - t0 > 360:
            log("volek: stopping early (blocked or too slow); rest will be tried tomorrow"); break
        time.sleep(3)
        log("volek %d/%d %s" % (idx + 1, len(urls), link))
        c = {"title": title, "url": link, "ticket_url": link, "source": "volek.events"}
        try:
            pg = get(link)
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

SITES = [
    ("volek.events", site_volek),
    ("javantik.com", site_javantik),
    ("kurdish.events", lambda: generic("https://www.kurdish.events/", "kurdish.events", r'href=["\']((?:https://www\.kurdish\.events)?/events/[^"\'#?]+)')),
    ("globalsounds.se", lambda: generic("https://globalsounds.se/", "globalsounds.se", r'href=["\']((?:https://globalsounds\.se)?/program/[^"\'#?]+)')),
    ("discotehran.se", lambda: generic("https://www.discotehran.se/", "discotehran.se", r'href=["\']((?:https://www\.discotehran\.se)?/[^"\'#?]*(?:event|konsert|biljett|ticket)[^"\'#?]*)')),
]

def main():
    keys = load_existing()
    cands, rows = [], []
    for name, fn in SITES:
        read = new = err = 0
        try:
            items = fn()
        except Exception as e:
            rows.append((name, "COULD NOT READ: %s" % str(e)[:80], 0, 0)); continue
        try:
            with open("seen_urls.txt", "a", encoding="utf-8") as sf:
                for c in items: sf.write(c["url"] + "\n")
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
        rows.append((name, "ok", read, new, err))
    os.makedirs("findings", exist_ok=True)
    json.dump(cands, open("findings/website-%s.json" % TODAY, "w", encoding="utf-8"), ensure_ascii=False, indent=1)
    lines = ["| Website | Status | Events read | New candidates | Page errors |", "|---|---|---|---|---|"]
    for r in rows:
        r = list(r) + [0] * (5 - len(r))
        lines.append("| %s | %s | %s | %s | %s |" % tuple(r))
    open("findings/website-%s.summary.txt" % TODAY, "w").write("\n".join(lines) + "\n")
    print("\n".join(lines))

if __name__ == "__main__":
    main()
