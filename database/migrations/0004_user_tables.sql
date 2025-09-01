-- Minimal user tables used by left column/UI bits. Safe/idempotent.

CREATE TABLE IF NOT EXISTS user_bookmarks (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  system_id   INT UNSIGNED DEFAULT NULL,
  system_name VARCHAR(128) DEFAULT '',
  created_at  DATETIME NULL,
  KEY idx_system_id (system_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS user_poi (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  system_id   INT UNSIGNED DEFAULT NULL,
  system_name VARCHAR(128) DEFAULT '',
  note        TEXT NULL,
  created_at  DATETIME NULL,
  KEY idx_system_id (system_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS user_visited (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  system_id     INT UNSIGNED DEFAULT NULL,
  system_name   VARCHAR(128) DEFAULT '',
  visit_count   INT UNSIGNED NOT NULL DEFAULT 1,
  last_visited  DATETIME NULL,
  KEY idx_system_id (system_id),
  KEY idx_system_name (system_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
