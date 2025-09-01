-- Minimal schema to satisfy current UI queries.

CREATE TABLE IF NOT EXISTS edtb_stations (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  system_id INT UNSIGNED NOT NULL,
  name VARCHAR(255) NOT NULL,
  ls_from_star INT UNSIGNED DEFAULT 0,
  max_landing_pad_size VARCHAR(8) DEFAULT '',
  faction VARCHAR(255) DEFAULT '',
  government VARCHAR(255) DEFAULT '',
  allegiance VARCHAR(255) DEFAULT '',
  state VARCHAR(255) DEFAULT '',
  type VARCHAR(255) DEFAULT '',
  economies TEXT,
  import_commodities TEXT,
  export_commodities TEXT,
  prohibited_commodities TEXT,
  selling_ships TEXT,
  selling_modules TEXT,
  shipyard TINYINT(1) DEFAULT 0,
  outfitting TINYINT(1) DEFAULT 0,
  commodities_market TINYINT(1) DEFAULT 0,
  black_market TINYINT(1) DEFAULT 0,
  refuel TINYINT(1) DEFAULT 0,
  repair TINYINT(1) DEFAULT 0,
  rearm TINYINT(1) DEFAULT 0,
  is_planetary TINYINT(1) DEFAULT 0,
  shipyard_updated_at DATETIME NULL,
  outfitting_updated_at DATETIME NULL,
  PRIMARY KEY (id),
  KEY idx_stations_system (system_id),
  KEY idx_stations_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS edtb_powers (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  system_name VARCHAR(255) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_power (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Optional: modules table referenced by stations page when building "Selling modules"
-- Only include if you don't already have this elsewhere.
CREATE TABLE IF NOT EXISTS edtb_modules (
  id INT UNSIGNED NOT NULL,
  class TINYINT UNSIGNED DEFAULT 0,
  rating CHAR(1) DEFAULT '',
  price INT UNSIGNED DEFAULT 0,
  group_name VARCHAR(255) DEFAULT '',
  category_name VARCHAR(255) DEFAULT '',
  PRIMARY KEY (id),
  KEY idx_modules_group (group_name),
  KEY idx_modules_category (category_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
