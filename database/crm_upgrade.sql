-- CRM Admin upgrade — rôles, permissions, paramètres magasin
-- Exécuter dans phpMyAdmin sur la base `ecommerce`

CREATE TABLE IF NOT EXISTS `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `label` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `permissions` text NOT NULL,
  `is_system` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `store_settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Colonnes admin (ignorer erreur si déjà existantes)
ALTER TABLE `admins` ADD COLUMN IF NOT EXISTS `email` varchar(100) DEFAULT NULL;
ALTER TABLE `admins` ADD COLUMN IF NOT EXISTS `role_id` int(11) DEFAULT NULL;
ALTER TABLE `admins` ADD COLUMN IF NOT EXISTS `is_active` tinyint(1) DEFAULT 1;
ALTER TABLE `admins` ADD COLUMN IF NOT EXISTS `last_login_at` datetime DEFAULT NULL;

-- Rôles par défaut
INSERT IGNORE INTO `roles` (`id`, `name`, `label`, `description`, `permissions`, `is_system`) VALUES
(1, 'super_admin', 'Super Administrateur', 'Accès complet à toutes les fonctionnalités', '["*"]', 1),
(2, 'manager', 'Gérant', 'Gestion complète sauf paramètres sensibles', '["dashboard","products","orders","customers","reports","reports.export","settings.store"]', 1),
(3, 'vendeur', 'Vendeur', 'Commandes et clients', '["dashboard","orders","customers","reports"]', 1),
(4, 'stock', 'Gestionnaire stock', 'Produits et inventaire', '["dashboard","products","reports","reports.export"]', 1);

UPDATE `admins` SET `role_id` = 1 WHERE `role_id` IS NULL;
