#!/bin/bash
# Manual one-shot, EVERYTHING: websites job, then Instagram job (Apify credit!). Both publish complete events on the LOCAL and MAIN sites (incomplete ones wait in the admin queue).
# Usage:  ./sync_all.sh                 websites + Instagram
#         ./sync_all.sh --pull          git pull first (done once, before the websites job)
#         ./sync_all.sh --deploy        also upload the site files to Strato (after the websites job)
#         ./sync_all.sh --no-social     websites only (same as ./sync_websites.sh)
cd ~/BB || exit 1
W=(); S=(); SOCIAL=1
for a in "$@"; do case "$a" in
  --pull) W+=(--pull);;
  --deploy) W+=(--deploy);;
  --no-social) SOCIAL=0;;
esac; done
./sync_websites.sh "${W[@]}" || echo "(websites job ended with an error - continuing)"
if [ "$SOCIAL" = 1 ]; then echo; echo "################ INSTAGRAM ################"; ./sync_social.sh "${S[@]}"; fi
