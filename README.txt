GAMEHUB - Games & Apps Website Script (PHP 8 + MySQL) - Hostinger ready
=======================================================================

INSTALL
1. Hostinger hPanel > Databases > MySQL Databases: create a database + user (note the name, user, password).
2. Upload and extract the zip into public_html (or a subdomain folder). Move the CONTENTS of the "gamehub" folder
   into that folder, so index.php sits directly in it. Use a separate folder + database for every website.
3. Open your domain. A fresh install redirects to /setup automatically.
4. Fill in: database details, website name, tagline, tags, contact email, admin username/password.
   (Database details can also be pre-filled in config.php, but it is not required: setup writes config.php for you.)
5. Click "Install Website". Tables, default pages (About, Contact, Privacy, Terms, DMCA) and FAQs are created and
   the /setup folder deletes itself.
6. Log in at /admin/.

ADMIN
- Games & Apps: add/edit, mark as Game, App or both, category (existing or new), draft/publish, bulk select + publish/unpublish/delete, "Delete ALL".
- Import: paste one or many Google Play links (one per line) -> published immediately. "Load starter pack" fills 18 popular games and apps.
- Categories, Pages (auto in footer), FAQs, Settings (logo 240x60, favicon 512x512), Header/Footer code, ads.txt, Sitemap, Account.

HOMEPAGE ORDER: Popular (games + apps) > Games > Apps > FAQs.
URLS: / , /game/{slug}, /category/{slug}, /browse/popular|games|apps, /search?q=, /p/{page}, /sitemap.xml, /robots.txt, /ads.txt
The sitemap is not linked on the site (submit sitemap.xml in Google Search Console instead).

UPGRADING an existing install: upload the new files over the old ones (keep config.php, uploads/ and storage/).
The database is upgraded automatically the first time you open the site or admin.

REQUIREMENTS: PHP 8.0+, MySQL/MariaDB, extensions pdo_mysql, curl, mbstring, fileinfo/gd.
