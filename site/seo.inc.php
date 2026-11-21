<?php
// بزن بریم · صفحه‌های سئو (فرود) — HTML کامل از طرف سرور، تا گوگل بدون اجرای جاوااسکریپت هم ببیند.
// این فایل فقط خواندن از دیتابیس می‌کند؛ هیچ چیزی نمی‌نویسد.
// برای اضافه کردن شهر یا دسته‌ی جدید فقط آرایه‌های SEO_PLACES و SEO_CATS و SEO_GROUP_CATS را ویرایش کنید.

const SEO_PROD_HOST = 'bezanberim.eu';
const SEO_BRAND = 'بزن بریم';

// ---------------------------------------------------------------- settings
// key = آدرس (لاتین)؛ aliases = نام شهر در داده‌ها (بدون حرف بزرگ و بدون اِعراب/نقطه‌های خاص)
function seo_places() {
  return array(
    'europe' => array('kind' => 'all', 'fa' => 'اروپا', 'en' => 'Europe', 'cc' => '', 'aliases' => array(), 'fa_alts' => array(), 'local' => array()),
    'sweden' => array('kind' => 'country', 'cc' => 'se', 'fa' => 'سوئد', 'en' => 'Sweden', 'aliases' => array(), 'fa_alts' => array('سوید'),
      'local' => array('party' => 'persisk fest, kurdisk fest', 'concert' => 'persisk konsert, kurdisk konsert')),
    'germany' => array('kind' => 'country', 'cc' => 'de', 'fa' => 'آلمان', 'en' => 'Germany', 'aliases' => array(), 'fa_alts' => array(),
      'local' => array('party' => 'persische Party, kurdische Party', 'concert' => 'persisches Konzert, kurdisches Konzert')),
    'stockholm' => array('kind' => 'city', 'cc' => 'se', 'parent' => 'sweden', 'fa' => 'استکهلم', 'en' => 'Stockholm',
      'aliases' => array('stockholm', 'استکهلم', 'استکلهم', 'solna', 'sundbyberg', 'kista', 'saltsjobaden', 'nacka', 'jarfalla', 'huddinge', 'sollentuna', 'taby', 'handen', 'upplands vasby'),
      'fa_alts' => array('استکلهم', 'استکهولم'), 'local' => array('party' => 'persisk fest Stockholm, kurdisk fest Stockholm', 'concert' => 'persisk konsert Stockholm, kurdisk konsert Stockholm')),
    'gothenburg' => array('kind' => 'city', 'cc' => 'se', 'parent' => 'sweden', 'fa' => 'یوتبوری', 'en' => 'Gothenburg',
      'aliases' => array('goteborg', 'gothenburg', 'gotheburg', 'یوتبوری', 'گوتنبرگ', 'گوتبرگ', 'molndal', 'partille', 'kungsbacka'),
      'fa_alts' => array('گوتنبرگ', 'گوتبرگ', 'Göteborg'), 'local' => array('party' => 'persisk fest Göteborg, kurdisk fest Göteborg', 'concert' => 'persisk konsert Göteborg, kurdisk konsert Göteborg')),
    'berlin' => array('kind' => 'city', 'cc' => 'de', 'parent' => 'germany', 'fa' => 'برلین', 'en' => 'Berlin',
      'aliases' => array('berlin', 'برلین'), 'fa_alts' => array(), 'local' => array('party' => 'persische Party Berlin, kurdische Party Berlin', 'concert' => 'persisches Konzert Berlin, kurdisches Konzert Berlin')),
    'cologne' => array('kind' => 'city', 'cc' => 'de', 'parent' => 'germany', 'fa' => 'کلن', 'en' => 'Cologne',
      'aliases' => array('koln', 'cologne', 'کلن', 'کلون'), 'fa_alts' => array('کلون', 'Köln'), 'local' => array('party' => 'persische Party Köln, kurdische Party Köln', 'concert' => 'persisches Konzert Köln, kurdisches Konzert Köln')),
    'hamburg' => array('kind' => 'city', 'cc' => 'de', 'parent' => 'germany', 'fa' => 'هامبورگ', 'en' => 'Hamburg',
      'aliases' => array('hamburg', 'هامبورگ'), 'fa_alts' => array(), 'local' => array('party' => 'persische Party Hamburg, kurdische Party Hamburg', 'concert' => 'persisches Konzert Hamburg, kurdisches Konzert Hamburg')),
  );
}

function seo_cats() {
  return array(
    'party' => array('fa' => 'پارتی', 'fa_pl' => 'پارتی‌ها و شب‌های ایرانی و کوردی', 'en' => 'party', 'cats' => array('party', 'celebration'),
      'words' => 'پارتی، شب، دی‌جی، یلدا و جشن'),
    'concert' => array('fa' => 'کنسرت', 'fa_pl' => 'کنسرت‌ها', 'en' => 'concert', 'cats' => array('concert'),
      'words' => 'کنسرت و اجرای زنده'),
  );
}

function seo_group_cats() {
  return array(
    'sports' => array('fa' => 'گروه ورزشی', 'title' => 'گروه‌های ورزشی ایرانی و کوردی', 'en' => 'sports groups',
      'rx' => '/ورزش|والیبال|بدمینتون|پینگ|فوتبال|فوتسال|بسکتبال|شنا|تنیس|کوهنوردی|دوچرخه|دو و میدانی|volley|badminton|pingpong|ping-pong|sport|football|futsal|hiking|basket/iu',
      'intro' => 'والیبال، بدمینتون، پینگ‌پنگ و دیگر ورزش‌های گروهی'),
    'culture' => array('fa' => 'گروه فرهنگی', 'title' => 'گروه‌ها و انجمن‌های فرهنگی ایرانی و کوردی', 'en' => 'cultural associations',
      'rx' => '/فرهنگ|هنر|فیلم|سخنرانی|نمایش|تئاتر|ادبیات|شعر|آکادمی|انجمن|culture|kultur|film|lecture|academy|association|kuf/iu',
      'intro' => 'انجمن‌های فرهنگی، کانون‌های جوانان، نمایش فیلم، سخنرانی و نشست‌های آموزشی'),
    'choir' => array('fa' => 'گروه آواز و کر', 'title' => 'گروه‌های آواز، کر و موسیقی', 'en' => 'choirs and music groups',
      'rx' => '/آواز|هم‌آوازان|هم آوازان|کر\b|گروه کر|موسیقی|تنبور|دف|ساز|choir|chorus|music|band/iu',
      'intro' => 'گروه‌های آواز، کر و موسیقی سنتی'),
    'recreation' => array('fa' => 'گروه تفریحی', 'title' => 'گروه‌های تفریحی و دورهمی', 'en' => 'social and recreation groups',
      'rx' => '/تفریح|سرگرمی|بازی|مافیا|دورهمی|گردش|سفر|پیک‌نیک|کمپ|board|game|social|mafia|dorehami/iu',
      'intro' => 'دورهمی، بازی فکری، شب مافیا و برنامه‌های تفریحی'),
    'books' => array('fa' => 'گروه کتابخوانی', 'title' => 'گروه‌ها و باشگاه‌های کتابخوانی فارسی', 'en' => 'book clubs',
      'rx' => '/کتاب|داستان|خاطره|ادبی|book|stories|reading/iu',
      'intro' => 'باشگاه‌های کتاب‌خوانی، نقد کتاب، داستان و خاطره‌نویسی'),
  );
}

// ---------------------------------------------------------------- helpers
function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

function seo_host() {
  $h = strtolower($_SERVER['HTTP_HOST'] ?? '');
  $h = preg_replace('/:\d+$/', '', $h);
  return preg_replace('/^www\./', '', $h);
}
function seo_is_prod() { return seo_host() === SEO_PROD_HOST; }
function seo_base() { return rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/'); }
function seo_origin() {
  if (seo_is_prod()) return 'https://' . SEO_PROD_HOST;
  $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
  return ($https ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'localhost');
}
function seo_url($path = '') { // internal link (works in a sub folder too)
  $p = trim($path, '/');
  return seo_base() . '/' . ($p === '' ? '' : $p . '/');
}
function seo_abs($path = '') { return seo_origin() . seo_url($path); }

function seo_nrm($s) {
  $s = trim((string)$s);
  $s = str_replace(array('ي', 'ك', "\xE2\x80\x8C"), array('ی', 'ک', ' '), $s);
  $s = strtr($s, array('Ö' => 'o', 'Ä' => 'a', 'Å' => 'a', 'Ü' => 'u', 'É' => 'e', 'Ø' => 'o', 'İ' => 'i',
    'ö' => 'o', 'ä' => 'a', 'å' => 'a', 'ü' => 'u', 'é' => 'e', 'è' => 'e', 'ß' => 'ss', 'ø' => 'o', 'æ' => 'ae', 'ñ' => 'n', 'ç' => 'c', 'ı' => 'i', 'š' => 'sh', 'á' => 'a', 'í' => 'i', 'ó' => 'o'));
  $s = strtolower($s);
  return trim(preg_replace('/\s+/u', ' ', $s));
}
function seo_fa_num($n) { return strtr((string)$n, array('0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹')); }
function seo_fa_date($iso) {
  if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', (string)$iso, $m)) return '';
  $mon = array('', 'ژانویه', 'فوریه', 'مارس', 'آوریل', 'مه', 'ژوئن', 'ژوئیه', 'اوت', 'سپتامبر', 'اکتبر', 'نوامبر', 'دسامبر');
  return seo_fa_num((int)$m[3]) . ' ' . $mon[(int)$m[2]] . ' ' . seo_fa_num($m[1]);
}
function seo_cut($s, $n) { // character-safe truncate (no mbstring needed)
  $s = trim(preg_replace('/\s+/u', ' ', (string)$s));
  if (preg_match('/^.{0,' . (int)$n . '}/us', $s, $m)) { $t = $m[0]; } else { $t = $s; }
  return $t === $s ? $s : rtrim($t, " ,.;،") . '…';
}
function seo_today() { $d = new DateTime('now', new DateTimeZone('Europe/Stockholm')); return $d->format('Y-m-d'); }

function seo_countries() {
  return array('se' => array('سوئد', 'Sweden'), 'de' => array('آلمان', 'Germany'), 'gb' => array('بریتانیا', 'United Kingdom'),
    'nl' => array('هلند', 'Netherlands'), 'no' => array('نروژ', 'Norway'), 'dk' => array('دانمارک', 'Denmark'), 'fr' => array('فرانسه', 'France'),
    'at' => array('اتریش', 'Austria'), 'ch' => array('سوئیس', 'Switzerland'), 'be' => array('بلژیک', 'Belgium'), 'fi' => array('فنلاند', 'Finland'),
    'it' => array('ایتالیا', 'Italy'), 'es' => array('اسپانیا', 'Spain'), 'tr' => array('ترکیه', 'Turkey'), 'gr' => array('یونان', 'Greece'),
    'cz' => array('چک', 'Czechia'), 'ie' => array('ایرلند', 'Ireland'), 'pl' => array('لهستان', 'Poland'), 'pt' => array('پرتغال', 'Portugal'),
    'hu' => array('مجارستان', 'Hungary'), 'lu' => array('لوکزامبورگ', 'Luxembourg'));
}

// همان منطق دسته‌بندی سایت (app.js → catOf)
function seo_cat_of($e) {
  $t = strtolower($e['title'] . ' ' . ($e['note'] ?? ''));
  if (preg_match('/yalda|یلدا|new year|nowruz|newroz|norooz|x-mas|christmas|flag day|ala kurdistan|celebration|جشن|homecoming/u', $t)) return 'celebration';
  if (preg_match('/talk|book|exhibition|film|screening|santur|author|circle|yoga|theat|standup|stand-up|کتاب|نمایش|فیلم/u', $t)) return 'culture';
  if (preg_match('/party|night|\bdj\b|club|afro|ladies|tjej|fest\b|parti|پارتی/u', $t)) return 'party';
  return 'concert';
}

function seo_safe_img($u) {
  $u = (string)$u;
  if (preg_match('#^thumbs/[a-z0-9._-]+\.(jpe?g|png|webp)$#i', $u)) return seo_base() . '/' . $u;
  if (preg_match('#^https://(www\.javantik\.com/cdn/events-image|volek\.events/wp-content/uploads|www\.modernamuseet\.se/app/uploads|www\.kingsplace\.co\.uk/wp-content/uploads|rrorwfctzuxjwjbtuhvt\.supabase\.co/storage/v1/ob)#', $u)) return $u;
  return '';
}
function seo_safe_link($u) { $u = (string)$u; return preg_match('#^https?://#i', $u) ? $u : ''; }

// ---------------------------------------------------------------- data
function seo_data() {
  static $d = null;
  if ($d !== null) return $d;
  $rows = array();
  $cfg = __DIR__ . '/api/config.php';
  if (is_file($cfg)) {
    require_once $cfg;
    try {
      $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
      foreach ($pdo->query("SELECT path, data FROM docs WHERE coll IN ('events','communities')")->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $x = json_decode($r['data'], true);
        if (is_array($x)) $rows[] = array(substr($r['path'], 0, strpos($r['path'], '/')), substr($r['path'], strpos($r['path'], '/') + 1), $x);
      }
    } catch (Exception $e) { return $d = null; }
  } else { // local test without a database: use the seed files
    foreach (array('events' => 'seed-events.json', 'communities' => 'seed-communities.json') as $coll => $f) {
      $j = @json_decode((string)@file_get_contents(__DIR__ . '/api/' . $f), true);
      foreach ((array)$j as $it) if (isset($it['id'], $it['data'])) $rows[] = array($coll, $it['id'], $it['data']);
    }
  }
  $cc = seo_countries(); $today = seo_today(); $ev = array(); $gr = array();
  foreach ($rows as $r) {
    list($coll, $id, $x) = $r;
    if ($coll === 'events') {
      $e = array('id' => $id, 'title' => trim((string)($x['title'] ?? '')), 'date' => (string)($x['date'] ?? ''), 'endDate' => (string)($x['endDate'] ?? ''),
        'time' => (string)($x['time'] ?? ''), 'venue' => (string)($x['venue'] ?? ''), 'city' => trim((string)($x['city'] ?? '')), 'country' => strtolower((string)($x['country'] ?? '')),
        'price' => (string)($x['price'] ?? ''), 'type' => (string)($x['type'] ?? ''), 'url' => (string)($x['url'] ?? ''), 'image' => (string)($x['image'] ?? ''),
        'organizer' => (string)($x['organizer'] ?? ''), 'note' => (string)($x['note'] ?? ''), 'addedAt' => (string)($x['addedAt'] ?? ''));
      if ($e['title'] === '' || !isset($cc[$e['country']]) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $e['date'])) continue;
      $end = preg_match('/^\d{4}-\d{2}-\d{2}$/', $e['endDate']) ? $e['endDate'] : $e['date'];
      $e['upcoming'] = $end >= $today;
      $e['cat'] = seo_cat_of($e);
      $e['cityn'] = seo_nrm($e['city']);
      $ev[$id] = $e;
    } else {
      $g = array('id' => $id, 'name' => (string)($x['name'] ?? ''), 'nameLatin' => (string)($x['nameLatin'] ?? ''), 'desc' => (string)($x['desc'] ?? ''), 'city' => (string)($x['city'] ?? ''),
        'country' => strtolower((string)($x['country'] ?? '')), 'kind' => (string)($x['kind'] ?? ''), 'url' => (string)($x['url'] ?? ''), 'type' => (string)($x['type'] ?? ''),
        'order' => (int)($x['order'] ?? 99), 'logo' => (string)($x['logo'] ?? ''));
      if ($g['name'] === '') continue;
      $gr[$id] = $g;
    }
  }
  uasort($ev, function ($a, $b) { return strcmp($a['date'], $b['date']) ?: strcmp($a['title'], $b['title']); });
  uasort($gr, function ($a, $b) { return $a['order'] <=> $b['order'] ?: strcmp($a['name'], $b['name']); });
  return $d = array('events' => $ev, 'groups' => $gr, 'today' => $today);
}

function seo_in_place($e, $pk) {
  $P = seo_places(); $p = $P[$pk] ?? null;
  if (!$p) return false;
  if ($p['kind'] === 'all') return true;
  if ($p['kind'] === 'country') return $e['country'] === $p['cc'];
  if ($e['country'] !== $p['cc']) return false;
  foreach ($p['aliases'] as $a) {
    $a = seo_nrm($a);
    if ($e['cityn'] === $a) return true;
    if (strlen($a) >= 5 && preg_match('/(^|[^a-z])' . preg_quote($a, '/') . '($|[^a-z])/', $e['cityn'])) return true;
  }
  return false;
}

// برنامه‌های پیش‌رو برای یک مکان + دسته
function seo_events($pk, $ck = '') {
  $D = seo_data(); $C = seo_cats(); $out = array();
  foreach ($D['events'] as $e) {
    if (!$e['upcoming'] || !seo_in_place($e, $pk)) continue;
    if ($ck !== '' && !in_array($e['cat'], $C[$ck]['cats'], true)) continue;
    $out[] = $e;
  }
  return $out;
}

function seo_group_matches($g, $gk) {
  $G = seo_group_cats(); $c = $G[$gk];
  $hay = $g['name'] . ' ' . $g['nameLatin'] . ' ' . $g['desc'] . ' ' . $g['id'];
  if (preg_match($c['rx'], $hay)) return true;
  return false;
}
function seo_groups($gk = '') {
  $D = seo_data(); $out = array();
  foreach ($D['groups'] as $g) {
    if (!isset(seo_countries()[$g['country']])) continue;
    if ($g['kind'] === '' ) continue;
    if ($gk === '' || seo_group_matches($g, $gk)) $out[] = $g;
  }
  return $out;
}

function seo_type_label($t) { return $t === 'kurdish' ? 'کوردی' : ($t === 'both' ? 'ایرانی و کوردی' : 'ایرانی / افغانی'); }

// ---------------------------------------------------------------- html pieces
function seo_card($e) {
  $img = seo_safe_img($e['image']);
  $when = seo_fa_date($e['date']) . ($e['endDate'] !== '' && $e['endDate'] !== $e['date'] ? ' تا ' . seo_fa_date($e['endDate']) : '');
  $meta = array();
  if ($e['time'] !== '') $meta[] = '<span class="ltr">' . h($e['time']) . '</span>';
  if ($e['venue'] !== '') $meta[] = '<bdi>' . h($e['venue']) . '</bdi>';
  $cc = seo_countries(); $place = trim($e['city'] . '، ' . $cc[$e['country']][0], '، ');
  $meta[] = '<bdi>' . h($place) . '</bdi>';
  if ($e['price'] !== '' && stripos($e['price'], 'see ') !== 0) $meta[] = '<bdi>' . h($e['price']) . '</bdi>';
  $go = seo_safe_link($e['url']);
  return '<article class="ev">' .
    ($img ? '<a class="evimg" href="' . h(seo_url('event/' . $e['id'])) . '" tabindex="-1" aria-hidden="true"><img src="' . h($img) . '" alt="" loading="lazy" width="96" height="120" onerror="this.parentNode.style.display=\'none\'"></a>' : '') .
    '<div class="evb"><span class="tag ' . h($e['type']) . '">' . h(seo_type_label($e['type'])) . '</span>' .
    '<h3 dir="auto"><a href="' . h(seo_url('event/' . $e['id'])) . '">' . h($e['title']) . '</a></h3>' .
    '<p class="when"><time datetime="' . h($e['date']) . '">' . h($when) . '</time></p>' .
    '<p class="meta">' . implode(' · ', $meta) . '</p>' .
    ($go ? '<a class="go" href="' . h($go) . '" target="_blank" rel="noopener nofollow">لینک بلیت و اطلاعات ←</a>' : '') .
    '</div></article>';
}

function seo_group_card($g) {
  $cc = seo_countries(); $url = seo_safe_link($g['url']);
  $place = trim($g['city'] . '، ' . ($cc[$g['country']][0] ?? ''), '، ');
  return '<article class="gc"><h3 dir="auto">' . h($g['name']) . '</h3>' .
    ($g['nameLatin'] !== '' && $g['nameLatin'] !== $g['name'] && !preg_match('#^(https?://|www\.)#i', $g['nameLatin']) ? '<p class="gl ltr">' . h($g['nameLatin']) . '</p>' : '') .
    ($g['desc'] !== '' && $g['desc'] !== $g['name'] ? '<p dir="auto">' . h($g['desc']) . '</p>' : '') .
    '<p class="meta">' . h($place) . ($g['kind'] !== '' && $g['kind'] !== 'زیرگروه' ? ' · ' . h($g['kind']) : '') . '</p>' .
    ($url ? '<a class="go" href="' . h($url) . '" target="_blank" rel="noopener nofollow">صفحه‌ی گروه ←</a>' : '') . '</article>';
}

function seo_event_ld($e, $pageUrl) {
  $cc = seo_countries();
  $start = $e['date'] . (preg_match('/^(\d{1,2}):(\d{2})/', $e['time'], $m) ? sprintf('T%02d:%02d', $m[1], $m[2]) : '');
  $ld = array('@type' => 'Event', 'name' => $e['title'], 'startDate' => $start,
    'eventStatus' => 'https://schema.org/EventScheduled', 'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
    'location' => array('@type' => 'Place', 'name' => $e['venue'] !== '' ? $e['venue'] : ($e['city'] !== '' ? $e['city'] : $cc[$e['country']][1]),
      'address' => array('@type' => 'PostalAddress', 'addressLocality' => $e['city'], 'addressCountry' => strtoupper($e['country']))),
    'url' => $pageUrl, 'inLanguage' => $e['type'] === 'kurdish' ? 'ku' : 'fa');
  if ($e['endDate'] !== '' && $e['endDate'] !== $e['date']) $ld['endDate'] = $e['endDate'];
  $img = seo_safe_img($e['image']);
  if ($img) $ld['image'] = array(strpos($img, 'http') === 0 ? $img : seo_origin() . $img);
  if ($e['organizer'] !== '') $ld['organizer'] = array('@type' => 'Organization', 'name' => $e['organizer']);
  $l = seo_safe_link($e['url']);
  if ($l) $ld['offers'] = array('@type' => 'Offer', 'url' => $l, 'availability' => 'https://schema.org/InStock');
  return $ld;
}

function seo_crumbs_ld($crumbs) { // [[name, path], ...]
  $items = array(); $i = 1;
  foreach ($crumbs as $c) $items[] = array('@type' => 'ListItem', 'position' => $i++, 'name' => $c[0], 'item' => seo_abs($c[1]));
  return array('@type' => 'BreadcrumbList', 'itemListElement' => $items);
}

function seo_links_nav() { // internal links to every landing page (also shown on the home page footer)
  $P = seo_places(); $C = seo_cats(); $G = seo_group_cats(); $h = '';
  $h .= '<div class="lcol"><b>پارتی و کنسرت در اروپا</b><ul>';
  $h .= '<li><a href="' . h(seo_url('party')) . '">پارتی ایرانی و کوردی در اروپا</a></li><li><a href="' . h(seo_url('concert')) . '">کنسرت ایرانی و کوردی در اروپا</a></li></ul></div>';
  foreach (array('sweden', 'germany') as $ck) {
    $h .= '<div class="lcol"><b><a href="' . h(seo_url($ck)) . '">' . h($P[$ck]['fa']) . '</a></b><ul>';
    $h .= '<li><a href="' . h(seo_url($ck . '/party')) . '">پارتی در ' . h($P[$ck]['fa']) . '</a></li><li><a href="' . h(seo_url($ck . '/concert')) . '">کنسرت در ' . h($P[$ck]['fa']) . '</a></li>';
    foreach ($P as $pk => $p) if ($p['kind'] === 'city' && $p['parent'] === $ck) {
      $h .= '<li><a href="' . h(seo_url($pk . '/party')) . '">پارتی در ' . h($p['fa']) . '</a> · <a href="' . h(seo_url($pk . '/concert')) . '">کنسرت</a></li>';
    }
    $h .= '</ul></div>';
  }
  $h .= '<div class="lcol"><b><a href="' . h(seo_url('groups')) . '">گروه‌ها و انجمن‌ها</a></b><ul>';
  foreach ($G as $gk => $g) $h .= '<li><a href="' . h(seo_url('groups/' . $gk)) . '">' . h($g['fa']) . '</a></li>';
  $h .= '</ul></div>';
  return $h;
}

function seo_render($o) {
  // $o: title, desc, path, robots, h1, body (html), ld (array of ld nodes), crumbs [[name,path]...], og_image (optional)
  $canon = seo_abs($o['path']);
  $robots = $o['robots'] ?? 'index,follow,max-image-preview:large';
  if (!seo_is_prod()) { $robots = 'noindex,nofollow'; header('X-Robots-Tag: noindex'); }
  $ld = array('@context' => 'https://schema.org', '@graph' => array_values($o['ld'] ?? array()));
  $og = $o['og_image'] ?? (seo_abs('assets/og.png'));
  $og = preg_replace('#/assets/og/$#', '/assets/og.png', $og);
  $og = rtrim($og, '/');
  $b = seo_base();
  $crumbHtml = '';
  if (!empty($o['crumbs'])) {
    $parts = array();
    foreach ($o['crumbs'] as $i => $c) $parts[] = ($i < count($o['crumbs']) - 1) ? '<a href="' . h(seo_url($c[1])) . '">' . h($c[0]) . '</a>' : '<span>' . h($c[0]) . '</span>';
    $crumbHtml = '<nav class="crumbs" aria-label="مسیر">' . implode(' ‹ ', $parts) . '</nav>';
  }
  header('Content-Type: text/html; charset=utf-8');
  header('Cache-Control: public, max-age=600');
  echo '<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">' .
    '<title>' . h($o['title']) . '</title><meta name="description" content="' . h($o['desc']) . '">' .
    '<meta name="robots" content="' . h($robots) . '"><link rel="canonical" href="' . h($canon) . '">' .
    '<meta property="og:type" content="website"><meta property="og:site_name" content="BezanBerim · بزن بریم"><meta property="og:locale" content="fa_IR">' .
    '<meta property="og:title" content="' . h($o['title']) . '"><meta property="og:description" content="' . h($o['desc']) . '"><meta property="og:url" content="' . h($canon) . '">' .
    '<meta property="og:image" content="' . h($og) . '"><meta name="twitter:card" content="summary_large_image"><meta name="theme-color" content="#17251E">' .
    '<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' .
    '<link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&display=swap" rel="stylesheet">' .
    '<link rel="stylesheet" href="' . h($b) . '/assets/css/seo.css">' .
    '<script type="application/ld+json">' . json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) . '</script>' .
    '</head><body><header class="top"><div class="wrap bar"><a class="logo" href="' . h($b) . '/"><b>بزن بریم</b><small>BezanBerim</small></a>' .
    '<a class="app" href="' . h($b) . '/">همه‌ی برنامه‌ها در بزن بریم ←</a></div></header>' .
    '<main class="wrap">' . $crumbHtml . '<section class="hero"><h1>' . $o['h1'] . '</h1>' . ($o['lead'] ?? '') . '</section>' . $o['body'] . '</main>' .
    '<footer class="foot"><div class="wrap"><p class="fb"><b>بزن بریم</b> · برنامه‌های ایرانی، کوردی و افغانی در اروپا</p><div class="lgrid">' . seo_links_nav() . '</div>' .
    '<p class="fc"><a href="' . h($b) . '/#add">برنامه یا گروهی را که نمی‌بینید معرفی کنید</a></p></div></footer></body></html>';
}

function seo_not_found() {
  http_response_code(404);
  seo_render(array('title' => 'صفحه پیدا نشد · بزن بریم', 'desc' => 'این صفحه وجود ندارد.', 'path' => '404', 'robots' => 'noindex,follow',
    'h1' => 'صفحه پیدا نشد', 'lead' => '<p>این آدرس وجود ندارد یا برنامه‌اش دیگر روی سایت نیست.</p>',
    'body' => '<p><a class="btn" href="' . h(seo_base()) . '/">رفتن به صفحه‌ی اصلی بزن بریم</a></p>'));
  exit;
}

// ---------------------------------------------------------------- pages
function seo_events_page($pk, $ck) {
  $P = seo_places(); $C = seo_cats(); $p = $P[$pk];
  $cat = $ck !== '' ? $C[$ck] : null;
  $list = seo_events($pk, $ck);
  $n = count($list);
  $place = $p['kind'] === 'all' ? 'اروپا' : $p['fa'];
  $enp = $p['en'];
  $path = ($pk === 'europe' ? '' : $pk . '/') . ($ck !== '' ? $ck : '');
  $path = trim($path, '/');
  if ($cat) {
    $title = $cat['fa'] . ' ایرانی و کوردی در ' . $place . ($p['kind'] === 'all' ? '' : ' (' . $enp . ')') . ' — برنامه‌های پیش‌رو | بزن بریم';
    $h1 = $cat['fa'] . ' ایرانی، کوردی و افغانی در ' . h($place);
  } else {
    $title = 'پارتی، کنسرت و برنامه‌های ایرانی و کوردی در ' . $place . ($p['kind'] === 'all' ? '' : ' (' . $enp . ')') . ' | بزن بریم';
    $h1 = 'برنامه‌های ایرانی، کوردی و افغانی در ' . h($place);
  }
  $first = null; // the nearest event that has not started yet (an ongoing exhibition is not "next")
  foreach ($list as $e0) { if ($e0['date'] >= seo_today()) { $first = $e0; break; } }
  if ($first === null && $n) $first = $list[0];
  $what = $cat ? $cat['fa_pl'] : 'پارتی، کنسرت و جشن‌های ایرانی، کوردی و افغانی';
  if ($n) {
    $desc = 'فهرست به‌روز ' . $what . ' در ' . $place . ': ' . seo_fa_num($n) . ' برنامه‌ی پیش‌رو با تاریخ، سالن و لینک بلیت.';
    $near = ' نزدیک‌ترین: ' . $first['title'] . ' (' . seo_fa_date($first['date']) . ').';
    if (preg_match('/^.{0,175}$/us', $desc . $near)) $desc .= $near;
  } else {
    $desc = 'برنامه‌های ' . ($cat ? $cat['fa'] : 'پارتی و کنسرت') . ' ایرانی، کوردی و افغانی در ' . $place . ' در بزن بریم. هر وقت برنامه‌ای ثبت شود همین‌جا می‌آید.';
  }
  $desc = seo_cut($desc, 175);
  // lead paragraph (unique per page, built from real data)
  $lead = '<p class="lead">';
  if ($n) {
    $cities = array();
    foreach ($list as $e) if ($e['city'] !== '') $cities[$e['city']] = ($cities[$e['city']] ?? 0) + 1;
    arsort($cities);
    $lead .= 'در حال حاضر ' . seo_fa_num($n) . ' برنامه‌ی پیش‌رو برای ' . h($what) . ' در ' . h($place) . ' ثبت شده است';
    if ($p['kind'] !== 'city' && count($cities) > 1) $lead .= '، از جمله در ' . h(implode('، ', array_slice(array_keys($cities), 0, 4)));
    $lead .= '. هر برنامه تاریخ، سالن و لینک رسمی بلیت دارد و فهرست هر روز به‌روز می‌شود.';
  } else {
    $lead .= 'فعلاً برنامه‌ی پیش‌روی ثبت‌شده‌ای برای ' . h($what) . ' در ' . h($place) . ' نداریم. اگر برنامه‌ای می‌شناسید، از «معرفی برنامه» برای ما بفرستید.';
  }
  $lead .= '</p>';
  $kw = ($cat ? $cat['fa'] : 'پارتی و کنسرت');
  $also = '<p class="also">این صفحه برای جست‌وجوهایی مثل «' . h($kw . ' ' . $p['fa']) . '»';
  foreach ($p['fa_alts'] as $alt) if (preg_match('/[\x{0600}-\x{06FF}]/u', $alt)) $also .= ' یا «' . h($kw . ' ' . $alt) . '»';
  $also .= ' است.</p>';
  $loc = $p['local'][$ck !== '' ? $ck : 'party'] ?? '';
  $en = ($cat ? ucfirst($cat['en']) : 'Parties and concerts') . ' — Persian, Kurdish & Afghan events in ' . $enp;
  $also .= '<p class="also ltr">' . h($en) . ($loc !== '' ? ' · ' . h($loc) : '') . '</p>';
  $lead .= $also;
  // body
  $body = '';
  if ($n) {
    $body .= '<section><h2>' . h(($cat ? $cat['fa_pl'] : 'همه‌ی برنامه‌ها') . ' در ' . $place) . ' <span class="cnt">(' . seo_fa_num($n) . ')</span></h2><div class="evs">';
    foreach (array_slice($list, 0, 60) as $e) $body .= seo_card($e);
    $body .= '</div>';
    if ($n > 60) $body .= '<p class="more"><a href="' . h(seo_base()) . '/">برنامه‌های بیشتر را در بزن بریم ببینید ←</a></p>';
    $body .= '</section>';
  }
  // related links
  $rel = '<section class="rel"><h2>صفحه‌های مرتبط</h2><ul>';
  if ($pk !== 'europe') $rel .= '<li><a href="' . h(seo_url($pk)) . '">همه‌ی برنامه‌ها در ' . h($place) . '</a></li>';
  foreach ($C as $ck2 => $c2) if ($ck2 !== $ck || $pk === 'europe' && $ck === '') $rel .= '<li><a href="' . h(seo_url(($pk === 'europe' ? '' : $pk . '/') . $ck2)) . '">' . h($c2['fa']) . ' در ' . h($place) . '</a></li>';
  if ($p['kind'] === 'city') $rel .= '<li><a href="' . h(seo_url($p['parent'])) . '">برنامه‌ها در ' . h($P[$p['parent']]['fa']) . '</a></li>';
  if ($p['kind'] === 'country') foreach ($P as $k2 => $p2) if ($p2['kind'] === 'city' && $p2['parent'] === $pk) $rel .= '<li><a href="' . h(seo_url($k2)) . '">برنامه‌ها در ' . h($p2['fa']) . '</a></li>';
  $rel .= '<li><a href="' . h(seo_url('groups')) . '">گروه‌ها و انجمن‌های ایرانی و کوردی</a></li></ul></section>';
  $body .= $rel;

  $crumbs = array(array('بزن بریم', ''));
  if ($pk !== 'europe') {
    if ($p['kind'] === 'city') $crumbs[] = array($P[$p['parent']]['fa'], $p['parent']);
    $crumbs[] = array($p['fa'], $pk);
  }
  if ($cat) $crumbs[] = array($cat['fa'], $path);
  $ld = array(seo_crumbs_ld($crumbs));
  foreach (array_slice($list, 0, 25) as $e) $ld[] = seo_event_ld($e, seo_abs('event/' . $e['id']));
  seo_render(array('title' => $title, 'desc' => $desc, 'path' => $path, 'h1' => $h1, 'lead' => $lead, 'body' => $body, 'ld' => $ld, 'crumbs' => $crumbs,
    'robots' => $n ? 'index,follow,max-image-preview:large' : 'noindex,follow'));
  exit;
}

function seo_groups_page($gk) {
  $G = seo_group_cats(); $c = $gk !== '' ? $G[$gk] : null;
  $list = seo_groups($gk);
  $n = count($list);
  $cities = array();
  foreach ($list as $g) if ($g['city'] !== '') $cities[$g['city']] = 1;
  if ($c) {
    $title = $c['title'] . ' در سوئد و اروپا | بزن بریم';
    $h1 = h($c['title']);
    $lead = '<p class="lead">' . h($c['intro']) . ': ' . ($n ? seo_fa_num($n) . ' گروه و انجمن ثبت‌شده' . (count($cities) ? '، از جمله در ' . h(implode('، ', array_slice(array_keys($cities), 0, 5))) : '') . '. هر کارت به صفحه‌ی رسمی همان گروه می‌رود.' : 'هنوز گروهی در این دسته ثبت نشده. اگر گروهی می‌شناسید، معرفی کنید.') . '</p>';
    $desc = seo_cut($c['intro'] . ' — ' . ($n ? seo_fa_num($n) . ' گروه و انجمن ایرانی و کوردی با لینک صفحه‌ی رسمی' : 'گروه‌های ایرانی و کوردی') . ' در سوئد و اروپا.', 175);
    $path = 'groups/' . $gk;
  } else {
    $title = 'گروه‌ها و انجمن‌های ایرانی و کوردی: ورزشی، فرهنگی، آواز و کر، تفریحی، کتابخوانی | بزن بریم';
    $h1 = 'گروه‌ها و انجمن‌های ایرانی، کوردی و افغانی';
    $lead = '<p class="lead">گروه ورزشی، فرهنگی، آواز و کر، تفریحی و کتابخوانی: همه‌ی گروه‌ها و انجمن‌هایی که در بزن بریم ثبت شده‌اند، به‌همراه لینک صفحه‌ی رسمی هر کدام (' . seo_fa_num($n) . ' مورد).</p>';
    $desc = 'گروه‌های ورزشی، فرهنگی، آواز و کر، تفریحی و کتابخوانی ایرانی و کوردی در سوئد و اروپا — با لینک صفحه‌ی رسمی هر گروه.';
    $path = 'groups';
  }
  $lead .= '<p class="also ltr">Persian & Kurdish community groups in Sweden and Europe: sports, culture, choir, social, book clubs.</p>';
  $body = '';
  if ($c) {
    $body .= '<section><h2>' . h($c['title']) . ' <span class="cnt">(' . seo_fa_num($n) . ')</span></h2><div class="gcs">';
    foreach ($list as $g) $body .= seo_group_card($g);
    $body .= '</div></section>';
  } else {
    $body .= '<section><h2>دسته‌ها</h2><div class="gcs">';
    foreach ($G as $k => $x) {
      $cnt = count(seo_groups($k));
      $body .= '<article class="gc"><h3><a href="' . h(seo_url('groups/' . $k)) . '">' . h($x['title']) . '</a></h3><p>' . h($x['intro']) . '</p><p class="meta">' . seo_fa_num($cnt) . ' گروه</p></article>';
    }
    $body .= '</div></section><section><h2>همه‌ی گروه‌ها <span class="cnt">(' . seo_fa_num($n) . ')</span></h2><div class="gcs">';
    foreach ($list as $g) $body .= seo_group_card($g);
    $body .= '</div></section>';
  }
  $rel = '<section class="rel"><h2>صفحه‌های مرتبط</h2><ul>';
  foreach ($G as $k => $x) if ($k !== $gk) $rel .= '<li><a href="' . h(seo_url('groups/' . $k)) . '">' . h($x['fa']) . '</a></li>';
  $rel .= '<li><a href="' . h(seo_url('stockholm')) . '">پارتی و کنسرت در استکهلم</a></li></ul></section>';
  $crumbs = array(array('بزن بریم', ''), array('گروه‌ها و انجمن‌ها', 'groups'));
  if ($c) $crumbs[] = array($c['fa'], $path);
  $ld = array(seo_crumbs_ld($crumbs));
  $items = array(); $i = 1;
  foreach (array_slice($list, 0, 30) as $g) { $it = array('@type' => 'ListItem', 'position' => $i++, 'item' => array('@type' => 'Organization', 'name' => $g['name'])); if (seo_safe_link($g['url'])) $it['item']['sameAs'] = seo_safe_link($g['url']); $items[] = $it; }
  if ($items) $ld[] = array('@type' => 'ItemList', 'name' => $c ? $c['title'] : 'گروه‌ها و انجمن‌ها', 'itemListElement' => $items);
  seo_render(array('title' => $title, 'desc' => $desc, 'path' => $path, 'h1' => $h1, 'lead' => $lead, 'body' => $body . $rel, 'ld' => $ld, 'crumbs' => $crumbs,
    'robots' => $n ? 'index,follow,max-image-preview:large' : 'noindex,follow'));
  exit;
}

function seo_event_page($id) {
  $D = seo_data();
  if (!isset($D['events'][$id])) seo_not_found();
  $e = $D['events'][$id]; $cc = seo_countries(); $P = seo_places();
  $country = $cc[$e['country']][0]; $when = seo_fa_date($e['date']) . ($e['endDate'] !== '' && $e['endDate'] !== $e['date'] ? ' تا ' . seo_fa_date($e['endDate']) : '');
  $title = $e['title'] . ($e['city'] !== '' ? ' — ' . $e['city'] : '') . '، ' . seo_fa_date($e['date']) . ' | بزن بریم';
  $desc = seo_cut(seo_type_label($e['type']) . ': ' . $e['title'] . ' در ' . ($e['venue'] !== '' ? $e['venue'] . '، ' : '') . ($e['city'] !== '' ? $e['city'] . '، ' : '') . $country . ' — ' . $when . ($e['time'] !== '' ? '، ساعت ' . $e['time'] : '') . '. لینک بلیت و اطلاعات.', 175);
  $rows = array('تاریخ' => '<time datetime="' . h($e['date']) . '">' . h($when) . '</time>');
  if ($e['time'] !== '') $rows['ساعت'] = '<span class="ltr">' . h($e['time']) . '</span>';
  if ($e['venue'] !== '') $rows['سالن / مکان'] = h($e['venue']);
  $rows['شهر'] = h(trim($e['city'] . '، ' . $country, '، '));
  if ($e['price'] !== '') $rows['قیمت'] = h($e['price']);
  $rows['نوع'] = h(seo_type_label($e['type']));
  if ($e['organizer'] !== '') $rows['برگزارکننده'] = h($e['organizer']);
  $t = '<table class="kv">'; foreach ($rows as $k => $v) $t .= '<tr><th>' . h($k) . '</th><td dir="auto">' . $v . '</td></tr>'; $t .= '</table>';
  $img = seo_safe_img($e['image']); $go = seo_safe_link($e['url']);
  $body = '<section class="evpage">' . ($img ? '<img class="poster" src="' . h($img) . '" alt="' . h($e['title']) . '" width="360" height="450">' : '') . '<div>' . $t .
    ($e['upcoming'] ? '' : '<p class="past">این برنامه برگزار شده است.</p>') .
    ($go ? '<p><a class="btn" href="' . h($go) . '" target="_blank" rel="noopener nofollow">لینک بلیت و اطلاعات رسمی ←</a></p>' : '') . '</div></section>';
  // more in the same city
  $more = array();
  foreach ($D['events'] as $o) if ($o['id'] !== $id && $o['upcoming'] && $o['country'] === $e['country'] && $o['cityn'] === $e['cityn']) $more[] = $o;
  if ($more) { $body .= '<section><h2>برنامه‌های دیگر در ' . h($e['city'] !== '' ? $e['city'] : $country) . '</h2><div class="evs">'; foreach (array_slice($more, 0, 6) as $o) $body .= seo_card($o); $body .= '</div></section>'; }
  $rel = array();
  foreach ($P as $pk => $p) if ($p['kind'] === 'city' && seo_in_place($e, $pk)) $rel[] = array($pk, $p);
  $crumbs = array(array('بزن بریم', ''));
  $placeLinks = '<section class="rel"><h2>صفحه‌های مرتبط</h2><ul>';
  foreach ($P as $pk => $p) if ($p['kind'] === 'country' && $p['cc'] === $e['country']) { $crumbs[] = array($p['fa'], $pk); $placeLinks .= '<li><a href="' . h(seo_url($pk)) . '">برنامه‌ها در ' . h($p['fa']) . '</a></li>'; }
  foreach ($rel as $r) { $crumbs[] = array($r[1]['fa'], $r[0]); $placeLinks .= '<li><a href="' . h(seo_url($r[0])) . '">برنامه‌ها در ' . h($r[1]['fa']) . '</a></li>'; }
  $placeLinks .= '<li><a href="' . h(seo_base()) . '/">همه‌ی برنامه‌ها در بزن بریم</a></li></ul></section>';
  $crumbs[] = array($e['title'], 'event/' . $id);
  $ld = array(seo_crumbs_ld($crumbs), seo_event_ld($e, seo_abs('event/' . $id)));
  seo_render(array('title' => $title, 'desc' => $desc, 'path' => 'event/' . $id, 'h1' => h($e['title']),
    'lead' => '<p class="lead">' . h(seo_type_label($e['type'])) . ($e['city'] !== '' ? ' · ' . h($e['city']) : '') . ' · ' . h($when) . '</p>',
    'body' => $body . $placeLinks, 'ld' => $ld, 'crumbs' => $crumbs, 'og_image' => $img ? (strpos($img, 'http') === 0 ? $img : seo_origin() . $img) : null,
    'robots' => $e['upcoming'] ? 'index,follow,max-image-preview:large' : 'noindex,follow'));
  exit;
}

// ---------------------------------------------------------------- router
function seo_route($r) {
  $r = trim((string)$r, '/');
  $parts = $r === '' ? array() : explode('/', $r);
  $P = seo_places(); $C = seo_cats(); $G = seo_group_cats();
  if (seo_data() === null) { http_response_code(503); header('Retry-After: 600'); echo 'Temporarily unavailable'; exit; }
  // canonical slash
  $uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
  if ($uri !== null && substr($uri, -1) !== '/') { header('Location: ' . $uri . '/', true, 301); exit; }
  if (count($parts) === 1 && isset($C[$parts[0]])) seo_events_page('europe', $parts[0]);
  if (count($parts) >= 1 && $parts[0] === 'event') { if (count($parts) === 2 && preg_match('/^[a-z0-9._-]{3,160}$/i', $parts[1])) seo_event_page($parts[1]); seo_not_found(); }
  if ($parts && $parts[0] === 'groups') {
    if (count($parts) === 1) seo_groups_page('');
    if (count($parts) === 2 && isset($G[$parts[1]])) seo_groups_page($parts[1]);
    seo_not_found();
  }
  if ($parts && isset($P[$parts[0]]) && $parts[0] !== 'europe') {
    if (count($parts) === 1) seo_events_page($parts[0], '');
    if (count($parts) === 2 && isset($C[$parts[1]])) seo_events_page($parts[0], $parts[1]);
  }
  seo_not_found();
}
