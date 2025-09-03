-- Create missing table used by Nearest Systems autocomplete & lookups
CREATE TABLE IF NOT EXISTS user_systems_own (
  id   INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(128) NOT NULL,
  x    DOUBLE NOT NULL DEFAULT 0,
  y    DOUBLE NOT NULL DEFAULT 0,
  z    DOUBLE NOT NULL DEFAULT 0,
  UNIQUE KEY uq_user_systems_own_name (name),
  KEY idx_user_systems_own_xyz (x, y, z)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed from visited systems (names -> coords via edtb_systems)
INSERT IGNORE INTO user_systems_own (name, x, y, z)
SELECT es.name, es.x, es.y, es.z
FROM edtb_systems es
JOIN (
  SELECT DISTINCT system_name FROM user_visited_systems
) u ON u.system_name = es.name;

-- Seed from bookmarks by name
INSERT IGNORE INTO user_systems_own (name, x, y, z)
SELECT es.name, es.x, es.y, es.z
FROM edtb_systems es
JOIN (
  SELECT DISTINCT COALESCE(system_name, '') AS system_name
  FROM user_bookmarks
) b ON b.system_name <> '' AND b.system_name = es.name;

-- Seed from POI by name
INSERT IGNORE INTO user_systems_own (name, x, y, z)
SELECT es.name, es.x, es.y, es.z
FROM edtb_systems es
JOIN (
  SELECT DISTINCT COALESCE(system_name, '') AS system_name
  FROM user_poi
) p ON p.system_name <> '' AND p.system_name = es.name;

-- Seed from bookmarks by system_id
INSERT IGNORE INTO user_systems_own (name, x, y, z)
SELECT es.name, es.x, es.y, es.z
FROM edtb_systems es
JOIN user_bookmarks b2 ON b2.system_id = es.id;

-- Seed from POI by system_id
INSERT IGNORE INTO user_systems_own (name, x, y, z)
SELECT es.name, es.x, es.y, es.z
FROM edtb_systems es
JOIN user_poi p2 ON p2.system_id = es.id;
