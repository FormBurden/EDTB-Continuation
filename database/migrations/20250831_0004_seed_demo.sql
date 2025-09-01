-- Demo seeds for systems + stations (safe to re-run)

-- === Systems (insert only if missing) ===
INSERT INTO systems (name, x, y, z)
SELECT 'HIP 5700', 112.10, -24.50, 35.80
WHERE NOT EXISTS (SELECT 1 FROM systems WHERE name='HIP 5700');

INSERT INTO systems (name, x, y, z)
SELECT 'HIP 5623', 114.70, -26.10, 37.20
WHERE NOT EXISTS (SELECT 1 FROM systems WHERE name='HIP 5623');

INSERT INTO systems (name, x, y, z)
SELECT 'Shinrarta Dezhra', 55.75, -52.00, -21.41
WHERE NOT EXISTS (SELECT 1 FROM systems WHERE name='Shinrarta Dezhra');

INSERT INTO systems (name, x, y, z)
SELECT 'Sol', 0.00, 0.00, 0.00
WHERE NOT EXISTS (SELECT 1 FROM systems WHERE name='Sol');

-- === Stations (insert only if missing) ===
-- HIP 5700 / Bracewell Port
INSERT INTO edtb_stations (
  system_id, name, ls_from_star, max_landing_pad_size,
  faction, government, allegiance, state, type,
  import_commodities, export_commodities, prohibited_commodities,
  economies, selling_ships,
  shipyard, outfitting, commodities_market, black_market,
  refuel, repair, rearm, is_planetary
)
SELECT
  (SELECT id FROM systems WHERE name='HIP 5700'),
  'Bracewell Port', 790, 'L',
  'HIP 5700 Corp', 'Corporate', 'Federation', 'Boom', 'Coriolis Starport',
  'Foods, Minerals', 'Machinery, Consumer Tech', 'Narcotics',
  'Industrial, High Tech', 'Adder, Cobra Mk III',
  1, 1, 1, 0,
  1, 1, 1, 0
WHERE NOT EXISTS (
  SELECT 1
  FROM edtb_stations s
  JOIN systems sy ON sy.id = s.system_id
  WHERE sy.name='HIP 5700' AND s.name='Bracewell Port'
);

-- HIP 5623 / d'Arrest Ring
INSERT INTO edtb_stations (
  system_id, name, ls_from_star, max_landing_pad_size,
  faction, government, allegiance, state, type,
  import_commodities, export_commodities, prohibited_commodities,
  economies, selling_ships,
  shipyard, outfitting, commodities_market, black_market,
  refuel, repair, rearm, is_planetary
)
SELECT
  (SELECT id FROM systems WHERE name='HIP 5623'),
  'd''Arrest Ring', 1040, 'L',
  'HIP 5623 Network', 'Corporate', 'Federation', 'Boom', 'Orbis Starport',
  'Food Cartridges, Minerals', 'Consumer Tech, Superconductors', 'Slaves',
  'High Tech, Industrial', 'Cobra Mk III, Viper Mk III',
  1, 1, 1, 0,
  1, 1, 1, 0
WHERE NOT EXISTS (
  SELECT 1
  FROM edtb_stations s
  JOIN systems sy ON sy.id = s.system_id
  WHERE sy.name='HIP 5623' AND s.name='d''Arrest Ring'
);

-- Shinrarta Dezhra / Jameson Memorial (all services)
INSERT INTO edtb_stations (
  system_id, name, ls_from_star, max_landing_pad_size,
  faction, government, allegiance, state, type,
  import_commodities, export_commodities, prohibited_commodities,
  economies, selling_ships,
  shipyard, outfitting, commodities_market, black_market,
  refuel, repair, rearm, is_planetary
)
SELECT
  (SELECT id FROM systems WHERE name='Shinrarta Dezhra'),
  'Jameson Memorial', 500, 'L',
  'The Pilots Federation', 'Corporate', 'Independent', 'Boom', 'Coriolis Starport',
  'Metals, Minerals', 'Consumer Tech, Machinery', 'Narcotics',
  'High Tech, Industrial', 'Anaconda, Asp Explorer, Python',
  1, 1, 1, 0,
  1, 1, 1, 0
WHERE NOT EXISTS (
  SELECT 1
  FROM edtb_stations s
  JOIN systems sy ON sy.id = s.system_id
  WHERE sy.name='Shinrarta Dezhra' AND s.name='Jameson Memorial'
);

-- Sol / Galileo (example planetary port)
INSERT INTO edtb_stations (
  system_id, name, ls_from_star, max_landing_pad_size,
  faction, government, allegiance, state, type,
  import_commodities, export_commodities, prohibited_commodities,
  economies, selling_ships,
  shipyard, outfitting, commodities_market, black_market,
  refuel, repair, rearm, is_planetary
)
SELECT
  (SELECT id FROM systems WHERE name='Sol'),
  'Galileo', 2100, 'M',
  'Federal Administration', 'Democracy', 'Federation', 'Boom', 'Planetary Port',
  'Food Cartridges', 'Basic Medicines', 'Onionhead',
  'Industrial, High Tech', 'Eagle, Sidewinder',
  1, 1, 1, 0,
  1, 1, 1, 1
WHERE NOT EXISTS (
  SELECT 1
  FROM edtb_stations s
  JOIN systems sy ON sy.id = s.system_id
  WHERE sy.name='Sol' AND s.name='Galileo'
);
