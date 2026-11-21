# BezanBerim website (بزن بریم)
Europe-only site of Kurdish, Persian and Afghan events. Public page is Persian (RTL).

## Files
- index.html + assets/css + assets/js/app.js: the whole front end (plain HTML/CSS/JS, no build step).
- api/api.php: PHP + MySQL (PDO). Table docs(path, coll, data JSON). Collections: events, communities, sources, config, submissions, archive.
- api/config.php: DB login + BB_TOKEN. NOT in Git (copy api/config.example.php). NEVER commit, print or copy its values.
- api/seed-*.json: first data, loaded once by op=seed (admin only).
- thumbs/ and event images: see the safeImg allowlist in app.js before adding a new image host.

## Rules
- Keep RTL and Persian text. Mobile first, check narrow screens.
- Events from the automatic feed jobs are published by api.php op=import (token, complete events only; incomplete ones wait in pending/). Visitor-submitted pages/feeds need admin approval. Do not write events into the live database by hand.
- Past events are hidden by date (isUpcoming in app.js) and archived daily by api.php op=cleanup (token or admin).
- Test site: http://192.168.0.104/bezanberim/ (Apache on this VM, database bb_test). Real site: bezanberim.eu on Strato - do not touch it from here.
- Never commit secrets (config.php, tokens). Do not use sudo unless asked.

## Deploy (by the owner)
Upload changed files to Strato by FTP/SFTP. Do not overwrite the real api/config.php there.
