#!/usr/bin/env python3
"""Talks to the BezanBerim site API with the secret token.
Usage:  python site_sync.py cleanup [--dry]   -> archive events whose date is before today (Stockholm); today's events stay until tomorrow
         python site_sync.py export                 -> rewrite sources.txt from the site's approved pages + upcoming events
         python site_sync.py import [file] [--queue] -> send findings/website-checked-<today>.json. Complete events (title, upcoming date, European country) are PUBLISHED directly; incomplete ones wait in pending/ for Hosein. --queue = send everything to pending/ instead.
Needs in ~/.config/bb/env:  export BB_API_URL="https://bezanberim.eu/api/api.php"   export BB_TOKEN="..."  (never commit these)"""
import json, os, re, sys, urllib.request, urllib.error
from datetime import datetime
from zoneinfo import ZoneInfo

URL = os.environ.get("BB_API_URL", "")
TOKEN = os.environ.get("BB_TOKEN", "")

def call(op, extra="", body=None):
    if not URL or not TOKEN:
        sys.exit("BB_API_URL / BB_TOKEN not set (skipping)")
    req = urllib.request.Request("%s?op=%s%s" % (URL, op, extra), data=json.dumps(body or {}).encode(),
                                 headers={"Content-Type": "application/json", "X-BB-Token": TOKEN,
                                          "User-Agent": "bb-sync/1.0"}, method="POST")
    try:
        with urllib.request.urlopen(req, timeout=60) as r:
            return json.loads(r.read().decode())
    except urllib.error.HTTPError as e:
        sys.exit("site answered HTTP %s: %s" % (e.code, e.read().decode()[:200]))

if __name__ == "__main__":
    cmd = sys.argv[1] if len(sys.argv) > 1 else ""
    if cmd == "cleanup":
        res = call("cleanup", "&dry=1" if "--dry" in sys.argv else "")
        print("cleanup: today=%s archived=%s wouldArchive=%s kept=%s" % (res.get("today"), res.get("archived"), res.get("wouldArchive"), res.get("kept")))
        for line in res.get("list", []): print("  -", line)
    elif cmd == "export":
        # approved pages from the site -> sources.txt (adds new ones, removes rejected ones; keeps websites/comments); upcoming events -> "Existing events"
        res = call("export")
        def key(u):
            u = u.strip().lower().rstrip("/"); u = re.sub(r"^https?://(www\.)?", "", u); return u
        def norm(x):
            p, h, u = x.get("platform"), (x.get("handle") or ""), (x.get("url") or "")
            link = u if u.startswith("http") else ("https://www.instagram.com/%s/" % h.lstrip("@") if p == "instagram" else "https://www.facebook.com/%s" % h.lstrip("@") if p == "facebook" else "https://%s" % h)
            return p, link.rstrip("/") + ("/" if p == "instagram" else "")
        lines = open("sources.txt", encoding="utf-8").read().split("\n")
        approved = [norm(x) for x in res["sources"] if x["status"] == "approved"]
        rejected = {key(norm(x)[1]) for x in res["sources"] if x["status"] == "rejected"}
        add = {"instagram": [], "facebook": [], "website": []}
        for p, link in approved:
            if p in add and not any(x in link for x in ("/groups/", "/search", "facebook.com/search")): add[p].append(link)
        out, sec, seen = [], "", set()
        def flush():
            for l in add.get(sec, []):
                if key(l) not in seen: out.append(l); seen.add(key(l))
        for ln in lines:
            t = ln.strip()
            if t.startswith("#"):
                flush()
                low = t.lower()
                sec = "instagram" if low.startswith("# instagram") else "facebook" if low.startswith("# facebook") else "website" if low.startswith("# websites") else "events" if low.startswith("# existing") else ""
                out.append(ln)
                if sec == "events":
                    out.append("# Title | Date YYYY-MM-DD | City  (refreshed from the site on %s)" % res.get("today"))
                    for e in sorted(res["events"], key=lambda e: e["date"]):
                        if e["title"]: out.append("%s | %s | %s" % (e["title"], e["date"], e["city"]))
                    seen = set(); break
                seen = set(); continue
            if sec in ("instagram", "facebook", "website") and t.startswith("http"):
                if key(t) in rejected: continue
                if key(t) in seen: continue
                seen.add(key(t))
            if sec == "facebook" and t.startswith("# (none yet"): continue
            out.append(ln)
        if sec != "events": flush()
        open("sources.txt", "w", encoding="utf-8").write("\n".join(out).rstrip("\n") + "\n")
        print("export: instagram=%d facebook=%d websites=%d events=%d" % (sum(1 for l in out if "instagram.com/" in l and l.startswith("http")), sum(1 for l in out if "facebook.com/" in l and l.startswith("http")), len(add["website"]), len(res["events"])))
    elif cmd == "import":
        today = datetime.now(ZoneInfo("Europe/Stockholm")).strftime("%Y-%m-%d")
        args = [a for a in sys.argv[2:] if not a.startswith("--")]
        path = args[0] if args else "findings/website-checked-%s.json" % today
        try:
            items = json.load(open(path, encoding="utf-8"))
        except Exception as e:
            sys.exit("import: cannot read %s (%s)" % (path, e))
        out = []
        for e in items if isinstance(items, list) else []:
            title = str(e.get("title") or "").strip()
            if not title: continue
            cc = str(e.get("country") or "").lower()
            cc = cc if re.fullmatch(r"[a-z]{2}", cc) else "xx"
            date = str(e.get("date") or "")
            date = date if re.fullmatch(r"\d{4}-\d{2}-\d{2}", date) else ""
            slug = re.sub(r"[^a-z0-9]+", "-", title.lower()).strip("-")[:40] or "event"
            eid = "%s-%s-%s" % (cc, date.replace("-", "") or "undated", slug)
            link = e.get("ticket_url") if str(e.get("ticket_url") or "").startswith("http") else e.get("source_url")
            u = lambda k: "" if str(e.get(k) or "") == "unknown" else str(e.get(k) or "")
            out.append({"id": eid, "title": title, "date": date, "time": u("time"), "city": u("city"), "country": cc if cc != "xx" else "",
                        "endDate": u("endDate") if re.fullmatch(r"\d{4}-\d{2}-\d{2}", u("endDate")) else "", "ongoing": "1" if e.get("ongoing") else "", "venue": u("venue"), "price": u("price"), "type": u("type"), "image": u("image"), "url": link or "",
                        "source": str(e.get("source_url") or ""), "organizer": u("organizer"), "notes": u("notes"),
                        "endDate": u("endDate"), "ongoing": bool(e.get("ongoing")), "dateLabel": u("dateLabel")})
        if not out:
            print("import: nothing to send"); sys.exit(0)
        publish = "--queue" not in sys.argv
        res = call("import", "", {"events": out, "publish": publish})
        print("import: sent=%d published=%s waiting-for-admin=%s skipped(already known)=%s" % (len(out), res.get("published", 0), res.get("queued", res.get("added", 0)), res.get("skipped")))
    else:
        sys.exit(__doc__)
