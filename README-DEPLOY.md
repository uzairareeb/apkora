# Apkora — deploy notes (checked 11 Nov 2026)
- Stack: PHP 8 + MySQL (PDO mysql), Apache .htaccess rewrites. NOT a static/Node site.
- Site name is NOT hardcoded: set it in /setup (site_name) — stored in DB settings. Defaults in templates/layout.php + templates/home.php changed GameHub -> Apkora.
- Vercel: NO native PHP runtime. Community vercel-php can run api/index.php only; /admin/*.php, /setup/*.php are direct files and will not run, filesystem is read-only/ephemeral (uploads + storage/installed.lock + setup writing config.php all break), and MySQL must be external. Vercel is therefore NOT recommended as-is.
- Recommended host: Hostinger (per original README — PHP + MySQL, upload contents to public_html, open domain, run /setup) or Render Docker php:8.2-apache + external MySQL.
- Setup needs: MySQL database + user, site name, tagline, keywords, contact email, admin username/password (8+ chars).
- After setup: /setup deletes itself, login at /admin/, Import can load 18 starter games/apps from Play Store links.

## Render + TiDB Cloud (working route, Nov 2026)
- Host: Render free Web Service, Docker (php:8.2-apache, mod_rewrite, pdo_mysql/gd/mbstring/curl). Sleeps after 15 min idle on free tier (30-60s wake).
- DB: TiDB Cloud Starter (free, no card, MySQL-compatible, 5 GiB). TLS required: DB_PORT=4000, DB_SSL=1.
- Env vars on Render: DB_HOST, DB_NAME, DB_USER, DB_PASS, DB_PORT=4000, DB_SSL=1, INSTALLED=0 during setup, then INSTALLED=1 after setup (lock file is lost on redeploy; env flag survives).
- Setup: open the Render URL, /setup/ with DB details + site_name=Apkora, tagline/keywords from SEO-PACK.md, contact email, admin user/pass. Then set INSTALLED=1 in Render and redeploy.
