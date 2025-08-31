-- Track when the CMDR last entered each system (used by Gallery)
CREATE TABLE IF NOT EXISTS user_visited_systems (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  system_name VARCHAR(128) NOT NULL,
  visit DATETIME NOT NULL,
  -- helpful for fast "latest visit" lookups
  KEY idx_uvs_system_visit (system_name, visit),
  -- keep in sync with `systems.name`
  CONSTRAINT fk_uvs_system FOREIGN KEY (system_name)
    REFERENCES systems(name)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
