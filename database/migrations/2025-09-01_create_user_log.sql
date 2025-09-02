CREATE TABLE IF NOT EXISTS user_log (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  system_id INT UNSIGNED NULL,
  system_name VARCHAR(255) NOT NULL DEFAULT '',
  station_id INT UNSIGNED NULL,
  log_entry MEDIUMTEXT NOT NULL,
  stardate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  title VARCHAR(255) DEFAULT NULL,
  pinned TINYINT(1) NOT NULL DEFAULT 0,
  type ENUM('system','general','personal') NOT NULL DEFAULT 'system',
  audio TEXT NULL,
  weight INT NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY idx_system_name (system_name),
  KEY idx_type (type),
  KEY idx_stardate (stardate),
  KEY idx_pinned_weight (pinned, weight)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
