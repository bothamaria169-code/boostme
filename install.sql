-- BoostMe App (PHP SaaS) — run once in phpMyAdmin on the empty database.
-- Then open https://boostme.swiftfusion.co.za/setup to create the admin login.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS clients (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(120) NOT NULL,
  industry      VARCHAR(80)  NOT NULL DEFAULT '',
  contact_name  VARCHAR(120) NOT NULL DEFAULT '',
  email         VARCHAR(190) NOT NULL DEFAULT '',          -- weekly report goes here
  phone         VARCHAR(40)  NOT NULL DEFAULT '',
  hashtags      VARCHAR(500) NOT NULL DEFAULT '',          -- added to every caption
  signature     VARCHAR(300) NOT NULL DEFAULT '',          -- e.g. "Book: 071 000 0000"
  tiktok_mode   ENUM('inbox','direct') NOT NULL DEFAULT 'inbox',
  weekly_email  TINYINT(1) NOT NULL DEFAULT 1,
  last_report   DATETIME NULL,
  active        TINYINT(1) NOT NULL DEFAULT 1,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(120) NOT NULL,
  email         VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role          ENUM('admin','client') NOT NULL DEFAULT 'client',
  client_id     INT UNSIGNED NULL,
  last_login    DATETIME NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- One row per client per platform. Facebook + Instagram share the Page token.
CREATE TABLE IF NOT EXISTS connections (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  client_id     INT UNSIGNED NOT NULL,
  platform      ENUM('facebook','instagram','tiktok') NOT NULL,
  account_id    VARCHAR(100) NOT NULL,
  account_name  VARCHAR(150) NOT NULL DEFAULT '',
  token         TEXT NOT NULL,
  refresh_token TEXT NULL,
  expires_at    INT UNSIGNED NOT NULL DEFAULT 0,             -- 0 = does not expire
  updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY one_per_platform (client_id, platform),
  FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS posts (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  client_id     INT UNSIGNED NOT NULL,
  caption       TEXT NOT NULL,
  media         TEXT NOT NULL,                               -- JSON list of paths under uploads/
  media_type    ENUM('photo','video') NOT NULL DEFAULT 'photo',
  platforms     VARCHAR(60) NOT NULL,                        -- "facebook,instagram,tiktok"
  scheduled_at  DATETIME NOT NULL,
  status        ENUM('pending','scheduled','posting','posted','partial','failed') NOT NULL DEFAULT 'scheduled',
  created_by    INT UNSIGNED NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY due (status, scheduled_at),
  FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- What each platform answered. A platform with ok=1 is never posted again for that post.
CREATE TABLE IF NOT EXISTS post_results (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  post_id       INT UNSIGNED NOT NULL,
  platform      VARCHAR(20) NOT NULL,
  ok            TINYINT(1) NOT NULL,
  remote_id     VARCHAR(120) NOT NULL DEFAULT '',
  link          VARCHAR(300) NOT NULL DEFAULT '',
  error         VARCHAR(500) NOT NULL DEFAULT '',
  posted_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY one_result (post_id, platform),
  FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS settings (
  k VARCHAR(80) PRIMARY KEY,
  v TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS email_log (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  client_id  INT UNSIGNED NULL,
  recipient  VARCHAR(190) NOT NULL,
  subject    VARCHAR(200) NOT NULL,
  ok         TINYINT(1) NOT NULL,
  error      VARCHAR(300) NOT NULL DEFAULT '',
  sent_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- First client. Fill in the email/phone in Clients → H&L Beauty Salon.
INSERT INTO clients (name, industry, hashtags, signature, tiktok_mode)
SELECT 'H&L Beauty Salon', 'Hair salon & nail bar',
       '#HLBeautySalon #HairGoals #Braids #Wigs #NailBar #Nails #SouthAfricanHair #HairInspo',
       'Book your appointment today. DM us or WhatsApp to book.',
       'inbox'
WHERE NOT EXISTS (SELECT 1 FROM clients WHERE name = 'H&L Beauty Salon');
