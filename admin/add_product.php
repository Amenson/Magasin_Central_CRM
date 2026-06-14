<?php
session_start();
require_once '../config.php';
require_once 'includes/auth.php';
require_once 'includes/layout.php';

initAdminSession($pdo);
requirePermission('products');

$success = '';
$error   = '';

// Catégories disponibles (ajoutez/modifiez selon vos besoins)
$categories = [
    'electronique' => 'Électronique',
    'mode'         => 'Mode',
    'maison'       => 'Maison',
    'loisirs'      => 'Loisirs',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    // Récupération et nettoyage des données
    $name        = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price       = floatval($_POST['price'] ?? 0);
    $stock       = intval($_POST['stock'] ?? 0);
    $category    = $_POST['category'] ?? '';
    $imagePath   = '';

    // Validation des champs obligatoires
    $errors = [];
    if (empty($name)) $errors[] = 'Le nom du produit est obligatoire.';
    if (empty($description)) $errors[] = 'La description est obligatoire.';
    if ($price <= 0) $errors[] = 'Le prix doit être supérieur à 0.';
    if ($stock < 0) $errors[] = 'Le stock ne peut pas être négatif.';
    if (!array_key_exists($category, $categories)) $errors[] = 'Catégorie invalide.';

    // Gestion de l'upload d'image
    if (empty($errors)) {
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath   = $_FILES['image']['tmp_name'];
            $fileName      = $_FILES['image']['name'];
            $fileSize      = $_FILES['image']['size'];
            $fileExt       = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            $allowedExt    = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $maxSize       = 5 * 1024 * 1024; // 5 Mo

            if (!in_array($fileExt, $allowedExt)) {
                $errors[] = 'Format d\'image non autorisé (JPG, JPEG, PNG, GIF, WEBP uniquement).';
            } elseif ($fileSize > $maxSize) {
                $errors[] = 'L\'image dépasse la taille maximale autorisée (5 Mo).';
            } else {
                // Nom unique + dossier d'upload
                $newFileName = uniqid('prod_', true) . '.' . $fileExt;
                $uploadDir   = '../uploads/products/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $destPath = $uploadDir . $newFileName;

                if (move_uploaded_file($fileTmpPath, $destPath)) {
                    $imagePath = 'uploads/products/' . $newFileName; // Chemin relatif en base
                } else {
                    $errors[] = 'Erreur lors de l\'enregistrement de l\'image.';
                }
            }
        } else {
            // Image facultative : placeholder par défaut
            $imagePath = 'uploads/placeholder.jpg'; // Créez ce fichier placeholder
        }
    }

    // Insertion en base si aucune erreur
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO products 
                (name, description, price, stock, category, image, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$name, $description, $price, $stock, $category, $imagePath]);

            $success = 'Produit ajouté avec succès !';
            // Réinitialiser le formulaire
            $_POST = [];
        } catch (PDOException $e) {
            $errors[] = 'Erreur base de données : ' . $e->getMessage();
        }
    }

    if (!empty($errors)) {
        $error = implode('<br>', $errors);
    }
}

adminLayoutStart('Ajouter un produit', 'products');
?>

<?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

<div class="pro-card">
    <div class="card-header-pro"><i class="bi bi-plus-circle me-2"></i> Nouveau produit</div>
    <div class="card-body">
        <form method="POST" enctype="multipart/form-data">
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label fw-bold">Nom *</label><input type="text" name="name" class="form-control" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required></div>
                <div class="col-md-6"><label class="form-label fw-bold">Catégorie *</label><select name="category" class="form-select" required><option value="">Choisir...</option><?php foreach ($categories as $slug => $nom): ?><option value="<?= $slug ?>" <?= (($_POST['category'] ?? '') === $slug) ? 'selected' : '' ?>><?= htmlspecialchars($nom) ?></option><?php endforeach; ?></select></div>
                <div class="col-md-6"><label class="form-label fw-bold">Prix (CFA) *</label><input type="number" step="0.01" min="0.01" name="price" class="form-control" value="<?= $_POST['price'] ?? '' ?>" required></div>
                <div class="col-md-6"><label class="form-label fw-bold">Stock *</label><input type="number" min="0" name="stock" class="form-control" value="<?= $_POST['stock'] ?? '100' ?>" required></div>
                <div class="col-12"><label class="form-label fw-bold">Description *</label><textarea name="description" rows="5" class="form-control" required><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea></div>
                <div class="col-12"><label class="form-label fw-bold">Image</label><input type="file" name="image" id="imageInput" class="form-control" accept="image/*"><div class="mt-3 text-center"><img id="imagePreview" class="preview-img d-none" alt="Preview"></div></div>
                <div class="col-12"><button type="submit" name="add" class="btn btn-primary"><i class="bi bi-check-circle"></i> Ajouter</button><a href="dashboard.php" class="btn btn-secondary ms-2">Annuler</a></div>
            </div>
        </form>
    </div>
</div>
<script>document.getElementById('imageInput')?.addEventListener('change',function(e){const f=e.target.files[0],p=document.getElementById('imagePreview');if(f){const r=new FileReader();r.onload=ev=>{p.src=ev.target.result;p.classList.remove('d-none')};r.readAsDataURL(f)}});</script>
<?php adminLayoutEnd(); ?>