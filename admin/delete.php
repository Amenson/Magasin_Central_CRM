<?php
session_start();
require_once '../config.php';
require_once 'includes/auth.php';

initAdminSession($pdo);
requirePermission('products');

// Vérification de l'ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['flash'] = [
        'type' => 'danger',
        'message' => 'ID du produit invalide.'
    ];
    header('Location: dashboard.php');
    exit;
}

$id = (int)$_GET['id'];

// Récupérer le produit pour obtenir le chemin de l'image
$stmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    $_SESSION['flash'] = [
        'type' => 'danger',
        'message' => 'Produit non trouvé.'
    ];
    header('Location: dashboard.php');
    exit;
}

// Suppression de l'image physique (si elle existe et n'est pas le placeholder)
$imagePath = '../' . ($product['image'] ?? '');
$placeholder = '../uploads/placeholder.jpg';

if ($product['image'] && file_exists($imagePath) && $imagePath !== $placeholder) {
    @unlink($imagePath); // @ pour éviter les warnings si échec
}

// Suppression en base de données
$stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
if ($stmt->execute([$id])) {
    $_SESSION['flash'] = [
        'type' => 'success',
        'message' => 'Produit supprimé avec succès !'
    ];
} else {
    $_SESSION['flash'] = [
        'type' => 'danger',
        'message' => 'Erreur lors de la suppression du produit.'
    ];
}

header('Location: dashboard.php');
exit;
?>