# BoostMe App — going live on boostme.swiftfusion.co.za

Plain PHP 8.1+ (8.2 recommended) + MySQL. No Composer, no Node, no SSH needed.
PHP extensions: `pdo_mysql`, `curl`, `gd`, `mbstring`.

## 1. Code (cPanel Git)
1. cPanel → **Domains** → make sure `boostme.swiftfusion.co.za` exists. Note its folder
   (e.g. `/home/swiftfusionco/boostme.swiftfusion.co.za`) and empty it.
2. cPanel → **Git Version Control** → **Create** → Clone URL
   `https://TOKEN@github.com/bothamaria169-code/boostme.git` (cPanel rejects `user:token@`),
   Repository Path = that subdomain folder.
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
/usr/local/bin/php /home/swiftfusionco/boostme.swiftfusion.co.za/cron.php >/dev/null 2>&1
```
Check the PHP path: if cPanel shows a versioned one (e.g. `/usr/local/bin/ea-php82`), use that.

## 6. Meta app (Facebook + Instagram) — reuse the existing "BoostMe" app (1040148602389540)
1. developers.facebook.com → BoostMe → **Facebook Login → Settings → Valid OAuth Redirect URIs**:
   `https://boostme.swiftfusion.co.za/connect/meta`
2. App settings → Basic: App Domain `boostme.swiftfusion.co.za`, Privacy URL `/privacy`, Terms URL `/terms`.
3. BoostMe → **Settings**: paste App ID + App secret (+ Configuration ID if it is a Login-for-Business app).
4. **Until Meta approves the app (App Review + Business Verification)**, only people with a role on the
   app can connect. Fastest route for H&L: they add *your* Facebook as an admin of their Page
   (Meta Business Suite → Settings → People/Partners), then *you* press Connect on their client page.

## 7. TikTok app
1. developers.tiktok.com → create app → add **Login Kit** + **Content Posting API**.
2. Redirect URI: `https://boostme.swiftfusion.co.za/connect/tiktok`. Terms/Privacy: `/terms`, `/privacy`.
3. **URL properties → verify domain**: TikTok gives a file like `tiktokXXXX.txt` — paste its name and
   content into BoostMe → Settings. BoostMe serves it.
4. Paste Client key + secret into Settings. In Sandbox, add the H&L TikTok account as a target user.
5. Leave H&L on **Inbox** mode: videos land in their TikTok inbox and they tap Post (adds a trending
   sound too). Switch to **Direct** only after TikTok audits the app.

## Daily use
- Admin: Dashboard (all clients) → client → New post / Approve / Post now / Retry failed.
- Client: own Dashboard, connect accounts, upload posts (wait for approval), Monday email.
- Guide for clients: `/guide` (BoostMe-Guide.pdf).
