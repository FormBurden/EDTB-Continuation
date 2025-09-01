-- Stations (legacy-compatible) + systems view adapter

-- 1) Compatibility view so old code that references "edtb_systems" keeps working.
--    Maps to the new "systems" table created in 0001_core.sql.
CREATE OR REPLACE VIEW edtb_systems AS
SELECT id, name, x, y, z
FROM systems;

-- 2) Main stations table expected by legacy UI.
CREATE TABLE IF NOT EXISTS edtb_stations (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  system_id INT UNSIGNED NOT NULL,
  name VARCHAR(128) NOT NULL,

  -- Distances / attributes used in UI
  ls_from_star INT UNSIGNED NULL,
  max_landing_pad_size VARCHAR(1) NULL,   -- 'S','M','L' (kept flexible)

  faction VARCHAR(128) NULL,
  government VARCHAR(64) NULL,
  allegiance VARCHAR(64) NULL,
  state VARCHAR(64) NULL,
  type VARCHAR(64) NULL,

  import_commodities TEXT NULL,
  export_commodities TEXT NULL,
  prohibited_commodities TEXT NULL,
  economies VARCHAR(255) NULL,
  selling_ships TEXT NULL,

  -- Facilities toggles shown in the left column
  shipyard TINYINT(1) NOT NULL DEFAULT 0,
  outfitting TINYINT(1) NOT NULL DEFAULT 0,
  commodities_market TINYINT(1) NOT NULL DEFAULT 0,
  black_market TINYINT(1) NOT NULL DEFAULT 0,
  refuel TINYINT(1) NOT NULL DEFAULT 0,
  repair TINYINT(1) NOT NULL DEFAULT 0,
  rearm TINYINT(1) NOT NULL DEFAULT 0,
  is_planetary TINYINT(1) NOT NULL DEFAULT 0,

  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

  CONSTRAINT uq_station_per_system UNIQUE (system_id, name),
  KEY idx_stations_system (system_id),
  KEY idx_stations_name (name),
  KEY idx_stations_ls (ls_from_star),

  CONSTRAINT fk_stations_system
    FOREIGN KEY (system_id) REFERENCES systems(id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
