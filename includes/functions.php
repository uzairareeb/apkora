<?php
/* ---------- basics ---------- */
function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

function base_path(): string {
    static $b = null;
    if ($b === null) {
        $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
        $dir = preg_replace('#/(admin|setup)$#', '', $dir);
        $b = rtrim($dir, '/');
    }
    return $b;
}
function url(string $p = ''): string { return base_path() . '/' . ltrim($p, '/'); }
function site_origin(): string {
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    return ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
}
function site_url(string $p = ''): string {
    $su = rtrim((string)setting('site_url', ''), '/');
    if ($su === '') $su = site_origin() . base_path();
    return $su . '/' . ltrim($p, '/');
}
function redirect(string $to): void { header('Location: ' . $to); exit; }

/* ---------- database ---------- */
function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $c = $GLOBALS['cfg'];
        $port = (int)($c['db_port'] ?? 3306);
        $dsn = 'mysql:host=' . ($c['db_host'] ?? 'localhost') . ';port=' . ($port > 0 ? $port : 3306) . ';dbname=' . ($c['db_name'] ?? '') . ';charset=utf8mb4';
        $opts = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        if (!empty($c['db_ssl'])) {
            foreach (['/etc/ssl/certs/ca-certificates.crt', '/etc/pki/tls/certs/ca-bundle.crt'] as $ca) {
                if (is_file($ca)) { $opts[PDO::MYSQL_ATTR_SSL_CA] = $ca; break; }
            }
        }
        $pdo = new PDO($dsn, $c['db_user'] ?? '', $c['db_pass'] ?? '', $opts);
    }
    return $pdo;
}
function q(string $sql, array $p = []): PDOStatement { $s = db()->prepare($sql); $s->execute($p); return $s; }
function row(string $sql, array $p = []): ?array { $r = q($sql, $p)->fetch(); return $r ?: null; }
function rows(string $sql, array $p = []): array { return q($sql, $p)->fetchAll(); }
function val(string $sql, array $p = []) { $r = q($sql, $p)->fetch(PDO::FETCH_NUM); return $r ? $r[0] : null; }

/* ---------- settings ---------- */
function settings(bool $reload = false): array {
    static $s = null;
    if ($s === null || $reload) {
        $s = [];
        foreach (rows('SELECT k, v FROM settings') as $r) $s[$r['k']] = $r['v'];
    }
    return $s;
}
function setting(string $k, $d = '') { $s = settings(); return (isset($s[$k]) && $s[$k] !== '') ? $s[$k] : $d; }
function set_setting(string $k, string $v): void {
    q('INSERT INTO settings (k, v) VALUES (?, ?) ON DUPLICATE KEY UPDATE v = VALUES(v)', [$k, $v]);
}

/* ---------- security / session ---------- */
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">'; }
function csrf_check(): void {
    $t = $_POST['_csrf'] ?? '';
    if (!is_string($t) || !hash_equals($_SESSION['csrf'] ?? '', $t)) {
        http_response_code(419);
        exit('Security token mismatch. Please go back, refresh the page and try again.');
    }
}
function flash(string $msg, string $type = 'ok'): void { $_SESSION['flash'][] = [$type, $msg]; }
function flashes(): array { $f = $_SESSION['flash'] ?? []; unset($_SESSION['flash']); return $f; }
function is_admin(): bool { return !empty($_SESSION['admin_id']); }
function require_admin(): void { if (!is_admin()) redirect(url('admin/login.php')); }

/* ---------- text helpers ---------- */
function slugify(string $s): string {
    $s = strtolower(trim($s));
    if (function_exists('iconv')) { $t = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s); if ($t !== false) $s = $t; }
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    $s = trim((string)$s, '-');
    return $s !== '' ? $s : 'item';
}
function unique_slug(string $table, string $slug, int $ignore = 0): string {
    $base = $slug; $i = 2;
    while ((int)val("SELECT COUNT(*) FROM `$table` WHERE slug = ? AND id <> ?", [$slug, $ignore]) > 0) { $slug = $base . '-' . $i++; }
    return $slug;
}
function excerpt(string $s, int $n = 160): string {
    $s = trim(preg_replace('/\s+/', ' ', strip_tags($s)));
    return mb_strlen($s) > $n ? rtrim(mb_substr($s, 0, $n - 1)) . '…' : $s;
}
function img_url($p): string {
    $p = trim((string)$p);
    if ($p === '') return url('assets/img/placeholder.svg');
    if (preg_match('#^https?://#i', $p)) return $p;
    return url($p);
}
function clean_url(string $u): string {
    $u = trim($u);
    return preg_match('#^https?://#i', $u) ? $u : '';
}
function ago(string $dt): string {
    $d = time() - strtotime($dt);
    if ($d < 3600) return max(1, (int)($d / 60)) . ' min ago';
    if ($d < 86400) return (int)($d / 3600) . ' hours ago';
    if ($d < 86400 * 30) return (int)($d / 86400) . ' days ago';
    return date('M j, Y', strtotime($dt));
}

/* ---------- http + images ---------- */
function http_get(string $url, int $timeout = 20): ?string {
    $ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36';
    if (!function_exists('curl_init')) {
        $ctx = stream_context_create(['http' => ['timeout' => $timeout, 'header' => "User-Agent: $ua\r\nAccept-Language: en-US,en;q=0.9\r\n"]]);
        $r = @file_get_contents($url, false, $ctx);
        return $r === false ? null : $r;
    }
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_MAXREDIRS => 5,
        CURLOPT_TIMEOUT => $timeout, CURLOPT_ENCODING => '', CURLOPT_USERAGENT => $ua,
        CURLOPT_HTTPHEADER => ['Accept-Language: en-US,en;q=0.9'],
    ]);
    $r = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ($r !== false && $code >= 200 && $code < 300) ? (string)$r : null;
}
function image_ext(int $type): ?string {
    $map = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_GIF => 'gif', IMAGETYPE_WEBP => 'webp'];
    return $map[$type] ?? null;
}
function save_upload(array $f): ?string {
    if (empty($f['tmp_name']) || ($f['error'] ?? 1) !== UPLOAD_ERR_OK || $f['size'] > 4 * 1024 * 1024) return null;
    $info = @getimagesize($f['tmp_name']);
    if (!$info || !($ext = image_ext($info[2]))) return null;
    $name = date('Ymd') . '-' . bin2hex(random_bytes(6)) . '.' . $ext;
    if (!move_uploaded_file($f['tmp_name'], ROOT . '/uploads/' . $name)) return null;
    return 'uploads/' . $name;
}
function save_remote_image(string $url): ?string {
    $host = (string)parse_url($url, PHP_URL_HOST);
    if (!preg_match('/(^|\.)googleusercontent\.com$/i', $host)) return null;
    $data = http_get($url, 20);
    if (!$data || strlen($data) < 300) return null;
    $info = @getimagesizefromstring($data);
    if (!$info || !($ext = image_ext($info[2]))) return null;
    $name = date('Ymd') . '-' . bin2hex(random_bytes(6)) . '.' . $ext;
    return file_put_contents(ROOT . '/uploads/' . $name, $data) ? 'uploads/' . $name : null;
}

/* ---------- games ---------- */
function games_list(string $where = '', array $p = [], string $order = 'g.created_at DESC', int $limit = 8, int $offset = 0): array {
    $sql = "SELECT g.*, c.name AS cat_name, c.slug AS cat_slug FROM games g LEFT JOIN categories c ON c.id = g.category_id WHERE g.status = 'published'"
         . ($where !== '' ? " AND $where" : '') . " ORDER BY $order LIMIT " . (int)$limit . ' OFFSET ' . (int)$offset;
    return rows($sql, $p);
}
function game_card(array $g): string {
    $h = '<a class="card" href="' . e(url('game/' . $g['slug'])) . '">';
    $h .= '<img loading="lazy" src="' . e(img_url($g['icon'])) . '" alt="' . e($g['title']) . ' icon" width="88" height="88">';
    $h .= '<div class="card-body"><h3>' . e($g['title']) . '</h3>';
    if (!empty($g['cat_name'])) $h .= '<span class="cat">' . e($g['cat_name']) . '</span>';
    $m = [type_label($g)];
    if ((float)$g['rating'] > 0) $m[] = '★ ' . number_format((float)$g['rating'], 1);
    if ($g['size'] !== '') $m[] = e($g['size']);
    if ($m) $h .= '<span class="meta">' . implode(' · ', $m) . '</span>';
    return $h . '</div></a>';
}
function paginate_html(int $total, int $per, int $page, string $base): string {
    $pages = (int)ceil($total / max(1, $per));
    if ($pages <= 1) return '';
    $sep = strpos($base, '?') === false ? '?' : '&';
    $h = '<nav class="pager" aria-label="Pagination">';
    for ($i = 1; $i <= $pages; $i++) {
        if ($i === $page) $h .= '<span class="cur">' . $i . '</span>';
        elseif ($i === 1 || $i === $pages || abs($i - $page) <= 2) $h .= '<a href="' . e($base . ($i > 1 ? $sep . 'page=' . $i : '')) . '">' . $i . '</a>';
        elseif (abs($i - $page) === 3) $h .= '<span>…</span>';
    }
    return $h . '</nav>';
}

/* ---------- sitemap ---------- */
function sitemap_xml(): string {
    $u = [[site_url(''), date('c'), '1.0']];
    foreach (['popular', 'games', 'apps'] as $b) $u[] = [site_url('browse/' . $b), date('c'), '0.6'];
    foreach (rows("SELECT slug, updated_at FROM games WHERE status = 'published'") as $r) $u[] = [site_url('game/' . $r['slug']), date('c', strtotime($r['updated_at'])), '0.8'];
    foreach (rows('SELECT slug FROM categories') as $r) $u[] = [site_url('category/' . $r['slug']), date('c'), '0.6'];
    foreach (rows("SELECT slug, updated_at FROM pages WHERE status = 'published'") as $r) $u[] = [site_url('p/' . $r['slug']), date('c', strtotime($r['updated_at'])), '0.4'];
    $x = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    foreach ($u as $i) $x .= "  <url><loc>" . e($i[0]) . "</loc><lastmod>{$i[1]}</lastmod><priority>{$i[2]}</priority></url>\n";
    return $x . '</urlset>' . "\n";
}
function regenerate_sitemap(): bool { return @file_put_contents(ROOT . '/sitemap.xml', sitemap_xml()) !== false; }

/* ---------- front-end rendering ---------- */
function page(string $tpl, array $vars = [], array $meta = []): void {
    extract($vars);
    ob_start();
    include ROOT . '/templates/' . $tpl . '.php';
    $content = ob_get_clean();
    include ROOT . '/templates/layout.php';
}


/* ---------- schema migration (adds Game/App flags to older installs) ---------- */
function migrate(): void {
    if (setting('schema_v', '1') === '2') return;
    $defs = ['is_game' => 'TINYINT(1) NOT NULL DEFAULT 1', 'is_app' => 'TINYINT(1) NOT NULL DEFAULT 0'];
    foreach ($defs as $c => $def) {
        if (!rows("SHOW COLUMNS FROM games LIKE '$c'")) db()->exec("ALTER TABLE games ADD COLUMN $c $def");
    }
    set_setting('schema_v', '2');
    settings(true);
}

function type_label(array $g): string {
    $game = (int)($g['is_game'] ?? 1) === 1; $app = (int)($g['is_app'] ?? 0) === 1;
    if ($game && $app) return 'Game & App';
    return $app ? 'App' : 'Game';
}

/* ---------- description formatting ---------- */
function format_description(string $text): string {
    $text = trim(str_replace(["\r\n", "\r"], "\n", $text));
    if ($text === '') return '';
    $bullet = '/^[•·▪●■◆►✔✓\-\*]\s*/u';
    $out = '';
    foreach (preg_split("/\n{2,}/", $text) as $block) {
        $lines = array_values(array_filter(array_map('trim', explode("\n", $block)), fn($l) => $l !== ''));
        if (!$lines) continue;
        $para = []; $list = [];
        $flushP = function () use (&$para, &$out) { if ($para) { $out .= '<p>' . implode('<br>', array_map('e', $para)) . '</p>'; $para = []; } };
        $flushL = function () use (&$list, &$out) { if ($list) { $out .= '<ul>' . implode('', array_map(fn($i) => '<li>' . e($i) . '</li>', $list)) . '</ul>'; $list = []; } };
        foreach ($lines as $l) {
            if (preg_match($bullet, $l)) { $flushP(); $list[] = preg_replace($bullet, '', $l); continue; }
            $flushL();
            $isHeading = count($lines) === 1 && mb_strlen($l) <= 60 && (substr($l, -1) === ':' || mb_strtoupper($l) === $l || !preg_match('/[.!?,;]/u', $l));
            if ($isHeading) { $flushP(); $out .= '<h3>' . e(rtrim($l, ':')) . '</h3>'; continue; }
            $para[] = $l;
        }
        $flushL(); $flushP();
    }
    return $out;
}

/* ---------- categories / files ---------- */
function get_or_create_category(string $name): int {
    $name = trim($name);
    if ($name === '') return 0;
    $id = val('SELECT id FROM categories WHERE LOWER(name) = LOWER(?)', [$name]);
    if ($id) return (int)$id;
    q('INSERT INTO categories (name, slug, sort_order) VALUES (?, ?, 50)', [$name, unique_slug('categories', slugify($name))]);
    return (int)db()->lastInsertId();
}
function delete_game_files(array $g): void {
    $paths = array_merge([(string)$g['icon']], preg_split('/\R/', (string)$g['screenshots']));
    foreach ($paths as $p) {
        $p = trim($p);
        if (preg_match('#^uploads/[A-Za-z0-9._-]+$#', $p) && is_file(ROOT . '/' . $p)) @unlink(ROOT . '/' . $p);
    }
}

/* ---------- Play Store import (used by single + bulk import) ---------- */
function import_playstore(string $link, array $o = []): array {
    @set_time_limit(120);
    $res = ps_fetch($link);
    if (isset($res['error'])) return ['ok' => false, 'msg' => $res['error']];
    $dupe = val('SELECT id FROM games WHERE source_url = ?', [$res['source_url']]);
    if ($dupe) return ['ok' => false, 'dupe' => true, 'id' => (int)$dupe, 'title' => $res['title'], 'msg' => $res['title'] . ' is already imported.'];

    $type = $o['type'] ?? 'auto';
    if (!in_array($type, ['game', 'app', 'both'], true)) $type = $res['is_game'] ? 'game' : 'app';
    $isGame = in_array($type, ['game', 'both'], true) ? 1 : 0;
    $isApp = in_array($type, ['app', 'both'], true) ? 1 : 0;

    $newCat = trim((string)($o['new_category'] ?? ''));
    $pick = (int)($o['category_id'] ?? 0);
    if ($newCat !== '') $catId = get_or_create_category($newCat);
    elseif ($pick > 0 && val('SELECT id FROM categories WHERE id = ?', [$pick])) $catId = $pick;
    else $catId = get_or_create_category($res['category'] !== '' ? $res['category'] : (($isApp && !$isGame) ? 'Apps' : 'Games'));

    $icon = $res['icon'] !== '' ? (save_remote_image($res['icon']) ?: $res['icon']) : '';
    $shots = [];
    foreach (array_slice($res['screenshots'], 0, 4) as $sh) $shots[] = save_remote_image($sh) ?: $sh;

    $slug = unique_slug('games', slugify($res['title']));
    q("INSERT INTO games (title, slug, short_desc, description, extra_content, icon, screenshots, category_id, developer, installs, rating, download_url, source_url, is_game, is_app, status, created_at, updated_at)
       VALUES (?, ?, ?, ?, '', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'published', NOW(), NOW())", [
        $res['title'], $slug, $res['short_desc'], $res['description'], $icon, implode("\n", $shots), $catId ?: null, $res['developer'], $res['installs'],
        $res['rating'], $res['source_url'], $res['source_url'], $isGame, $isApp,
    ]);
    return ['ok' => true, 'id' => (int)db()->lastInsertId(), 'title' => $res['title'], 'slug' => $slug, 'msg' => 'Imported and published.'];
}
