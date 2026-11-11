<?php
require __DIR__ . '/includes/bootstrap.php';

if (getenv('INSTALLED') !== '1' && !is_file(ROOT . '/storage/installed.lock')) {
    if (is_dir(ROOT . '/setup')) redirect(url('setup/'));
    http_response_code(503);
    exit('Site is not installed. Upload the /setup folder and open /setup/.');
}

migrate();

$path = (string)parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$path = rawurldecode($path);
$bp = base_path();
if ($bp !== '' && strpos($path, $bp) === 0) $path = substr($path, strlen($bp));
$path = trim($path, '/');
$GLOBALS['route_path'] = $path;
$seg = $path === '' ? [] : explode('/', $path);
$route = $seg[0] ?? '';
$pageNo = max(1, (int)($_GET['page'] ?? 1));
$PER = 24;

function not_found(): void {
    http_response_code(404);
    page('404', [], ['title' => 'Page not found', 'noindex' => true]);
    exit;
}

function list_view(string $heading, string $where, array $params, string $order, string $baseUrl, array $meta = [], string $intro = ''): void {
    global $pageNo, $PER;
    $total = (int)val("SELECT COUNT(*) FROM games g WHERE g.status = 'published'" . ($where !== '' ? " AND $where" : ''), $params);
    $games = games_list($where, $params, $order, $PER, ($pageNo - 1) * $PER);
    $meta += ['title' => $heading . ($pageNo > 1 ? ' – Page ' . $pageNo : ''), 'canonical' => site_url(ltrim($GLOBALS['route_path'], '/')) . ($pageNo > 1 ? '?page=' . $pageNo : '')];
    page('list', ['heading' => $heading, 'games' => $games, 'intro' => $intro, 'pager' => paginate_html($total, $PER, $pageNo, url($baseUrl))], $meta);
}

switch ($route) {
    case '':
        $n = max(4, min(24, (int)setting('home_count', '12')));
        $popular = games_list('', [], 'g.views DESC, g.created_at DESC', $n);
        $games = games_list('g.is_game = 1', [], 'g.created_at DESC', $n);
        $apps = games_list('g.is_app = 1', [], 'g.created_at DESC', $n);
        $cats = rows('SELECT name, slug FROM categories ORDER BY sort_order, name');
        $faqs = rows('SELECT question, answer FROM faqs ORDER BY sort_order, id');
        $ld = [[
            '@context' => 'https://schema.org', '@type' => 'WebSite', 'name' => setting('site_name'), 'url' => site_url(''),
            'potentialAction' => ['@type' => 'SearchAction', 'target' => site_url('search') . '?q={search_term_string}', 'query-input' => 'required name=search_term_string'],
        ]];
        if ($faqs) {
            $ents = [];
            foreach ($faqs as $f) $ents[] = ['@type' => 'Question', 'name' => $f['question'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['answer']]];
            $ld[] = ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $ents];
        }
        page('home', compact('popular', 'games', 'apps', 'cats', 'faqs'), ['jsonld' => $ld, 'canonical' => site_url('')]);
        break;

    case 'game':
        $g = row("SELECT g.*, c.name AS cat_name, c.slug AS cat_slug FROM games g LEFT JOIN categories c ON c.id = g.category_id WHERE g.slug = ?", [$seg[1] ?? '']);
        if (!$g || ($g['status'] !== 'published' && !is_admin())) not_found();
        if ($g['status'] === 'published' && empty($_SESSION['seen'][$g['id']])) {
            q('UPDATE games SET views = views + 1 WHERE id = ?', [$g['id']]);
            $_SESSION['seen'][$g['id']] = 1;
            $g['views']++;
        }
        if ($g['category_id']) $related = games_list('g.id <> ? AND g.category_id = ?', [$g['id'], $g['category_id']], 'g.views DESC, g.created_at DESC', 6);
        else $related = games_list('g.id <> ?', [$g['id']], 'g.views DESC, g.created_at DESC', 6);
        $desc = $g['meta_desc'] !== '' ? $g['meta_desc'] : ($g['short_desc'] !== '' ? $g['short_desc'] : excerpt($g['description'], 155));
        $ld = [[
            '@context' => 'https://schema.org', '@type' => 'SoftwareApplication', 'name' => $g['title'], 'operatingSystem' => 'ANDROID',
            'applicationCategory' => 'GameApplication', 'description' => $desc, 'image' => img_url($g['icon']) ,
            'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'USD'],
        ], [
            '@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => site_url('')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => $g['title'], 'item' => site_url('game/' . $g['slug'])],
            ],
        ]];
        if (strpos($ld[0]['image'], 'http') !== 0) $ld[0]['image'] = site_origin() . $ld[0]['image'];
        $img = strpos(img_url($g['icon']), 'http') === 0 ? img_url($g['icon']) : site_origin() . img_url($g['icon']);
        page('game', compact('g', 'related'), [
            'title' => $g['meta_title'] !== '' ? $g['meta_title'] : $g['title'], 'description' => $desc, 'image' => $img,
            'canonical' => site_url('game/' . $g['slug']), 'og_type' => 'article', 'jsonld' => $ld, 'noindex' => $g['status'] !== 'published',
        ]);
        break;

    case 'category':
        $c = row('SELECT * FROM categories WHERE slug = ?', [$seg[1] ?? '']);
        if (!$c) not_found();
        list_view($c['name'] . ' Games', 'g.category_id = ?', [$c['id']], 'g.created_at DESC', 'category/' . $c['slug'],
            ['description' => $c['description'] !== '' ? $c['description'] : 'Download the best ' . $c['name'] . ' games.'], (string)$c['description']);
        break;

    case 'browse':
        $b = $seg[1] ?? '';
        $map = [
            'popular' => ['Popular Games & Apps', '', 'g.views DESC, g.created_at DESC'],
            'games' => ['Games', 'g.is_game = 1', 'g.created_at DESC'],
            'apps' => ['Apps', 'g.is_app = 1', 'g.created_at DESC'],
        ];
        if (!isset($map[$b])) not_found();
        list_view($map[$b][0], $map[$b][1], [], $map[$b][2], 'browse/' . $b);
        break;

    case 'search':
        $term = trim((string)($_GET['q'] ?? ''));
        $like = '%' . addcslashes($term, '%_\\') . '%';
        if ($term === '') list_view('Search', '1 = 0', [], 'g.created_at DESC', 'search', ['noindex' => true]);
        else list_view('Search results for “' . $term . '”', '(g.title LIKE ? OR g.short_desc LIKE ?)', [$like, $like], 'g.views DESC', 'search?q=' . rawurlencode($term), ['noindex' => true]);
        break;

    case 'p':
        $pg = row("SELECT * FROM pages WHERE slug = ? AND status = 'published'", [$seg[1] ?? '']);
        if (!$pg) not_found();
        page('page', compact('pg'), ['title' => $pg['title'], 'description' => $pg['meta_desc'] !== '' ? $pg['meta_desc'] : excerpt($pg['content'], 155), 'canonical' => site_url('p/' . $pg['slug'])]);
        break;

    case 'go':
        $g = row("SELECT id, download_url FROM games WHERE slug = ? AND status = 'published'", [$seg[1] ?? '']);
        if (!$g || $g['download_url'] === '') not_found();
        q('UPDATE games SET downloads = downloads + 1 WHERE id = ?', [$g['id']]);
        header('X-Robots-Tag: noindex, nofollow');
        redirect($g['download_url']);
        break;

    case 'sitemap.xml':
        header('Content-Type: application/xml; charset=utf-8');
        echo sitemap_xml();
        break;

    case 'robots.txt':
        header('Content-Type: text/plain; charset=utf-8');
        echo "User-agent: *\nAllow: /\nDisallow: /admin/\nDisallow: /go/\nDisallow: /search\n";
        if (trim((string)setting('robots_extra')) !== '') echo trim(setting('robots_extra')) . "\n";
        echo "\nSitemap: " . site_url('sitemap.xml') . "\n";
        break;

    case 'ads.txt':
        header('Content-Type: text/plain; charset=utf-8');
        if (trim((string)setting('ads_txt')) === '') { http_response_code(404); echo 'Not found'; } else echo trim(setting('ads_txt')) . "\n";
        break;

    default:
        not_found();
}
