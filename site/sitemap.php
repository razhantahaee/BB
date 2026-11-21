<?php
// sitemap.xml: only pages that have content (no empty pages).
require __DIR__ . '/seo.inc.php';
if (seo_data() === null) { http_response_code(503); exit; }
header('Content-Type: application/xml; charset=utf-8');
header('Cache-Control: public, max-age=3600');
$today = seo_today(); $urls = array(array('', $today));
$P = seo_places(); $C = seo_cats(); $G = seo_group_cats();
foreach (array_keys($C) as $ck) if (seo_events('europe', $ck)) $urls[] = array($ck, $today);
foreach ($P as $pk => $p) {
  if ($pk === 'europe') continue;
  if (seo_events($pk)) $urls[] = array($pk, $today);
  foreach (array_keys($C) as $ck) if (seo_events($pk, $ck)) $urls[] = array($pk . '/' . $ck, $today);
}
if (seo_groups()) $urls[] = array('groups', $today);
foreach (array_keys($G) as $gk) if (seo_groups($gk)) $urls[] = array('groups/' . $gk, $today);
foreach (seo_data()['events'] as $e) if ($e['upcoming']) $urls[] = array('event/' . $e['id'], preg_match('/^\d{4}-\d{2}-\d{2}$/', $e['addedAt']) ? $e['addedAt'] : $today);
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as $u) echo '<url><loc>' . htmlspecialchars(seo_abs($u[0]), ENT_XML1) . '</loc><lastmod>' . $u[1] . '</lastmod></url>' . "\n";
echo '</urlset>' . "\n";
