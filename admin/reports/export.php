<?php
session_start();
require_once '../../config.php';
require_once '../includes/auth.php';

initAdminSession($pdo);
requirePermission('reports.export');

[$dateFrom, $dateTo, $fromDate, $toDate] = reportDateFilter();
$type = $_GET['type'] ?? 'sales';

$filename = 'rapport_' . $type . '_' . $fromDate . '_' . $toDate . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$out = fopen('php://output', 'w');
fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8

switch ($type) {
    case 'customers':
        fputcsv($out, ['ID', 'Nom', 'Email', 'Téléphone', 'Inscription', 'Commandes', 'CA total', 'Dernière commande'], ';');
        $stmt = $pdo->prepare("
            SELECT u.id, u.name, u.email, u.phone, u.created_at,
                   COUNT(o.id) AS order_count,
                   COALESCE(SUM(o.total), 0) AS total_spent,
                   MAX(o.created_at) AS last_order
            FROM users u
            LEFT JOIN orders o ON o.user_id = u.id AND o.created_at BETWEEN ? AND ?
            GROUP BY u.id
            ORDER BY total_spent DESC
        ");
        $stmt->execute([$dateFrom, $dateTo]);
        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($out, [
                $r['id'], $r['name'], $r['email'], $r['phone'] ?? '',
                $r['created_at'], $r['order_count'], $r['total_spent'],
                $r['last_order'] ?? ''
            ], ';');
        }
        break;

    case 'products':
        fputcsv($out, ['ID', 'Nom', 'Catégorie', 'Prix', 'Stock', 'Quantité vendue', 'CA période'], ';');
        $stmt = $pdo->prepare("
            SELECT p.id, p.name, p.category, p.price, p.stock,
                   COALESCE(SUM(oi.quantity), 0) AS qty_sold,
                   COALESCE(SUM(oi.quantity * oi.price), 0) AS revenue
            FROM products p
            LEFT JOIN order_items oi ON oi.product_id = p.id
            LEFT JOIN orders o ON o.id = oi.order_id AND o.created_at BETWEEN ? AND ?
            GROUP BY p.id
            ORDER BY revenue DESC
        ");
        $stmt->execute([$dateFrom, $dateTo]);
        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($out, [
                $r['id'], $r['name'], $r['category'], $r['price'], $r['stock'],
                $r['qty_sold'], $r['revenue']
            ], ';');
        }
        break;

    case 'sales':
    default:
        fputcsv($out, ['ID', 'Client', 'Email', 'Téléphone', 'Total', 'Statut', 'Date'], ';');
        $stmt = $pdo->prepare("
            SELECT o.id, COALESCE(o.customer_name, u.name) AS client_name,
                   o.customer_email, o.customer_phone, o.total, o.status, o.created_at
            FROM orders o
            LEFT JOIN users u ON u.id = o.user_id
            WHERE o.created_at BETWEEN ? AND ?
            ORDER BY o.created_at DESC
        ");
        $stmt->execute([$dateFrom, $dateTo]);
        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($out, [
                $r['id'], $r['client_name'] ?? 'Anonyme', $r['customer_email'] ?? '',
                $r['customer_phone'] ?? '', $r['total'], orderStatusLabel($r['status']), $r['created_at']
            ], ';');
        }
        break;
}

fclose($out);
exit;
