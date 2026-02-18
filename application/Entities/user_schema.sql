CREATE TABLE `user` (
  `id` CHAR(36) NOT NULL,
  `user_name` VARCHAR(50) NOT NULL,
  `first_name` VARCHAR(100),
  `last_name` VARCHAR(100),
  `email_address` VARCHAR(255),
  `password_hash` VARCHAR(255),
  `is_admin` BOOLEAN DEFAULT FALSE,
  `is_active` BOOLEAN DEFAULT TRUE,
  `created_by_id` CHAR(36),
  `modified_by_id` CHAR(36),
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted` BOOLEAN DEFAULT FALSE,

  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_name` (`user_name`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_is_admin` (`is_admin`),
  KEY `idx_deleted` (`deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
