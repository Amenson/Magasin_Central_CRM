<?php
/**
 * Migration CRM — crée tables et rôles si absents (compatible MySQL/MariaDB)
 */
function runCrmMigration(PDO $pdo): void
{
    static $done = false;
    if ($done) return;
    $done = true;

    $pdo->exec("CREATE TABLE IF NOT EXISTS roles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL UNIQUE,
        label VARCHAR(100) NOT NULL,
        description VARCHAR(255) DEFAULT NULL,
        permissions TEXT NOT NULL,
        is_system TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS store_settings (
        setting_key VARCHAR(100) PRIMARY KEY,
        setting_value TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $adminCols = $pdo->query("SHOW COLUMNS FROM admins")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('email', $adminCols, true)) {
        $pdo->exec("ALTER TABLE admins ADD COLUMN email VARCHAR(100) DEFAULT NULL");
    }
    if (!in_array('role_id', $adminCols, true)) {
        $pdo->exec("ALTER TABLE admins ADD COLUMN role_id INT DEFAULT NULL");
    }
    if (!in_array('is_active', $adminCols, true)) {
        $pdo->exec("ALTER TABLE admins ADD COLUMN is_active TINYINT(1) DEFAULT 1");
    }
    if (!in_array('last_login_at', $adminCols, true)) {
        $pdo->exec("ALTER TABLE admins ADD COLUMN last_login_at DATETIME DEFAULT NULL");
    }

    $count = (int)$pdo->query("SELECT COUNT(*) FROM roles")->fetchColumn();
    if ($count === 0) {
        $roles = [
            ['super_admin', 'Super Administrateur', 'Accès complet', '["*"]', 1],
            ['manager', 'Gérant', 'Gestion magasin sans admin système', '["dashboard","products","orders","customers","reports","reports.export","settings.store"]', 1],
            ['vendeur', 'Vendeur', 'Commandes et clients', '["dashboard","orders","customers","reports"]', 1],
            ['stock', 'Gestionnaire stock', 'Produits et rapports stock', '["dashboard","products","reports","reports.export"]', 1],
        ];
        $stmt = $pdo->prepare("INSERT INTO roles (name, label, description, permissions, is_system) VALUES (?, ?, ?, ?, ?)");
        foreach ($roles as $r) {
            $stmt->execute($r);
        }
    }

    $pdo->exec("UPDATE admins SET role_id = (SELECT id FROM roles WHERE name = 'super_admin' LIMIT 1) WHERE role_id IS NULL");

    $settingsCount = (int)$pdo->query("SELECT COUNT(*) FROM store_settings")->fetchColumn();
    if ($settingsCount === 0 && defined('SITE_NAME')) {
        $defaults = [
            'site_name' => SITE_NAME,
            'site_tagline' => SITE_TAGLINE,
            'site_description' => SITE_DESCRIPTION,
            'site_email' => SITE_EMAIL,
            'site_phone' => SITE_PHONE,
            'site_whatsapp' => SITE_WHATSAPP,
            'site_address' => SITE_ADDRESS,
            'site_promo_code' => SITE_PROMO_CODE,
            'currency' => 'FCFA',
            'tax_rate' => '0',
            'low_stock_threshold' => '5',
        ];
        $stmt = $pdo->prepare("INSERT INTO store_settings (setting_key, setting_value) VALUES (?, ?)");
        foreach ($defaults as $k => $v) {
            $stmt->execute([$k, $v]);
        }
    }
}
