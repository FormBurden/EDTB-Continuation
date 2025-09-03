CREATE TABLE IF NOT EXISTS user_poi (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name        VARCHAR(128) NOT NULL,
  system_name VARCHAR(128) NOT NULL,
  category_id INT UNSIGNED DEFAULT NULL,
  comment     TEXT NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_user_poi_system_name (system_name),
  KEY idx_user_poi_category_id (category_id),
  CONSTRAINT fk_user_poi_category
    FOREIGN KEY (category_id)
    REFERENCES user_poi_categories(id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
