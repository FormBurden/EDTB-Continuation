CREATE TABLE IF NOT EXISTS edtb_powers (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(128) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO edtb_powers (name) VALUES
('Aisling Duval'),
('Arissa Lavigny-Duval'),
('Denton Patreus'),
('Edmund Mahon'),
('Felicia Winters'),
('Li Yong-Rui'),
('Pranav Antal'),
('Archon Delaine'),
('Zemina Torval'),
('Yuri Grom'),
('Zachary Hudson');
