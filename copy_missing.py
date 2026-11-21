#!/usr/bin/env python3
"""Copy upcoming events that exist on one site but not on the other (same ids), through the API import (token).
Usage:  python copy_missing.py main local [--dry]    -> events on MAIN that LOCAL does not have are published on LOCAL
        python copy_missing.py local main [--dry]    -> events on LOCAL that MAIN does not have are published on MAIN
Reads BB_API_URL / BB_TOKEN from ~/.config/bb/env (local) and ~/.config/bb/env.main (main). Nothing is deleted. Only upcoming events are copied."""
import json, os, re, sys, urllib.request, urllib.error
from datetime import datetime
from zoneinfo import ZoneInfo

FILES = {"local": os.path.expanduser("~/.config/bb/env"), "main": os.path.expanduser("~/.config/bb/env.main")}

def read_env(path):
    out = {}
    for line in open(path, encoding="utf-8"):
        m = re.match(r'\s*(?:export\s+)?([A-Z_]+)=(.*)$', line.strip())
        if m: out[m.group(1)] = m.group(2).strip().strip('"').strip("'")
    return out

def events(env):
    req = urllib.request.Request(env["BB_API_URL"] + "?op=list&c=events", headers={"User-Agent": "bb-copy/1.0"})
    with urllib.request.urlopen(req, timeout=60) as r:
        return {d["id"]: d["data"] for d in json.loads(r.read().decode())["docs"]}

def main():
    args = [a for a in sys.argv[1:] if not a.startswith("--")]
    if len(args) != 2 or args[0] not in FILES or args[1] not in FILES or args[0] == args[1]: sys.exit(__doc__)
    dry = "--dry" in sys.argv
    src, dst = read_env(FILES[args[0]]), read_env(FILES[args[1]])
    for n, e in (("source", src), ("target", dst)):
        if not e.get("BB_API_URL"): sys.exit("%s: BB_API_URL missing" % n)
    if not dst.get("BB_TOKEN"): sys.exit("target: BB_TOKEN missing")
    today = datetime.now(ZoneInfo("Europe/Stockholm")).strftime("%Y-%m-%d")
    a, b = events(src), events(dst)
    missing = []
    for i, d in a.items():
        end = d.get("endDate") or d.get("date") or ""
        if i in b or not end >= today: continue
        e = {k: d.get(k, "") for k in ("title", "date", "endDate", "time", "venue", "city", "country", "price", "type", "image", "url", "source", "organizer", "dateLabel")}
        e["id"] = i; e["ongoing"] = bool(d.get("ongoing"))
        missing.append(e)
    print("%s has %d events, %s has %d; upcoming events missing on %s: %d" % (args[0], len(a), args[1], len(b), args[1], len(missing)))
    for e in missing: print("  -", e["date"], e["city"], "|", e["title"])
    if dry or not missing: return
    req = urllib.request.Request(dst["BB_API_URL"] + "?op=import", data=json.dumps({"events": missing, "publish": True}).encode(),
                                 headers={"Content-Type": "application/json", "X-BB-Token": dst["BB_TOKEN"], "User-Agent": "bb-copy/1.0"}, method="POST")
    try:
        res = json.loads(urllib.request.urlopen(req, timeout=60).read().decode())
    except urllib.error.HTTPError as e:
        sys.exit("target answered HTTP %s: %s" % (e.code, e.read().decode()[:200]))
    print("copied: published=%s waiting-for-admin=%s skipped=%s" % (res.get("published", 0), res.get("queued", res.get("added", 0)), res.get("skipped")))

main()
