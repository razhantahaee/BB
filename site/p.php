<?php
// Router for the SEO landing pages (see .htaccess). Read-only.
require __DIR__ . '/seo.inc.php';
seo_route($_GET['r'] ?? '');
