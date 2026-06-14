<?php
session_start();
require_once '../config.php';
require_once 'includes/auth.php';
require_once 'includes/layout.php';

initAdminSession($pdo);
requirePermission('products');

$success = '';
$error = '';
$product = null;
$id = 0;

$categories = [
    'electronique' => 'Electronique',
    'mode' => 'Mode',
    'maison' => 'Maison',
    'loisirs' => 'Loisirs',
];

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $error = 'ID du produit invalide.';
} else {
    $id = (int)$_GET['id'];

    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        $error = 'Produit non trouve.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit']) && $product) {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);
    $category = $_POST['category'] ?? '';

    $errors = [];
    if ($name === '') $errors[] = 'Le nom est obligatoire.';
    if ($description === '') $errors[] = 'La description est obligatoire.';
    if ($price <= 0) $errors[] = 'Le prix doit etre superieur a 0.';
    if ($stock < 0) $errors[] = 'Le stock ne peut pas etre negatif.';
    if (!array_key_exists($category, $categories)) $errors[] = 'Selectionnez une categorie valide.';

    $imagePath = $product['image'];

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['image']['tmp_name'];
        $fileName = $_FILES['image']['name'];
        $fileSize = $_FILES['image']['size'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $maxSize = 5 * 1024 * 1024;

        if (!in_array($fileExt, $allowedExt, true)) {
            $errors[] = 'Format d\'image non autorise (JPG, PNG, GIF, WEBP uniquement).';
        } elseif ($fileSize > $maxSize) {
            $errors[] = 'L\'image depasse 5 Mo.';
        } else {
            $oldImage = '../' . $product['image'];
            $placeholder = '../uploads/placeholder.jpg';
            if ($product['image'] && file_exists($oldImage) && $oldImage !== $placeholder) {
                @unlink($oldImage);
            }

            $newFileName = uniqid('prod_', true) . '.' . $fileExt;
            $uploadDir = '../uploads/products/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $destPath = $uploadDir . $newFileName;
            if (move_uploaded_file($fileTmpPath, $destPath)) {
                $imagePath = 'uploads/products/' . $newFileName;
            } else {
                $errors[] = 'Erreur lors de l\'enregistrement de l\'image.';
            }
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE products
                SET name = ?, description = ?, price = ?, stock = ?, category = ?, image = ?
                WHERE id = ?
            ");
            $stmt->execute([$name, $description, $price, $stock, $category, $imagePath, $id]);

            $success = 'Produit modifie avec succes !';
            $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
            $stmt->execute([$id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $errors[] = 'Erreur base de donnees.';
            error_log($e->getMessage());
        }
    }

    if (!empty($errors)) {
        $error = implode('<br>', $errors);
    }
}

adminLayoutStart('Modifier le produit' . ($id ? " #$id" : ''), 'products');
?>

<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?= htmlspecialchars($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?= $error ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="pro-card">
    <div class="card-header-pro"><i class="bi bi-pencil-square me-2"></i> Produit #<?= $id ?: '-' ?></div>
    <div class="card-body">
        <?php if ($product): ?>
        <form method="POST" enctype="multipart/form-data">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Nom du produit <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($product['name']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Categorie <span class="text-danger">*</span></label>
                    <select name="category" class="form-select" required>
                        <option value="">Choisir...</option>
                        <?php foreach ($categories as $slug => $nom): ?>
                            <option value="<?= $slug ?>" <?= ($product['category'] ?? '') === $slug ? 'selected' : '' ?>>
                                <?= htmlspecialchars($nom) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Prix (CFA) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0.01" name="price" class="form-control" value="<?= htmlspecialchars((string)$product['price']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Stock <span class="text-danger">*</span></label>
                    <input type="number" min="0" name="stock" class="form-control" value="<?= htmlspecialchars((string)$product['stock']) ?>" required>
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold">Description <span class="text-danger">*</span></label>
                    <textarea name="description" rows="6" class="form-control" required><?= htmlspecialchars($product['description']) ?></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold">Image actuelle</label>
                    <div class="text-center mb-3">
                        <img src="../<?= htmlspecialchars($product['image'] ?? 'uploads/placeholder.jpg') ?>" class="preview-img" alt="Image actuelle">
                    </div>
                    <label class="form-label fw-bold">Nouvelle image (optionnel, max 5 Mo)</label>
                    <input type="file" name="image" id="imageInput" class="form-control" accept="image/*">
                    <small class="text-muted">Formats : JPG, PNG, GIF, WEBP</small>
                    <div class="mt-3 text-center">
                        <img id="imagePreview" class="preview-img d-none" alt="Previsualisation">
                    </div>
                </div>
                <div class="col-12 d-flex flex-wrap gap-2">
                    <button type="submit" name="edit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Enregistrer
                    </button>
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Retour
                    </a>
                </div>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>

<script>
document.getElementById('imageInput')?.addEventListener('change', function(e) {
    const file = e.target.files[0];
    const preview = document.getElementById('imagePreview');
    if (file && preview) {
        const reader = new FileReader();
        reader.onload = (ev) => {
            preview.src = ev.target.result;
            preview.classList.remove('d-none');
        };
        reader.readAsDataURL(file);
    } else if (preview) {
        preview.classList.add('d-none');
    }
});
</script>
<?php adminLayoutEnd(); ?>
