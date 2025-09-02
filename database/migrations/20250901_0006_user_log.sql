-- User log entries per system (minimal columns required by System::isLogged)
CREATE TABLE IF NOT EXISTS user_log (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  system_id BIGINT UNSIGNED NULL,
  system_name VARCHAR(255) NOT NULL DEFAULT '',
  title VARCHAR(255) DEFAULT '',
  body  TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_user_log_system_id   (system_id),
  KEY idx_user_log_system_name (system_name),
  KEY idx_user_log_created_at  (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
