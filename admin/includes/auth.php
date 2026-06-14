<?php
require_once __DIR__ . '/migrate.php';

const ADMIN_PERMISSIONS = [
    'dashboard'       => 'Tableau de bord',
    'products'        => 'Produits',
    'orders'          => 'Commandes',
    'customers'       => 'Clients',
    'reports'         => 'Rapports',
    'reports.export'  => 'Export rapports',
    'settings.admins' => 'Utilisateurs admin',
    'settings.roles'  => 'Rôles',
    'settings.store'  => 'Configuration magasin',
];

function requireAdminLogin(): void
{
    if (!isset($_SESSION['admin_id'])) {
        header('Location: ' . adminUrl('login.php'));
        exit;
    }
}

function adminUrl(string $path = ''): string
{
    $base = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    if (str_ends_with($base, '/includes')) {
        $base = dirname($base);
    }
    if (str_contains($path, 'reports/') || str_contains($path, 'settings/')) {
        return '../' . $path;
    }
    return $path;
}

function loadAdminPermissions(PDO $pdo, int $adminId): array
{
    $stmt = $pdo->prepare("
        SELECT r.permissions
        FROM admins a
        LEFT JOIN roles r ON r.id = a.role_id
        WHERE a.id = ? AND a.is_active = 1
    ");
    $stmt->execute([$adminId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row || empty($row['permissions'])) {
        return ['*'];
    }
    $perms = json_decode($row['permissions'], true);
    return is_array($perms) ? $perms : ['*'];
}

function adminHasPermission(string $permission): bool
{
    $perms = $_SESSION['admin_permissions'] ?? ['*'];
    if (in_array('*', $perms, true)) {
        return true;
    }
    return in_array($permission, $perms, true);
}

function requirePermission(string $permission): void
{
    requireAdminLogin();
    if (!adminHasPermission($permission)) {
        http_response_code(403);
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Accès refusé : permission insuffisante.'];
        header('Location: ' . (str_contains($_SERVER['SCRIPT_NAME'], '/reports/') || str_contains($_SERVER['SCRIPT_NAME'], '/settings/') ? '../dashboard.php' : 'dashboard.php'));
        exit;
    }
}

function initAdminSession(PDO $pdo): void
{
    runCrmMigration($pdo);
    if (isset($_SESSION['admin_id']) && !isset($_SESSION['admin_permissions'])) {
        $_SESSION['admin_permissions'] = loadAdminPermissions($pdo, (int)$_SESSION['admin_id']);
    }
}

function getStoreSetting(PDO $pdo, string $key, ?string $default = null): ?string
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        try {
            $rows = $pdo->query("SELECT setting_key, setting_value FROM store_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
            $cache = $rows ?: [];
        } catch (PDOException $e) {
            $cache = [];
        }
    }
    return $cache[$key] ?? $default;
}

function setStoreSetting(PDO $pdo, string $key, string $value): void
{
    $stmt = $pdo->prepare("
        INSERT INTO store_settings (setting_key, setting_value) VALUES (?, ?)
        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
    ");
    $stmt->execute([$key, $value]);
}

function getSiteConfig(PDO $pdo, string $key): string
{
    $map = [
        'site_name'        => SITE_NAME,
        'site_tagline'     => SITE_TAGLINE,
        'site_description' => SITE_DESCRIPTION,
        'site_email'       => SITE_EMAIL,
        'site_phone'       => SITE_PHONE,
        'site_whatsapp'    => SITE_WHATSAPP,
        'site_address'     => SITE_ADDRESS,
        'site_promo_code'  => SITE_PROMO_CODE,
        'currency'         => 'FCFA',
        'tax_rate'         => '0',
        'low_stock_threshold' => '5',
    ];
    return getStoreSetting($pdo, $key, $map[$key] ?? '');
}

function formatMoney(float $amount): string
{
    return number_format($amount, 0, ',', ' ') . ' FCFA';
}

function reportDateFilter(): array
{
    $from = trim($_GET['date_from'] ?? date('Y-m-01'));
    $to   = trim($_GET['date_to'] ?? date('Y-m-d'));
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $from = date('Y-m-01');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) $to = date('Y-m-d');
    return [$from . ' 00:00:00', $to . ' 23:59:59', $from, $to];
}

function orderStatusLabel(string $status): string
{
    $map = [
        'pending' => 'En attente', 'paid' => 'Payée', 'shipped' => 'Expédiée',
        'delivered' => 'Livrée', 'cancelled' => 'Annulée',
        'Pending' => 'En attente', 'Processed' => 'Traitée', 'Shipped' => 'Expédiée', 'Delivered' => 'Livrée',
    ];
    return $map[$status] ?? ucfirst($status);
}

function isCancelledStatus(string $status): bool
{
    return in_array(strtolower($status), ['cancelled', 'annulée', 'annulee'], true);
}

function isRevenueStatus(string $status): bool
{
    if (isCancelledStatus($status)) return false;
    $s = strtolower($status);
    return in_array($s, ['paid', 'shipped', 'delivered', 'processed'], true);
}
