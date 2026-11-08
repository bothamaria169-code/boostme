# BoostMe App — going live on boostme.swiftfusion.co.za

Plain PHP 8.1+ (8.2 recommended) + MySQL. No Composer, no Node, no SSH needed.
PHP extensions: `pdo_mysql`, `curl`, `gd`, `mbstring`.

## 1. Code (cPanel Git)
1. cPanel → **Git Version Control** → **Create** → Clone URL
   `https://github.com/bothamaria169-code/boostme.git` (public repo, no token needed),
   Repository Path `repositories/boostme` → Create.
2. cPanel → **Domains** → `boostme.swiftfusion.co.za` → **Manage** → Document Root
   `repositories/boostme` → Update.
3. Later updates: **Manage → Pull or Deploy → Update from Remote**. `.env` and `uploads/` are
   git-ignored, so updates never touch them.

## 2. Database
phpMyAdmin → `swiftfusionco_boostme` → **Import** → `install.sql`. (Creates the tables and the
H&L Beauty Salon client.) Make sure user `swiftfusionco_booster` has ALL PRIVILEGES on it.

## 3. .env
File Manager → copy `.env.example` to `.env` → fill in `DB_PASS`, and the mailbox
(`MAIL_USER`/`MAIL_PASS` — create `boostme@swiftfusion.co.za` in cPanel → Email Accounts).

## 4. First login
Open `https://boostme.swiftfusion.co.za/setup` → create your admin login (works only once).
Then **Clients → H&L Beauty Salon → Manage**: add their email + phone, and **Create login** for them.

## 5. Cron (posts + Monday reports)
cPanel → **Cron Jobs** → every 5 minutes (`*/5 * * * *`):
```
/usr/local/bin/php /home/swiftfusionco/repositories/boostme/cron.php >/dev/null 2>&1
```
Check the PHP path: if cPanel shows a versioned one (e.g. `/usr/local/bin/ea-php82`), use that.

## 6. Meta + TikTok apps
Follow **SETUP-META-TIKTOK.md**: creating the apps, redirect URIs, Live mode, connecting H&L, and the
Meta App Review / TikTok review steps. You connect each client's accounts yourself (admin only).

## Daily use
- Admin: Dashboard (all clients) → client → New post / Approve / Post now / Retry failed.
- Client: own Dashboard (sees account status), uploads posts (wait for your approval), Monday email.
- Guide for clients: `/guide` (BoostMe-Guide.pdf).
