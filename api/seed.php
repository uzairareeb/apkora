<?php
// ONE-TIME seeder for the Vercel + TiDB install of Apkora.
// Protected by the SEED_KEY environment variable. DELETE THIS FILE after use.
require dirname(__DIR__) . '/includes/bootstrap.php';
header('Content-Type: text/plain; charset=utf-8');

$seedKey = (string)getenv('SEED_KEY');
if ($seedKey === '' || !hash_equals($seedKey, (string)($_GET['key'] ?? ''))) {
    http_response_code(403);
    exit('Forbidden');
}
$adminPass = (string)getenv('SEED_ADMIN_PASS');
if ($adminPass === '') exit('SEED_ADMIN_PASS env var is not set.');

function create_schema(PDO $pdo): void {
    $t = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
    $sql = [
        "CREATE TABLE IF NOT EXISTS settings (k VARCHAR(100) NOT NULL PRIMARY KEY, v MEDIUMTEXT) $t",
        "CREATE TABLE IF NOT EXISTS admins (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, username VARCHAR(60) NOT NULL UNIQUE, password_hash VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL) $t",
        "CREATE TABLE IF NOT EXISTS categories (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL, slug VARCHAR(120) NOT NULL UNIQUE, description TEXT, sort_order INT NOT NULL DEFAULT 0) $t",
        "CREATE TABLE IF NOT EXISTS games (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, title VARCHAR(200) NOT NULL, slug VARCHAR(220) NOT NULL UNIQUE, short_desc VARCHAR(300) NOT NULL DEFAULT '', description MEDIUMTEXT, extra_content MEDIUMTEXT, icon VARCHAR(500) NOT NULL DEFAULT '', screenshots TEXT, category_id INT UNSIGNED NULL, developer VARCHAR(150) NOT NULL DEFAULT '', version VARCHAR(50) NOT NULL DEFAULT '', size VARCHAR(50) NOT NULL DEFAULT '', installs VARCHAR(50) NOT NULL DEFAULT '', rating DECIMAL(3,1) NOT NULL DEFAULT 0, is_game TINYINT(1) NOT NULL DEFAULT 1, is_app TINYINT(1) NOT NULL DEFAULT 0, downloads INT UNSIGNED NOT NULL DEFAULT 0, views INT UNSIGNED NOT NULL DEFAULT 0, download_url TEXT, source_url TEXT, meta_title VARCHAR(200) NOT NULL DEFAULT '', meta_desc VARCHAR(300) NOT NULL DEFAULT '', status ENUM('published','draft') NOT NULL DEFAULT 'published', created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX idx_status_created (status, created_at), INDEX idx_views (views), INDEX idx_cat (category_id)) $t",
        "CREATE TABLE IF NOT EXISTS pages (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, title VARCHAR(200) NOT NULL, slug VARCHAR(220) NOT NULL UNIQUE, content MEDIUMTEXT, meta_desc VARCHAR(300) NOT NULL DEFAULT '', show_in_footer TINYINT(1) NOT NULL DEFAULT 1, status ENUM('published','draft') NOT NULL DEFAULT 'published', sort_order INT NOT NULL DEFAULT 0, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL) $t",
        "CREATE TABLE IF NOT EXISTS faqs (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, question VARCHAR(300) NOT NULL, answer TEXT NOT NULL, sort_order INT NOT NULL DEFAULT 0) $t",
    ];
    foreach ($sql as $s) $pdo->exec($s);
}

function default_pages(string $site, string $email): array {
    $mail = $email !== '' ? '<a href="mailto:' . e($email) . '">' . e($email) . '</a>' : 'the contact details on this website';
    $s = e($site);
    return [
        ['About Us', 'about-us', "<p>Welcome to <strong>$s</strong>, a website where we showcase mobile games and apps with clear descriptions, key details and safe links to the official download sources.</p><p>Our goal is to help players find games that match their interests quickly. Every game page lists useful information such as size, version, developer and category, so you can decide before you download.</p><h2>What we offer</h2><ul><li>Organised game listings by category</li><li>Popular, new and classic games sections</li><li>Direct links to official stores and publishers</li></ul><p>If you have suggestions or want to report an issue, please contact us at $mail.</p>"],
        ['Contact Us', 'contact-us', "<p>We would love to hear from you. For questions, feedback, game suggestions, copyright requests or business enquiries, please email us at $mail.</p><p>We usually reply within 2–3 business days.</p>"],
        ['Privacy Policy', 'privacy-policy', "<p>This Privacy Policy explains how <strong>$s</strong> collects, uses and protects information when you visit our website.</p><h2>Information we collect</h2><p>We do not require you to create an account to browse. We may automatically collect non-personal information such as browser type, device type, pages visited and approximate location through server logs and analytics tools.</p><h2>Cookies</h2><p>We and our third-party partners use cookies and similar technologies to remember preferences, analyse traffic and show advertising. You can disable cookies in your browser settings, but some parts of the site may not work as expected.</p><h2>Google AdSense and advertising</h2><p>Third-party vendors, including Google, use cookies to serve ads based on a user's prior visits to this and other websites. Google's use of advertising cookies enables it and its partners to serve ads based on your visit to our site and/or other sites on the Internet. You may opt out of personalised advertising by visiting Google Ads Settings.</p><h2>Analytics</h2><p>We may use Google Analytics or similar services to understand how visitors use the website. These services may collect information such as your IP address and pages viewed.</p><h2>External links</h2><p>Our pages may contain links to third-party websites and app stores. We are not responsible for their content or privacy practices.</p><h2>Children's privacy</h2><p>We do not knowingly collect personal information from children under 13.</p><h2>Changes to this policy</h2><p>We may update this policy from time to time. The latest version will always be available on this page.</p><h2>Contact</h2><p>If you have questions about this policy, contact us at $mail.</p>"],
        ['Terms and Conditions', 'terms-and-conditions', "<p>By accessing <strong>$s</strong> you agree to these terms. If you do not agree, please do not use the website.</p><h2>Use of the website</h2><p>The content is provided for general information and personal, non-commercial use. You agree not to misuse the website, attempt to disrupt its operation or use automated tools to harm the service.</p><h2>Third-party content and downloads</h2><p>Game names, logos and trademarks belong to their respective owners. Download buttons lead to third-party platforms. We do not host game files and are not responsible for third-party content.</p><h2>No warranty</h2><p>The website is provided on an \"as is\" basis. We make no guarantees about accuracy, availability or fitness for a particular purpose.</p><h2>Changes</h2><p>We may modify these terms at any time. Continued use of the website means you accept the updated terms.</p><h2>Contact</h2><p>Questions about these terms can be sent to $mail.</p>"],
        ['DMCA & Disclaimer', 'dmca-disclaimer', "<p><strong>$s</strong> respects the intellectual property rights of others. All game titles, icons, screenshots and trademarks are the property of their respective owners and are used for informational purposes.</p><h2>Copyright complaints</h2><p>If you are a rights holder and believe content on this website infringes your rights, please email $mail with the page URL, proof of ownership and a description of the issue. We will review and respond promptly, and remove content where appropriate.</p><h2>Disclaimer</h2><p>We are not affiliated with or endorsed by the developers or publishers of the games listed. We do not host or distribute game files.</p>"],
    ];
}

try {
    $pdo = db();
    create_schema($pdo);

    if ((int)$pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn() === 0) {
        $now = date('Y-m-d H:i:s');
        $site = 'Apkora';
        $email = 'uzair0340s@gmail.com';
        $tagline = 'Download Android Games & Apps — discover popular games and apps with size, version, developer and safe official download links.';
        $set = [
            'site_name' => $site,
            'tagline' => $tagline,
            'keywords' => 'download android games, download android apps, free android games, apk download, best android apps, popular mobile games',
            'contact_email' => $email,
            'meta_description' => $tagline,
            'site_url' => site_origin() . base_path(),
            'footer_text' => $tagline,
            'home_count' => '12',
            'schema_v' => '2',
            'header_code' => '', 'footer_code' => '', 'ads_txt' => '', 'robots_extra' => '', 'logo' => '', 'favicon' => '',
        ];
        $st = $pdo->prepare('INSERT INTO settings (k, v) VALUES (?, ?) ON DUPLICATE KEY UPDATE v = VALUES(v)');
        foreach ($set as $k => $x) $st->execute([$k, $x]);

        $pdo->prepare('INSERT INTO admins (username, password_hash, created_at) VALUES (?, ?, ?)')
            ->execute(['admin', password_hash($adminPass, PASSWORD_DEFAULT), $now]);

        $ps = $pdo->prepare("INSERT INTO pages (title, slug, content, show_in_footer, status, sort_order, created_at, updated_at) VALUES (?, ?, ?, 1, 'published', ?, ?, ?)");
        foreach (default_pages($site, $email) as $i => $pg) $ps->execute([$pg[0], $pg[1], $pg[2], $i, $now, $now]);

        $cs = $pdo->prepare('INSERT INTO categories (name, slug, sort_order) VALUES (?, ?, ?)');
        foreach (['Action', 'Arcade', 'Puzzle', 'Racing', 'Sports', 'Strategy'] as $i => $n) $cs->execute([$n, strtolower($n), $i]);

        $v = ['site_name' => $site, 'contact_email' => $email];
        $faqs = [
                ['What is ' . $v['site_name'] . '?', $v['site_name'] . ' is a website where you can discover games and apps, read details such as size, version and developer, and get links to the official download sources.'],
                ['Are the games free to download?', 'Most listed games and apps are free to download. Some games may include in-app purchases or ads, which depends on the publisher.'],
                ['Do you host the game files?', 'No. Download buttons redirect to the official store or publisher page, so you always download from the original source.'],
                ['How do I install a game or app?', 'Open the game or app page, press the Download button, and follow the instructions on the official store page.'],
                ['How can I request a game or app, or report a problem?', 'Please email us at ' . $v['contact_email'] . ' and we will review your request.'],
            ];
        $fs = $pdo->prepare('INSERT INTO faqs (question, answer, sort_order) VALUES (?, ?, ?)');
        foreach ($faqs as $i => $f) $fs->execute([$f[0], $f[1], $i]);
        echo "Base seed done: settings, admin, pages, categories, FAQs.\n";
    } else {
        echo "Base seed already done (admin exists) — skipped.\n";
    }

    // Starter games, in slices so each call stays inside the function timeout.
    $pkgs = ['com.supercell.clashofclans','com.supercell.brawlstars','com.kiloo.subwaysurf','com.mojang.minecraftpe','com.king.candycrushsaga','com.innersloth.spacemafia','com.roblox.client','com.imangi.templerun2','com.supercell.clashroyale','com.activision.callofduty.shooter','com.whatsapp','com.spotify.music','com.google.android.apps.maps','com.snapchat.android','org.telegram.messenger','com.canva.editor','com.instagram.android','com.netflix.mediaclient'];
    $from = max(0, (int)($_GET['from'] ?? 0));
    $count = max(0, (int)($_GET['count'] ?? 0));
    foreach (array_slice($pkgs, $from, $count ?: null, true) as $i => $pkg) {
        $r = import_playstore('https://play.google.com/store/apps/details?id=' . $pkg, ['type' => 'auto', 'category_id' => 0, 'new_category' => '']);
        echo ($i + 1) . ". $pkg — " . (!empty($r['ok']) ? 'OK: ' . $r['title'] : ('skip/fail: ' . ($r['msg'] ?? 'unknown'))) . "\n";
        flush();
    }
    echo "Seed call finished.\n";
} catch (Throwable $ex) {
    http_response_code(500);
    echo 'Seed failed: ' . $ex->getMessage();
}
