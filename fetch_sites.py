import json, urllib.request, datetime
def get(u): return json.load(urllib.request.urlopen(urllib.request.Request(u, headers={"User-Agent":"Mozilla/5.0"})))
out=[]; page=1
while True:
    try: items=get(f"https://volek.events/wp-json/wp/v2/event_listing?per_page=100&page={page}")
    except Exception: break
    if not items: break
    for i in items: out.append({"title":i["title"]["rendered"],"url":i["link"],"source":"volek.events"})
    page+=1
import os; os.makedirs("findings",exist_ok=True)
json.dump(out,open(f"findings/website-{datetime.date.today()}.json","w"),ensure_ascii=False,indent=1)
