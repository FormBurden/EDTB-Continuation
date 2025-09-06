-- Create table for saved System Map layouts (per system)
CREATE TABLE IF NOT EXISTS `user_system_map` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `system_name` VARCHAR(255) NOT NULL,
  `string` MEDIUMTEXT NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_system_name` (`system_name`),
  KEY `idx_updated_at` (`updated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
