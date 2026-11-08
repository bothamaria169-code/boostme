# BoostMe App — going live on boostme.swiftfusion.co.za

Plain PHP 8.1+ (8.2 recommended) + MySQL. No Composer, no Node, no SSH needed.
PHP extensions: `pdo_mysql`, `curl`, `gd`, `mbstring`.

## 1. Code (cPanel Git)
1. cPanel → **Git Version Control** → **Create** → Clone URL
   `https://github.com/bothamaria169-code/boostme.git` (public repo, no token needed),
   Repository Path `repositories/boostme` → Create.
2. **Manage → Pull or Deploy → Deploy HEAD Commit.** This copies the app into the subdomain folder
   `/home/swiftfusionco/boostme.swiftfusion.co.za` (the subdomain keeps its normal document root).
3. Every later update: **Update from Remote**, then **Deploy HEAD Commit**. Deploys never touch
   `.env` or `uploads/`, and keep cPanel's PHP-version lines in `.htaccess`.

Check: `https://boostme.swiftfusion.co.za/BoostMe-Guide.pdf` opens → the code is live.

## 2. Database
phpMyAdmin → `swiftfusionco_boostme` → **Import** → `install.sql`. (Creates the tables and the
H&L Beauty Salon client.) Make sure user `swiftfusionco_boostme_admin` has ALL PRIVILEGES on it
(cPanel → MySQL Databases → Add User To Database).

## 3. .env
File Manager → open the **`boostme.swiftfusion.co.za`** folder (not `repositories/boostme`; tick
*Show Hidden Files* in Settings) → copy `.env.example` to `.env` → set `DB_USER=swiftfusionco_boostme_admin`, fill in `DB_PASS`, and the mailbox
(`MAIL_USER`/`MAIL_PASS` — create `boostme@swiftfusion.co.za` in cPanel → Email Accounts).

## 4. First login
Open `https://boostme.swiftfusion.co.za/setup` → create your admin login (works only once).
Then **Clients → H&L Beauty Salon → Manage**: add their email + phone, and **Create login** for them.

## 5. Cron (posts + Monday reports)
cPanel → **Cron Jobs** → every 5 minutes (`*/5 * * * *`):
```
/usr/local/bin/php /home/swiftfusionco/boostme.swiftfusion.co.za/cron.php >/dev/null 2>&1
```
Check the PHP path: if cPanel shows a versioned one (e.g. `/usr/local/bin/ea-php82`), use that.

## 6. Meta app (Facebook + Instagram)
Use **two browser windows**: one logged in to **your** Facebook (to manage the app), one (a second
Chrome profile or incognito) logged in to the **client's** Facebook (to connect their Page).

**In your window:**
1. developers.facebook.com → **My Apps** → open the existing **BoostMe** app (1040148602389540),
   or **Create app** → **Other** → **Business** → name `BoostMe`.
2. **Add product → Facebook Login for Business → Configurations → Create configuration**:
   Login variation **General**, token **User access token**, assets **Pages + Instagram accounts**,
   permissions `pages_show_list`, `pages_manage_posts`, `pages_read_engagement`, `instagram_basic`,
   `instagram_content_publish`, `business_management` → Create → copy the **Configuration ID**.
3. **Facebook Login for Business → Settings**: Client OAuth + Web OAuth + Enforce HTTPS **On**;
   Valid OAuth Redirect URIs: `https://boostme.swiftfusion.co.za/connect/meta` → Save.
4. **App settings → Basic**: App domain `boostme.swiftfusion.co.za`; Privacy Policy URL and Data
   deletion instructions URL `https://boostme.swiftfusion.co.za/privacy`; Terms URL
   `https://boostme.swiftfusion.co.za/terms`; Category Business; 1024×1024 icon → Save.
   Copy the **App ID** and **App secret**.
5. BoostMe → **Settings** → paste App ID, App secret, Configuration ID → Save.
6. **App roles → Roles → Add People → Tester** → the client's Facebook name/profile link.
7. Switch **App mode** to **Live** (top of the dashboard). In Development mode posts are only
   visible to people with a role on the app.

**In the client's window:**
8. Check their Instagram is a **Business/Professional** account linked to their Facebook Page.
9. developers.facebook.com → **Get started** (confirm email/phone) → `developers.facebook.com/requests`
   → **Accept** the Tester invite.
10. Log in to BoostMe as admin → **Clients → H&L Beauty Salon → Manage → Connect** (Facebook) →
    **Opt in to all** → Continue. Facebook and Instagram both show as connected.
11. **New post** → H&L → a photo → tick Facebook + Instagram → save → **Post now**. Check it on their Page.

Repeat 6, 8–10 for each new client. When you want clients connected without the Tester step,
apply for Meta App Review — see **SETUP-META-TIKTOK.md**, Part A step 8.

## 7. TikTok app
See **SETUP-META-TIKTOK.md**, Part B (create app, verify domain, sandbox, review).

## Daily use
- Admin: Dashboard (all clients) → client → New post / Approve / Post now / Retry failed.
- Client: own Dashboard (sees account status), uploads posts (wait for your approval), Monday email.
- Guide for clients: `/guide` (BoostMe-Guide.pdf).
