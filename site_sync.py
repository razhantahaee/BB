#!/usr/bin/env python3
"""Talks to the BezanBerim site API with the secret token.
Usage:  python site_sync.py cleanup [--dry]   -> archive events whose date is before today (Stockholm); today's events stay until tomorrow
Needs in ~/.config/bb/env:  export BB_API_URL="https://bezanberim.eu/api/api.php"   export BB_TOKEN="..."  (never commit these)"""
import json, os, sys, urllib.request, urllib.error

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
    else:
        sys.exit(__doc__)
