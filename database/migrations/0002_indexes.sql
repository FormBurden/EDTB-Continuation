CREATE INDEX IF NOT EXISTS idx_systems_name       ON edtb_systems(name);
CREATE INDEX IF NOT EXISTS idx_systems_power      ON edtb_systems(power);
CREATE INDEX IF NOT EXISTS idx_systems_allegiance ON edtb_systems(allegiance);

CREATE INDEX IF NOT EXISTS idx_stations_system    ON edtb_stations(system_id);
CREATE INDEX IF NOT EXISTS idx_stations_name      ON edtb_stations(name);
