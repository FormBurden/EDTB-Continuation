-- Core systems table (subset of original columns used by UI)
CREATE TABLE IF NOT EXISTS edtb_systems (
  id            INT UNSIGNED NOT NULL PRIMARY KEY,
  name          VARCHAR(128) NOT NULL,
  allegiance    VARCHAR(32)  DEFAULT NULL,
  economy       VARCHAR(64)  DEFAULT NULL,
  government    VARCHAR(64)  DEFAULT NULL,
  security      VARCHAR(64)  DEFAULT NULL,
  power         VARCHAR(64)  DEFAULT NULL,
  power_state   VARCHAR(64)  DEFAULT NULL,
  x             DOUBLE NOT NULL DEFAULT 0,
  y             DOUBLE NOT NULL DEFAULT 0,
  z             DOUBLE NOT NULL DEFAULT 0,
  population    BIGINT UNSIGNED DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Powers list used by filters; only "name" is read by UI
CREATE TABLE IF NOT EXISTS edtb_powers (
  id   INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(64) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Stations table (columns referenced by NearestSystems filters/queries)
CREATE TABLE IF NOT EXISTS edtb_stations (
  id                       INT UNSIGNED NOT NULL PRIMARY KEY,
  system_id                INT UNSIGNED NOT NULL,
  name                     VARCHAR(128) NOT NULL,
  type                     VARCHAR(64)  DEFAULT NULL,
  max_landing_pad_size     VARCHAR(1)   DEFAULT NULL,
  economies                VARCHAR(255) DEFAULT NULL,
  faction                  VARCHAR(128) DEFAULT NULL,
  allegiance               VARCHAR(32)  DEFAULT NULL,
  government               VARCHAR(64)  DEFAULT NULL,
  state                    VARCHAR(64)  DEFAULT NULL,
  ls_from_star             INT UNSIGNED DEFAULT 0,
  commodities_market       TINYINT(1)   DEFAULT 0,
  outfitting               TINYINT(1)   DEFAULT 0,
  rearm                    TINYINT(1)   DEFAULT 0,
  refuel                   TINYINT(1)   DEFAULT 0,
  repair                   TINYINT(1)   DEFAULT 0,
  shipyard                 TINYINT(1)   DEFAULT 0,
  selling_ships            TEXT NULL,
  selling_modules          TEXT NULL,
  prohibited_commodities   TEXT NULL,
  import_commodities       TEXT NULL,
  export_commodities       TEXT NULL,
  black_market             TINYINT(1)   DEFAULT 0,
  is_planetary             TINYINT(1)   DEFAULT 0,
  outfitting_updated_at    DATETIME NULL,
  shipyard_updated_at      DATETIME NULL,
  KEY idx_system_id (system_id),
  KEY idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Optional: bookmark/poi stubs so left column checks never fatal
CREATE TABLE IF NOT EXISTS user_bookmarks (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  system_id   VARCHAR(64) DEFAULT '',
  system_name VARCHAR(128) DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS user_poi (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  system_id   VARCHAR(64) DEFAULT '',
  system_name VARCHAR(128) DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
