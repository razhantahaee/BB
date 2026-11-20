#!/usr/bin/env python3
"""Talks to the BezanBerim site API with the secret token.
Usage:  python site_sync.py cleanup [--dry]   -> archive events whose date is before today (Stockholm); today's events stay until tomorrow
         python site_sync.py import [file]     -> send findings/website-checked-<today>.json to the site's approval queue (pending/). Nothing goes live without Hosein.
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
    elif cmd == "import":
        today = datetime.now(ZoneInfo("Europe/Stockholm")).strftime("%Y-%m-%d")
        path = sys.argv[2] if len(sys.argv) > 2 else "findings/website-checked-%s.json" % today
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
        res = call("import", "", {"events": out})
        print("import: sent=%d added=%s skipped(already known)=%s" % (len(out), res.get("added"), res.get("skipped")))
    else:
        sys.exit(__doc__)
