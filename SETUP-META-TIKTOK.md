# Meta + TikTok app setup and approvals

You (Swift Fusion) own **one** Meta app and **one** TikTok app. They serve every client.
You connect each client's accounts yourself: BoostMe → Clients → client → Connect.

---

## Part A: Meta app (Facebook + Instagram)

Use **your own** Facebook account for developers.facebook.com, not the client's.
You can reuse the existing **BoostMe** app (ID 1040148602389540) and jump to step 3. To start fresh:

### 1. Create the app
1. developers.facebook.com → **My Apps → Create app**.
2. Use case: **Other** → Next → app type **Business** → Next.
3. Name `BoostMe`, your contact email, Business portfolio **Swift Fusion** (create one if asked) → **Create app**.

### 2. Add Facebook Login for Business
1. Dashboard → **Add product → Facebook Login for Business → Set up**.
2. **Configurations → Create configuration**:
   - Name `BoostMe posting`; Login variation **General**; Token type **User access token**.
   - Assets: **Pages** and **Instagram accounts**.
   - Permissions: `pages_show_list`, `pages_manage_posts`, `pages_read_engagement`,
     `instagram_basic`, `instagram_content_publish`, `business_management`.
   - Create, then copy the **Configuration ID**.
3. **Facebook Login for Business → Settings**: Client OAuth login **On**, Web OAuth login **On**,
   Enforce HTTPS **On**. Under Valid OAuth Redirect URIs, add exactly:
   `https://boostme.swiftfusion.co.za/connect/meta` → Save.

### 3. App settings → Basic
- App domains: `boostme.swiftfusion.co.za`
- Privacy Policy URL: `https://boostme.swiftfusion.co.za/privacy`
- Terms of Service URL: `https://boostme.swiftfusion.co.za/terms`
- User data deletion → Data deletion instructions URL: `https://boostme.swiftfusion.co.za/privacy`
- Category **Business**; App icon 1024×1024 → **Save**.
- Copy the **App ID** and **App secret** (Show).

### 4. Put the keys in BoostMe
BoostMe → **Settings** → paste App ID, App secret and Configuration ID → Save.

### 5. Give the client's Facebook a role on the app (avoids App Review)
Until Meta approves the app (step 8), it only works for people who have a role on it. Pick one:
- **Option 1 (best, scales):** the client makes **your** Facebook an admin of their Page
  (Page → Settings → Page access → Add new → your name, full control) and a partner in their
  Business portfolio. You then always connect with your own login.
- **Option 2 (use the client's own Facebook — what we use for H&L):** you don't need access to their Page.
  1. In **your** browser profile: App dashboard → **App roles → Roles → Add People → Tester** → the
     client's Facebook name or profile link.
  2. In a **second browser profile** (or incognito) logged in as the client: open developers.facebook.com
     → **Get started** (registers them as a developer: confirm the email/phone) → then
     `developers.facebook.com/requests` → **Accept**.
  3. In that same client window, log in to BoostMe as admin → H&L → **Connect**.
  Repeat for each new client.

### 6. Switch the app to Live
At the top of the dashboard (or **Publish** in the left menu), switch **App mode** to **Live**.
In Development mode, posts are visible **only to people with a role on the app**, so the public
would never see them. Live needs the URLs and icon from step 3.

### 7. Connect H&L
1. Check the client's Instagram is **Professional (Business)** and linked to their Facebook Page
   (Instagram → Settings → Account type; Page → Settings → Linked accounts).
2. In the same browser, log in to Facebook as the account that manages the Page.
3. BoostMe → Clients → H&L Beauty Salon → **Connect** (Facebook). When Facebook asks, choose
   **Opt in to all current and future** (or tick the H&L Page and its Instagram) → Continue.
4. Facebook and Instagram should both show Connected. Make a test post → **Post now**.

### 8. Approvals (only needed when clients' Pages have nobody with a role on your app)
1. **Business verification:** business.facebook.com → Settings → **Security Center → Start
   verification**. You need your CIPC registration, a document with the business address
   (e.g. a utility bill or bank letter), and the swiftfusion.co.za domain or email.
   It takes about 1–5 working days.
2. **Access verification** (Meta asks this of apps that serve other businesses): answer the
   form when it appears in the dashboard.
3. **App Review → Permissions and features**: request **Advanced access** for each permission in
   step 2. For each one:
   - Write one line on what it's for, e.g. "BoostMe publishes scheduled photo/video posts that our
     agency's clients approve, to their own Facebook Page."
   - Upload a screencast: log in to BoostMe → Clients → Connect → Facebook dialog → choose Page →
     create post → Post now → the post on Facebook/Instagram.
   - Give the reviewers a BoostMe admin test login (make a separate admin user for them).
4. Submit. A review usually takes 1–2 weeks. If it's rejected, Meta says which step was unclear;
   re-record that part and resubmit.

---

## Part B: TikTok app

### 1. Create the app
developers.tiktok.com → log in (your own account) → **Manage apps → Connect an app** → as an
**organisation** (Swift Fusion). Fill in:
- Name `BoostMe`, icon, category, a short description ("Schedules and publishes social posts for
  our agency's clients").
- Terms: `https://boostme.swiftfusion.co.za/terms`; Privacy: `https://boostme.swiftfusion.co.za/privacy`.
- Platform **Web** → `https://boostme.swiftfusion.co.za`.

### 2. Products
- **Login Kit** → Redirect URI: `https://boostme.swiftfusion.co.za/connect/tiktok`.
- **Content Posting API** → scopes `user.info.basic`, `video.upload`, `video.publish`.

### 3. Verify the domain
**URL properties → Add → URL prefix** `https://boostme.swiftfusion.co.za/` → download the file.
Open it in Notepad. In BoostMe → Settings, paste the **file name** (e.g. `tiktokAbC123.txt`) and the
**content** → Save → back in TikTok, press **Verify**.

### 4. Keys + sandbox test
- Paste the **Client key** and **Client secret** into BoostMe → Settings.
- Before approval: **Sandbox** tab → create a sandbox → **Target users** → add H&L's TikTok
  account. Use the *sandbox* key and secret in BoostMe while testing.
- Log in to H&L's TikTok in the same browser → BoostMe → H&L → **Connect** (TikTok).

### 5. Submit for review
**Submit for review**: explain each product and scope, and upload a demo video of the live site:
Connect TikTok → create post → Post now → the post arriving in the TikTok inbox. It takes a few
days to 2 weeks. Once approved, switch BoostMe to the **production** key and secret, then reconnect.

### 6. Inbox vs Direct
- **Inbox** (default): posts land in the client's TikTok inbox and they tap Post. This works
  after the step 5 approval.
- **Direct**: also needs TikTok's **Direct Post audit** (Content Posting API page). Until then,
  direct posts are private ("only me"). Switch H&L to Direct only after that audit passes.
