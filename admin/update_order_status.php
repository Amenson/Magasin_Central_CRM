<?php
// update_order_status.php

session_start();
require_once '../config.php';
require_once 'includes/auth.php';

initAdminSession($pdo);
if (!adminHasPermission('orders')) {
    header('Content-Type: application/json', true, 403);
    echo json_encode(['success' => false, 'message' => 'Accès refusé : permission insuffisante']);
    exit;
}

header('Content-Type: application/json');

// Lecture du JSON
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE || !isset($data['order_id']) || !isset($data['new_status'])) {
    echo json_encode(['success' => false, 'message' => 'Données invalides ou manquantes']);
    exit;
}

$orderId   = (int)$data['order_id'];
$newStatus = trim($data['new_status']);

$validStatuses = ['pending', 'paid', 'shipped', 'cancelled'];
if (!in_array($newStatus, $validStatuses)) {
    echo json_encode(['success' => false, 'message' => 'Statut non autorisé']);
    exit;
}

try {
    // Vérifier que la commande existe
    $checkStmt = $pdo->prepare("SELECT id FROM orders WHERE id = ?");
    $checkStmt->execute([$orderId]);
    if ($checkStmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'Commande non trouvée']);
        exit;
    }

    // Mise à jour simple sans updated_at
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$newStatus, $orderId]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Statut mis à jour avec succès']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Aucun changement (statut déjà identique)']);
    }
} catch (PDOException $e) {
    // Log détaillé pour vous (admin)
    error_log('Erreur DB update_order_status (order #' . $orderId . ') : ' . $e->getMessage());

    // Message générique pour le frontend
    echo json_encode(['success' => false, 'message' => 'Erreur technique base de données. Consultez les logs.']);
}
?>