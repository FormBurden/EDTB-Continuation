CREATE TABLE IF NOT EXISTS user_visited_systems (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  system_name VARCHAR(255) NOT NULL,
  visit DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_uvs_system (system_name),
  KEY idx_uvs_visit (visit)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO user_visited_systems (system_name, visit) VALUES ('__INIT__', NOW());
