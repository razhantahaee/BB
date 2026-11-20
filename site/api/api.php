<?php
// API ساده‌ی بزن بریم روی MySQL استرato
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
session_start();
require __DIR__ . '/config.php';

function pdo() {
  static $p = null;
  if (!$p) {
    $p = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $p->exec("CREATE TABLE IF NOT EXISTS docs (
      path VARCHAR(255) PRIMARY KEY,
      coll VARCHAR(64) NOT NULL,
      data MEDIUMTEXT NOT NULL,
      updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      INDEX (coll)
    ) CHARACTER SET utf8mb4");
    $p->exec("CREATE TABLE IF NOT EXISTS meta (k VARCHAR(64) PRIMARY KEY, v TEXT) CHARACTER SET utf8mb4");
  }
  return $p;
}

function out($x) { echo json_encode($x, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); exit; }
function fail($code, $msg) { http_response_code($code); out(['error' => $msg]); }
function isAdmin() { return !empty($_SESSION['admin']); }

function adminHash() {
  $s = pdo()->query("SELECT v FROM meta WHERE k='admin_hash'")->fetchColumn();
  return $s ?: null;
}

// توکن خودکار (VPS): در config.php مقدار BB_TOKEN را بگذارید؛ اگر خالی باشد این مسیرها بسته‌اند
function tokenOk() {
  $t = $_SERVER['HTTP_X_BB_TOKEN'] ?? '';
  return defined('BB_TOKEN') && BB_TOKEN !== '' && $t !== '' && hash_equals(BB_TOKEN, $t);
}

function collOf($path) { return explode('/', $path)[0]; }

function validPath($path) {
  return is_string($path) && strlen($path) <= 240 && preg_match('#^[A-Za-z0-9_.\-]+(/[A-Za-z0-9_.\-]+)*$#', $path);
}

// نوشتن: فقط ادمین، جز submissions/{uid} که هر کس برای خودش می‌تواند بنویسد
function canWrite($path, $uid) {
  if (isAdmin()) return true;
  $parts = explode('/', $path);
  return $parts[0] === 'submissions' && count($parts) === 2 && $uid !== null && $parts[1] === $uid;
}

// خواندن: رویدادها، گروه‌ها و config عمومی‌اند؛ submissions فقط برای خودش یا ادمین؛ sources فقط ادمین
function canRead($path, $uid) {
  if (isAdmin()) return true;
  $parts = explode('/', $path);
  $c = $parts[0];
  if ($c === 'events' || $c === 'communities') return true;
  if ($c === 'config') return true;
  if ($c === 'submissions') return count($parts) === 2 && $uid !== null && $parts[1] === $uid;
  return false;
}

// یکسان‌سازی آیدی/لینک صفحه برای تشخیص تکراری بودن: instagram:handle، facebook:handle یا groups/id، website:host
function feedKey($platform, $h) {
  $h = mb_strtolower(trim((string)$h));
  $h = preg_replace('#^https?://#', '', $h);
  $h = preg_replace('#^(www|m|mobile|web|l)\.#', '', $h);
  $h = preg_replace('#[?\#].*$#', '', $h);
  $h = rtrim($h, '/');
  if (preg_match('#^(chat\.whatsapp\.com|whatsapp\.com/(?:channel|invite|c)|wa\.me)/(.+)$#', $h, $m)) { $platform = 'whatsapp'; $h = explode('/', $m[2])[0]; }
  elseif (preg_match('#^(t\.me|telegram\.me|telegram\.dog)/(.+)$#', $h, $m)) { $platform = 'telegram'; $seg = explode('/', $m[2]); $h = (in_array($seg[0], ['joinchat', 's'], true) && isset($seg[1])) ? $seg[1] : $seg[0]; }
  elseif (preg_match('#^(instagram\.com|instagr\.am)/(.+)$#', $h, $m)) { $platform = 'instagram'; $h = explode('/', $m[2])[0]; }
  elseif (preg_match('#^(facebook\.com|fb\.com)/(.+)$#', $h, $m)) { $platform = 'facebook'; $seg = explode('/', $m[2]); $h = ($seg[0] === 'groups' && isset($seg[1])) ? 'groups/' . $seg[1] : $seg[0]; }
  elseif ($platform === 'website' || strpos($h, '.') !== false && strpos($h, ' ') === false && $platform !== 'instagram' && $platform !== 'facebook' && $platform !== 'whatsapp' && $platform !== 'telegram') { $platform = 'website'; $h = explode('/', $h)[0]; }
  $h = ltrim($h, '@');
  return $platform . ':' . $h;
}

$op = $_GET['op'] ?? '';
$raw = file_get_contents('php://input');
$in = $raw ? (json_decode($raw, true) ?: []) : [];
$uid = isset($_GET['uid']) ? (string)$_GET['uid'] : (isset($in['uid']) ? (string)$in['uid'] : null);
if ($uid !== null && !preg_match('/^[A-Za-z0-9_\-]{4,80}$/', $uid)) $uid = null;

switch ($op) {

  case 'whoami':
    out(['admin' => isAdmin(), 'hasAdmin' => adminHash() !== null]);

  case 'list': {
    $c = $_GET['c'] ?? '';
    if (!preg_match('/^[a-z]+$/', $c)) fail(400, 'bad collection');
    if ($c === 'submissions') {
      if (isAdmin()) $st = pdo()->prepare("SELECT path, data FROM docs WHERE coll=?");
      else { if ($uid === null) out(['docs' => []]); $st = pdo()->prepare("SELECT path, data FROM docs WHERE path=?"); $path = 'submissions/' . $uid; }
      if (isAdmin()) $st->execute([$c]); else $st->execute([$path]);
    } elseif ($c === 'sources' || $c === 'pending') {
      if (!isAdmin()) out(['docs' => []]);
      $st = pdo()->prepare("SELECT path, data FROM docs WHERE coll=?"); $st->execute([$c]);
    } elseif (in_array($c, ['events', 'communities', 'config'], true)) {
      $st = pdo()->prepare("SELECT path, data FROM docs WHERE coll=?"); $st->execute([$c]);
    } else {
      fail(403, 'not allowed');
    }
    $docs = [];
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
      $docs[] = ['id' => substr($r['path'], strrpos($r['path'], '/') + 1), 'data' => json_decode($r['data'], true) ?: new stdClass()];
    }
    out(['docs' => $docs]);
  }

  case 'get': {
    $path = $_GET['path'] ?? '';
    if (!validPath($path) || count(explode('/', $path)) !== 2) fail(400, 'bad path');
    if (!canRead($path, $uid)) fail(403, 'not allowed');
    $st = pdo()->prepare("SELECT data FROM docs WHERE path=?");
    $st->execute([$path]);
    $d = $st->fetchColumn();
    if ($d === false) out(['exists' => false, 'data' => null]);
    out(['exists' => true, 'data' => json_decode($d, true) ?: new stdClass()]);
  }

  case 'write': {
    $path = $in['path'] ?? '';
    $mode = $in['mode'] ?? 'set';
    $data = $in['data'] ?? [];
    if (!validPath($path) || count(explode('/', $path)) !== 2) fail(400, 'bad path');
    if (!is_array($data)) fail(400, 'bad data');
    if (!canWrite($path, $uid)) fail(403, 'not allowed');
    if (!in_array($mode, ['set', 'update', 'merge'], true)) fail(400, 'bad mode');

    $pdo = pdo();
    $st = $pdo->prepare("SELECT data FROM docs WHERE path=?");
    $st->execute([$path]);
    $cur = $st->fetchColumn();
    $exists = $cur !== false;
    if ($mode === 'update' && !$exists) fail(404, 'not found');

    $base = ($mode === 'set' || !$exists) ? [] : (json_decode($cur, true) ?: []);
    foreach ($data as $k => $v) {
      if (is_array($v) && isset($v['__delete__']) && $v['__delete__'] === true) { unset($base[$k]); continue; }
      $base[$k] = $v;
    }
    $json = json_encode($base, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $ins = $pdo->prepare("INSERT INTO docs (path, coll, data) VALUES (?,?,?) ON DUPLICATE KEY UPDATE data=VALUES(data)");
    $ins->execute([$path, collOf($path), $json]);
    out(['ok' => true]);
  }

  case 'delete': {
    $path = $in['path'] ?? '';
    if (!isAdmin()) fail(403, 'not allowed');
    if (!validPath($path)) fail(400, 'bad path');
    $st = pdo()->prepare("DELETE FROM docs WHERE path=?");
    $st->execute([$path]);
    out(['ok' => true]);
  }

  case 'setup': {
    // اولین بار: رمز ادمین را خودتان تعیین می‌کنید؛ بعد از آن این کار بسته می‌شود
    if (adminHash() !== null) fail(409, 'already set');
    $pw = (string)($in['password'] ?? '');
    if (strlen($pw) < 10) fail(400, 'password too short');
    $h = password_hash($pw, PASSWORD_DEFAULT);
    $st = pdo()->prepare("INSERT INTO meta (k, v) VALUES ('admin_hash', ?)");
    $st->execute([$h]);
    $_SESSION['admin'] = true;
    out(['ok' => true]);
  }

  case 'login': {
    $h = adminHash();
    if ($h === null) fail(409, 'not set');
    $pw = (string)($in['password'] ?? '');
    if (!password_verify($pw, $h)) { usleep(600000); fail(403, 'wrong password'); }
    session_regenerate_id(true);
    $_SESSION['admin'] = true;
    out(['ok' => true]);
  }

  case 'seed': {
    // ادمین: وارد کردن داده‌های اولیه از فایل‌های seed (فقط اگر سند از قبل وجود نداشته باشد)
    if (!isAdmin()) fail(403, 'not allowed');
    $files = ['seed-events.json' => 'events', 'seed-communities.json' => 'communities', 'seed-sources.json' => 'sources'];
    $pdo = pdo();
    $ins = $pdo->prepare("INSERT IGNORE INTO docs (path, coll, data) VALUES (?,?,?)");
    $counts = [];
    foreach ($files as $file => $coll) {
      $fp = __DIR__ . '/' . $file;
      if (!is_file($fp)) { $counts[$coll] = 'missing'; continue; }
      $rows = json_decode(file_get_contents($fp), true);
      if (!is_array($rows)) { $counts[$coll] = 'bad json'; continue; }
      $n = 0;
      foreach ($rows as $row) {
        if (!isset($row['id'], $row['data']) || !validPath($coll . '/' . $row['id'])) continue;
        $ins->execute([$coll . '/' . $row['id'], $coll, json_encode($row['data'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
        $n += $ins->rowCount();
      }
      $counts[$coll] = $n;
    }
    out(['ok' => true, 'inserted' => $counts]);
  }

  case 'cleanup': {
    // هر روز توسط VPS صدا زده می‌شود: برنامه‌هایی که تاریخ پایانشان (endDate یا date) قبل از امروز (به وقت استکهلم) است
    // از لیست سایت برداشته و در مجموعه‌ی archive نگهداری می‌شوند. برنامه‌ی امروز تا فردا می‌ماند. تاریخ نامعتبر = دست نمی‌خورد.
    if (!tokenOk() && !isAdmin()) fail(403, 'not allowed');
    $dry = isset($_GET['dry']);
    $today = (new DateTime('now', new DateTimeZone('Europe/Stockholm')))->format('Y-m-d');
    $pdo = pdo();
    $rows = $pdo->query("SELECT path, data FROM docs WHERE coll='events'")->fetchAll(PDO::FETCH_ASSOC);
    $old = []; $kept = 0;
    foreach ($rows as $r) {
      $d = json_decode($r['data'], true) ?: [];
      $end = (string)($d['endDate'] ?? $d['date'] ?? '');
      if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $end) || $end >= $today) { $kept++; continue; }
      $old[] = ['path' => $r['path'], 'data' => $d, 'raw' => $r['data']];
    }
    if (!$dry && $old) {
      $pdo->beginTransaction();
      $ins = $pdo->prepare("INSERT INTO docs (path, coll, data) VALUES (?,?,?) ON DUPLICATE KEY UPDATE data=VALUES(data)");
      $del = $pdo->prepare("DELETE FROM docs WHERE path=?");
      foreach ($old as $o) {
        $d = $o['data']; $d['archivedAt'] = $today;
        $ins->execute(['archive/' . substr($o['path'], 7), 'archive', json_encode($d, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
        $del->execute([$o['path']]);
      }
      $pdo->commit();
    }
    out(['ok' => true, 'dry' => $dry, 'today' => $today, 'archived' => $dry ? 0 : count($old), 'wouldArchive' => count($old), 'kept' => $kept,
         'list' => array_slice(array_map(function ($o) { return ($o['data']['date'] ?? '?') . ' ' . ($o['data']['title'] ?? $o['path']); }, $old), 0, 40)]);
  }

  case 'import': {
    // VPS (token) sends candidate events. They only land in pending/<id> = approval queue; NOTHING goes live without the admin.
    if (!tokenOk()) fail(403, 'not allowed');
    $list = $in['events'] ?? [];
    if (!is_array($list) || count($list) > 300) fail(400, 'bad events');
    $pdo = pdo();
    $known = [];
    foreach ($pdo->query("SELECT path FROM docs WHERE coll IN ('events','pending','archive')")->fetchAll(PDO::FETCH_COLUMN) as $pth) $known[substr($pth, strpos($pth, '/') + 1)] = 1;
    $ins = $pdo->prepare("INSERT INTO docs (path, coll, data) VALUES (?,?,?)");
    $today = (new DateTime('now', new DateTimeZone('Europe/Stockholm')))->format('Y-m-d');
    $added = 0; $skipped = 0;
    foreach ($list as $e) {
      if (!is_array($e)) { $skipped++; continue; }
      $id = (string)($e['id'] ?? '');
      if (!preg_match('/^[a-z0-9\-]{3,120}$/', $id) || isset($known[$id])) { $skipped++; continue; }
      $clean = [];
      foreach (['title','date','endDate','ongoing','time','city','country','venue','price','type','image','url','source','organizer','notes','postedAt','endDate','dateLabel'] as $k) {
        if (isset($e[$k]) && is_scalar($e[$k])) $clean[$k] = mb_substr((string)$e[$k], 0, 600);
      }
      if (!empty($e['ongoing'])) $clean['ongoing'] = true;
      if (empty($clean['title'])) { $skipped++; continue; }
      $clean['status'] = 'pending'; $clean['importedAt'] = $today;
      $ins->execute(['pending/' . $id, 'pending', json_encode($clean, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
      $known[$id] = 1; $added++;
    }
    out(['ok' => true, 'added' => $added, 'skipped' => $skipped]);
  }

  case 'checkfeed': {
    // عمومی: آیا این صفحه/سایت قبلاً پیگیری می‌شود، قبلاً بررسی شده، یا در انتظار بررسی است؟ فقط وضعیت را برمی‌گرداند، نه فهرست را.
    $platform = (string)($_GET['platform'] ?? $in['platform'] ?? '');
    $handle = (string)($_GET['handle'] ?? $in['handle'] ?? '');
    if (!in_array($platform, ['instagram', 'facebook', 'website', 'whatsapp', 'telegram'], true) || strlen(trim($handle)) < 2 || strlen($handle) > 300) fail(400, 'bad input');
    $key = feedKey($platform, $handle);
    $pdo = pdo();
    $status = 'new';
    $rank = ['new' => 0, 'pending' => 1, 'declined' => 2, 'group' => 3, 'tracked' => 4];
    $set = function ($s) use (&$status, $rank) { if ($rank[$s] > $rank[$status]) $status = $s; };
    foreach ($pdo->query("SELECT coll, data FROM docs WHERE coll IN ('sources','communities','submissions')")->fetchAll(PDO::FETCH_ASSOC) as $r) {
      $d = json_decode($r['data'], true) ?: [];
      if ($r['coll'] === 'submissions') {
        foreach (($d['items'] ?? []) as $it) {
          if (is_array($it) && feedKey($it['platform'] ?? '', $it['handle'] ?? ($it['url'] ?? '')) === $key) $set('pending');
        }
        continue;
      }
      $hit = false;
      foreach (['handle', 'url', 'nameLatin'] as $f) {
        if (!empty($d[$f]) && feedKey($d['platform'] ?? $platform, $d[$f]) === $key) { $hit = true; break; }
      }
      if (!$hit) continue;
      if ($r['coll'] === 'communities') $set('group');
      else $set(($d['status'] ?? '') === 'approved' ? 'tracked' : (($d['status'] ?? '') === 'rejected' ? 'declined' : 'pending'));
    }
    // صفحه‌ای که در انتظار بررسی است ولی خودش در sources تأیید شده، همان tracked حساب می‌شود (رتبه‌ی بالاتر)
    out(['exists' => $status !== 'new', 'status' => $status]);
  }

  case 'export': {
    // VPS (token): approved/rejected tracked pages + upcoming events (for dedup) -> the daily job rewrites sources.txt from this
    if (!tokenOk() && !isAdmin()) fail(403, 'not allowed');
    $pdo = pdo();
    $today = (new DateTime('now', new DateTimeZone('Europe/Stockholm')))->format('Y-m-d');
    $src = []; $ev = [];
    foreach ($pdo->query("SELECT path, data FROM docs WHERE coll IN ('sources','events')")->fetchAll(PDO::FETCH_ASSOC) as $r) {
      $d = json_decode($r['data'], true) ?: [];
      if (strpos($r['path'], 'sources/') === 0) {
        $st = $d['status'] ?? '';
        if ($st === 'approved' || $st === 'rejected') $src[] = ['id' => substr($r['path'], 8), 'platform' => $d['platform'] ?? '', 'handle' => $d['handle'] ?? '', 'url' => $d['url'] ?? '', 'status' => $st];
      } else {
        $end = (string)($d['endDate'] ?? $d['date'] ?? '');
        if ($end >= $today) $ev[] = ['title' => $d['title'] ?? '', 'date' => $d['date'] ?? '', 'city' => $d['city'] ?? ''];
      }
    }
    out(['ok' => true, 'today' => $today, 'sources' => $src, 'events' => $ev]);
  }

  case 'logout':
    $_SESSION = [];
    session_destroy();
    out(['ok' => true]);

  default:
    fail(400, 'unknown op');
}
