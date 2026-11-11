<?php
require dirname(__DIR__) . '/includes/bootstrap.php';

if (getenv('INSTALLED') === '1' || is_file(ROOT . '/storage/installed.lock')) {
    @unlink(__FILE__); @rmdir(__DIR__);
    exit('Already installed. Please delete the /setup folder manually if it still exists.');
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

$err = '';
$cfg = $GLOBALS['cfg'];
$v = [
    'db_host' => $cfg['db_host'] ?? 'localhost', 'db_name' => $cfg['db_name'] ?? '', 'db_user' => $cfg['db_user'] ?? '', 'db_pass' => $cfg['db_pass'] ?? '',
    'site_name' => '', 'tagline' => '', 'keywords' => '', 'contact_email' => '', 'admin_user' => '', 'default_cats' => '1',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach ($v as $k => $_) $v[$k] = trim((string)($_POST[$k] ?? ''));
    $adminPass = (string)($_POST['admin_pass'] ?? '');
    $adminPass2 = (string)($_POST['admin_pass2'] ?? '');
    if ($v['site_name'] === '') $err = 'Website name is required.';
    elseif (!filter_var($v['contact_email'], FILTER_VALIDATE_EMAIL)) $err = 'Please enter a valid contact email.';
    elseif (!preg_match('/^[A-Za-z0-9_.-]{3,40}$/', $v['admin_user'])) $err = 'Admin username must be 3-40 characters (letters, numbers, _ . -).';
    elseif (strlen($adminPass) < 8) $err = 'Admin password must be at least 8 characters.';
    elseif ($adminPass !== $adminPass2) $err = 'The two passwords do not match.';
    else {
        try {
            $pdo = new PDO('mysql:host=' . $v['db_host'] . ';dbname=' . $v['db_name'] . ';charset=utf8mb4', $v['db_user'], $_POST['db_pass'] ?? '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        } catch (Throwable $ex) { $err = 'Could not connect to the database: ' . $ex->getMessage(); }
    }
    if ($err === '') {
        try {
            create_schema($pdo);
            if ((int)$pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn() > 0) throw new RuntimeException('This database already has an admin account. Please use a new, empty database.');
            $now = date('Y-m-d H:i:s');
            $origin = site_origin() . base_path();
            $set = [
                'site_name' => $v['site_name'], 'tagline' => $v['tagline'], 'keywords' => $v['keywords'], 'contact_email' => $v['contact_email'],
                'meta_description' => $v['tagline'], 'site_url' => $origin, 'footer_text' => $v['tagline'], 'home_count' => '12', 'schema_v' => '2',
                'header_code' => '', 'footer_code' => '', 'ads_txt' => '', 'robots_extra' => '', 'logo' => '', 'favicon' => '',
            ];
            $st = $pdo->prepare('INSERT INTO settings (k, v) VALUES (?, ?) ON DUPLICATE KEY UPDATE v = VALUES(v)');
            foreach ($set as $k => $x) $st->execute([$k, $x]);
            $pdo->prepare('INSERT INTO admins (username, password_hash, created_at) VALUES (?, ?, ?)')->execute([$v['admin_user'], password_hash($adminPass, PASSWORD_DEFAULT), $now]);
            $ps = $pdo->prepare('INSERT INTO pages (title, slug, content, show_in_footer, status, sort_order, created_at, updated_at) VALUES (?, ?, ?, 1, \'published\', ?, ?, ?)');
            foreach (default_pages($v['site_name'], $v['contact_email']) as $i => $p) $ps->execute([$p[0], $p[1], $p[2], $i, $now, $now]);
            if ($v['default_cats'] === '1') {
                $cs = $pdo->prepare('INSERT INTO categories (name, slug, sort_order) VALUES (?, ?, ?)');
                foreach (['Action', 'Arcade', 'Puzzle', 'Racing', 'Sports', 'Strategy'] as $i => $n) $cs->execute([$n, strtolower($n), $i]);
            }
            $fs = $pdo->prepare('INSERT INTO faqs (question, answer, sort_order) VALUES (?, ?, ?)');
            $faqs = [
                ['What is ' . $v['site_name'] . '?', $v['site_name'] . ' is a website where you can discover games and apps, read details such as size, version and developer, and get links to the official download sources.'],
                ['Are the games free to download?', 'Most listed games and apps are free to download. Some games may include in-app purchases or ads, which depends on the publisher.'],
                ['Do you host the game files?', 'No. Download buttons redirect to the official store or publisher page, so you always download from the original source.'],
                ['How do I install a game or app?', 'Open the game or app page, press the Download button, and follow the instructions on the official store page.'],
                ['How can I request a game or app, or report a problem?', 'Please email us at ' . $v['contact_email'] . ' and we will review your request.'],
            ];
            foreach ($faqs as $i => $f) $fs->execute([$f[0], $f[1], $i]);

            $c = "<?php\nreturn " . var_export(['db_host' => $v['db_host'], 'db_name' => $v['db_name'], 'db_user' => $v['db_user'], 'db_pass' => (string)($_POST['db_pass'] ?? '')], true) . ";\n";
            if (@file_put_contents(ROOT . '/config.php', $c) === false) throw new RuntimeException('Cannot write config.php (permission denied). Please check the file permissions.');
            @mkdir(ROOT . '/storage', 0755, true);
            file_put_contents(ROOT . '/storage/installed.lock', date('c'));
            // sitemap (best effort)
            $GLOBALS['cfg'] = ['db_host' => $v['db_host'], 'db_name' => $v['db_name'], 'db_user' => $v['db_user'], 'db_pass' => (string)($_POST['db_pass'] ?? '')];
            try { regenerate_sitemap(); } catch (Throwable $ignore) {}
            // self-delete setup
            $deleted = @unlink(__FILE__);
            @rmdir(__DIR__);
            if ($deleted) { header('Location: ' . url('admin/login.php?installed=1')); exit; }
            $done = true;
        } catch (Throwable $ex) { $err = 'Setup failed: ' . $ex->getMessage(); }
    }
}
?><!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex">
<title>Setup</title>
<style>
*{box-sizing:border-box}body{margin:0;font-family:system-ui,Segoe UI,Roboto,Arial,sans-serif;background:#0f1220;color:#e8eaf6;padding:24px}
.box{max-width:720px;margin:0 auto;background:#181c30;border:1px solid #2a3050;border-radius:16px;padding:28px}
h1{margin:0 0 6px;font-size:26px}h2{font-size:16px;margin:26px 0 10px;color:#9fb0ff;border-bottom:1px solid #2a3050;padding-bottom:6px}
p.sub{color:#9aa3c7;margin:0 0 10px}label{display:block;font-size:13px;margin:12px 0 4px;color:#c5cbe8}
input[type=text],input[type=email],input[type=password]{width:100%;padding:11px 12px;border-radius:10px;border:1px solid #333b63;background:#0f1220;color:#fff;font-size:15px}
.row{display:grid;grid-template-columns:1fr 1fr;gap:12px}@media(max-width:600px){.row{grid-template-columns:1fr}}
button{margin-top:22px;padding:13px 22px;border:0;border-radius:10px;background:linear-gradient(135deg,#6c5ce7,#00b4d8);color:#fff;font-size:16px;font-weight:600;cursor:pointer;width:100%}
.err{background:#4a1d28;border:1px solid #a63a52;padding:12px;border-radius:10px;margin:14px 0}.ok{background:#173d2c;border:1px solid #2f8f63;padding:12px;border-radius:10px;margin:14px 0}
small{color:#8089b3}
.credit{text-align:center;margin-top:26px;padding-top:18px;border-top:1px solid #2a3050;color:#9aa3c7;font-size:14px}.credit strong{color:#fff}
.yt{display:inline-block;margin-top:10px;padding:12px 22px;border-radius:10px;background:#e62117;color:#fff!important;font-weight:700;text-decoration:none;font-size:15px}.yt:hover{background:#c41a11}
</style></head><body><div class="box">
<h1>🎮 Website Setup</h1><p class="sub">Fill in this form once. When installation finishes, the /setup folder deletes itself.</p>
<?php if (!empty($done)): ?>
  <div class="ok"><strong>Setup complete!</strong> However the /setup folder could not be removed automatically. Please delete the <code>setup</code> folder using FTP or the File Manager.<br><br><a style="color:#9fe" href="<?= e(url('admin/login.php')) ?>">Admin login →</a></div>
<?php else: ?>
<?php if ($err): ?><div class="err"><?= e($err) ?></div><?php endif; ?>
<form method="post" autocomplete="off">
<?= csrf_field() ?>
<h2>1. Database (create it in Hostinger hPanel)</h2>
<div class="row"><div><label>DB Host</label><input type="text" name="db_host" value="<?= e($v['db_host']) ?>" required></div><div><label>DB Name</label><input type="text" name="db_name" value="<?= e($v['db_name']) ?>" required></div></div>
<div class="row"><div><label>DB Username</label><input type="text" name="db_user" value="<?= e($v['db_user']) ?>" required></div><div><label>DB Password</label><input type="password" name="db_pass" value="<?= e($v['db_pass']) ?>"></div></div>
<small>If you already filled these in config.php they appear here automatically.</small>
<h2>2. Website details</h2>
<label>Website Name *</label><input type="text" name="site_name" value="<?= e($v['site_name']) ?>" required>
<label>Tagline</label><input type="text" name="tagline" value="<?= e($v['tagline']) ?>" placeholder="e.g. Download the best Android games">
<label>Tags / Keywords (comma separated)</label><input type="text" name="keywords" value="<?= e($v['keywords']) ?>" placeholder="games, android games, apk">
<label>Contact Email *</label><input type="email" name="contact_email" value="<?= e($v['contact_email']) ?>" required>
<label><input type="checkbox" name="default_cats" value="1" <?= $v['default_cats'] === '1' ? 'checked' : '' ?>> Create default categories (Action, Arcade, Puzzle, Racing, Sports, Strategy)</label>
<h2>3. Admin account</h2>
<div class="row"><div><label>Admin Username *</label><input type="text" name="admin_user" value="<?= e($v['admin_user']) ?>" required></div><div></div></div>
<div class="row"><div><label>Admin Password * (min 8)</label><input type="password" name="admin_pass" required></div><div><label>Confirm Password *</label><input type="password" name="admin_pass2" required></div></div>
<button type="submit">Install Website</button>
</form>
<?php endif; ?>
<div class="credit">Developed by <strong>Pro EDU</strong><br><a class="yt" href="https://www.youtube.com/@PROEDU76" target="_blank" rel="noopener">▶ Get More Script, Subscribe our channel</a></div>
</div></body></html>
