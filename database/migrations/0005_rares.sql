-- 0005_rares.sql
-- Linux-port migration: create table used by Map/Services/Fetch/Rares.php
-- Charset/collation aligned with other user_* and edtb_* tables.

CREATE TABLE IF NOT EXISTS `edtb_rares` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `system_name` VARCHAR(255) NOT NULL,
  `item` VARCHAR(255) NOT NULL,
  -- Some legacy codepaths (Windows-era) referenced `name`; keep for compatibility.
  `name` VARCHAR(255) DEFAULT NULL,
  `station` VARCHAR(255) DEFAULT NULL,
  `ls_to_star` INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_system_name` (`system_name`),
  KEY `idx_item` (`item`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

