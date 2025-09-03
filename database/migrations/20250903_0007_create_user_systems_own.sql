CREATE TABLE IF NOT EXISTS user_systems_own (
  id   INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(128) NOT NULL,
  x    DOUBLE NOT NULL DEFAULT 0,
  y    DOUBLE NOT NULL DEFAULT 0,
  z    DOUBLE NOT NULL DEFAULT 0,
  UNIQUE KEY uq_user_systems_own_name (name),
  KEY idx_user_systems_own_xyz (x, y, z)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
