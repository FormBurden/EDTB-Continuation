-- User bookmarks: allows either a numeric system_id or a name fallback
CREATE TABLE IF NOT EXISTS user_bookmarks (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  system_id BIGINT UNSIGNED NULL,
  system_name VARCHAR(255) NOT NULL DEFAULT '',
  note TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_user_bookmarks_system_id (system_id),
  KEY idx_user_bookmarks_system_name (system_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Points of Interest (leftColumn checks this table too)
CREATE TABLE IF NOT EXISTS user_poi (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  system_id BIGINT UNSIGNED NULL,
  system_name VARCHAR(255) NOT NULL DEFAULT '',
  title VARCHAR(255) NOT NULL DEFAULT '',
  description TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_user_poi_system_id (system_id),
  KEY idx_user_poi_system_name (system_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Visited systems (Gallery uses this)
CREATE TABLE IF NOT EXISTS user_visited_systems (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  system_id BIGINT UNSIGNED NULL,
  system_name VARCHAR(255) NOT NULL DEFAULT '',
  visited_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_user_visited_systems_system_id (system_id),
  KEY idx_user_visited_systems_system_name (system_name),
  KEY idx_user_visited_systems_visited_at (visited_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
