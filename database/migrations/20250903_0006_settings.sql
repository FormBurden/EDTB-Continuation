-- Settings UI: categories, catalog, and current values

CREATE TABLE IF NOT EXISTS edtb_settings_categories (
  id      INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name    VARCHAR(128) NOT NULL,
  weight  INT NOT NULL DEFAULT 0,
  UNIQUE KEY uq_edtb_settings_categories_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS edtb_settings_info (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  variable     VARCHAR(128) NOT NULL,
  name         VARCHAR(255) NOT NULL,
  type         VARCHAR(255) NOT NULL,          -- 'numeric' | 'textbox' | 'tf' | 'array' | 'enum::<vals>'
  info         TEXT NULL,                      -- help/description
  category_id  INT UNSIGNED NOT NULL,
  weight       INT NOT NULL DEFAULT 0,
  UNIQUE KEY uq_edtb_settings_info_variable (variable),
  KEY idx_edtb_settings_info_category (category_id),
  CONSTRAINT fk_edtb_settings_info_category
    FOREIGN KEY (category_id) REFERENCES edtb_settings_categories(id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS user_settings (
  id        INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  variable  VARCHAR(128) NOT NULL,
  value     TEXT NULL,
  UNIQUE KEY uq_user_settings_variable (variable)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Categories (ids used by Admin UI)
INSERT INTO edtb_settings_categories (id, name, weight) VALUES
  (1, 'General', 10),
  (2, 'Display', 20),
  (3, 'Audio',   30),
  (4, 'Paths',   40)
ON DUPLICATE KEY UPDATE name=VALUES(name), weight=VALUES(weight);

-- Catalog (Admin/index.php reads 'type' and 'info')
-- enum syntax: enum::value>>Label&&value>>Label
INSERT INTO edtb_settings_info (variable, name, type, info, category_id, weight) VALUES
  ('data_notify_age', 'Stale Data Warning (days)', 'numeric',
   'How many days before data is considered stale for notifications.', 1, 10),

  ('default_map', 'Default Map',
   'enum::system_map>>System Map&&galaxy_map>>Galaxy Map',
   'Which map opens by default.', 2, 10),

  ('sidebar_style', 'Sidebar Style',
   'enum::normal>>Normal&&narrow>>Narrow',
   'Switch between normal and narrow sidebar.', 2, 20),

  ('tts_override', 'TTS Override Map', 'array',
   'JSON (or one per line) of find→replace for TTS output.', 3, 10)
ON DUPLICATE KEY UPDATE
  name=VALUES(name), type=VALUES(type), info=VALUES(info),
  category_id=VALUES(category_id), weight=VALUES(weight);

-- Defaults so the LEFT JOIN for cat_id=2 returns rows
INSERT INTO user_settings (variable, value) VALUES
  ('data_notify_age', '30'),
  ('default_map', 'system_map'),
  ('sidebar_style', 'normal'),
  ('tts_override', '')
ON DUPLICATE KEY UPDATE value=VALUES(value);
