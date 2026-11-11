<?php
/*
 * Google Play importer (best-effort).
 * Google Play has no official free public API, so this parses the public app page.
 * If Google changes its page layout the parser may need adjusting; in that case
 * the data can still be entered manually in the admin form.
 */
function ps_package_id(string $input): ?string {
    $input = trim($input);
    $re = '/^[a-zA-Z][\w]*(\.[\w]+)+$/';
    if (preg_match($re, $input)) return $input;
    $p = parse_url($input);
    if (!$p || empty($p['host']) || stripos($p['host'], 'play.google.com') === false) return null;
    parse_str($p['query'] ?? '', $qs);
    $id = (string)($qs['id'] ?? '');
    return preg_match($re, $id) ? $id : null;
}

function ps_meta(string $html, string $name): string {
    $n = preg_quote($name, '#');
    if (preg_match('#<meta[^>]+(?:property|name)=["\']' . $n . '["\'][^>]*content=["\']([^"\']*)["\']#i', $html, $m)) return html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');
    if (preg_match('#<meta[^>]+content=["\']([^"\']*)["\'][^>]*(?:property|name)=["\']' . $n . '["\']#i', $html, $m)) return html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');
    return '';
}

function ps_fetch(string $input): array {
    $id = ps_package_id($input);
    if (!$id) return ['error' => 'This is not a valid Google Play link. Expected format: https://play.google.com/store/apps/details?id=com.example.game'];
    $page = http_get('https://play.google.com/store/apps/details?id=' . urlencode($id) . '&hl=en&gl=US', 25);
    if (!$page) return ['error' => 'Could not fetch the Google Play page (the app may not exist, or the request was blocked). Try again or add it manually.'];

    $ld = [];
    if (preg_match_all('#<script[^>]+type=["\']application/ld\+json["\'][^>]*>(.*?)</script>#s', $page, $mm)) {
        foreach ($mm[1] as $raw) {
            $j = json_decode($raw, true);
            if (is_array($j) && (isset($j['name']) || isset($j['@type']))) { $ld = $j; break; }
        }
    }

    $title = (string)($ld['name'] ?? '');
    if ($title === '') $title = trim(preg_replace('/\s+-\s+Apps on Google Play.*$/i', '', ps_meta($page, 'og:title')));
    if ($title === '') return ['error' => 'Could not read the app data. Please check the link.'];

    // full description
    $desc = '';
    if (preg_match('#data-g-id=["\']description["\'][^>]*>(.*?)</div>#s', $page, $m)) {
        $t = preg_replace('#<br\s*/?>#i', "\n", $m[1]);
        $desc = trim(html_entity_decode(strip_tags($t), ENT_QUOTES, 'UTF-8'));
    }
    $ogDesc = ps_meta($page, 'og:description');
    if ($desc === '') $desc = trim((string)($ld['description'] ?? $ogDesc));
    $desc = preg_replace("/\n{3,}/", "\n\n", $desc);

    // icon
    $icon = $ld['image'] ?? ps_meta($page, 'og:image');
    if (is_array($icon)) $icon = $icon[0] ?? '';
    $icon = (string)$icon;
    if ($icon !== '') $icon = preg_replace('/=[swh][\w\-]*$/', '', $icon) . '=w512-h512';

    // screenshots
    $shots = [];
    if (preg_match_all('#<img[^>]+>#i', $page, $tags)) {
        foreach ($tags[0] as $tag) {
            if (stripos($tag, 'screenshot') === false) continue;
            if (preg_match('#(?:src|data-src)=["\'](https://play-lh\.googleusercontent\.com/[^"\']+)["\']#i', $tag, $sm)) {
                $s = preg_replace('/=[swh][\w\-]*$/', '', html_entity_decode($sm[1])) . '=w720';
                if (!in_array($s, $shots, true)) $shots[] = $s;
            }
        }
    }

    $installs = '';
    if (preg_match('#>\s*([0-9][0-9.,]*\s?[KMB]?\+)\s*</div>\s*<div[^>]*>\s*Downloads#i', $page, $m)) $installs = trim($m[1]);

    $rating = 0.0;
    if (isset($ld['aggregateRating']['ratingValue'])) $rating = round((float)$ld['aggregateRating']['ratingValue'], 1);

    $dev = '';
    if (isset($ld['author']['name'])) $dev = (string)$ld['author']['name'];

    $rawCat = (string)($ld['applicationCategory'] ?? '');
    $isGame = $rawCat === '' ? true : (stripos($rawCat, 'GAME') === 0);
    $cat = $rawCat;
    $cat = ucwords(strtolower(str_replace('_', ' ', preg_replace('/^GAME_/i', '', $cat))));
    if ($cat === 'Game') $cat = '';

    return [
        'package' => $id,
        'title' => $title,
        'description' => $desc,
        'short_desc' => excerpt($ogDesc !== '' ? $ogDesc : $desc, 180),
        'icon' => $icon,
        'screenshots' => array_slice($shots, 0, 6),
        'installs' => $installs,
        'rating' => $rating,
        'developer' => $dev,
        'category' => $cat,
        'is_game' => $isGame,
        'source_url' => 'https://play.google.com/store/apps/details?id=' . $id,
    ];
}
