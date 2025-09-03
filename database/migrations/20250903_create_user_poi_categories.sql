CREATE TABLE IF NOT EXISTS user_poi_categories (
  id   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(64)  NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_user_poi_categories_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO user_poi_categories (name)
VALUES ('Station'), ('Planet'), ('Nebula'), ('POI'), ('Bookmark');
