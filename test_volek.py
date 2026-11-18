print("start", flush=True)
from playwright.sync_api import sync_playwright
try:
    with sync_playwright() as p:
        b = p.firefox.launch(headless=True)
        pg = b.new_page()
        r = pg.goto("https://volek.events/event/kurdish-party-in-holland/", timeout=60000)
        print("status:", r.status if r else None, flush=True)
        pg.wait_for_timeout(3000)
        print("title:", pg.title(), flush=True)
        print(pg.inner_text("body")[:600], flush=True)
        b.close()
except Exception as e:
    print("ERROR:", e, flush=True)
print("end", flush=True)
