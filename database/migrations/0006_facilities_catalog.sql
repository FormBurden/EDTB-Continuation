CREATE TABLE IF NOT EXISTS edtb_facilities (
  id   INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(128) NOT NULL,
  UNIQUE KEY uq_facilities_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO edtb_facilities (name) VALUES
('Black Market'),
('Commodities Market'),
('Mission Board'),
('Contacts'),
('Universal Cartographics'),
('Outfitting'),
('Shipyard'),
('Refuel'),
('Repair'),
('Restock (Munitions)'),
('Interstellar Factors'),
('Material Trader'),
('Technology Broker'),
('Engineer Workshop'),
('Search and Rescue'),
('Crew Lounge'),
('Livery'),
('Large Pad'),
('Medium Pad'),
('Small Pad');
