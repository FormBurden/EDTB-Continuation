-- Core table for ED systems (from systems_populated.csv: name,x,y,z)
CREATE TABLE IF NOT EXISTS systems (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(128) NOT NULL,
  x DOUBLE NOT NULL,
  y DOUBLE NOT NULL,
  z DOUBLE NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_systems_name (name),
  KEY idx_systems_xyz (x, y, z)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
