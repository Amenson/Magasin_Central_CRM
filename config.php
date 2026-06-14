<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); // Change to your password
define('DB_NAME', 'ecommerce');

// Identité du magasin
define('SITE_NAME', 'Magasin Central');
define('SITE_TAGLINE', 'Le magasin de quartier, accessible partout');
define('SITE_DESCRIPTION', 'Magasin Central, c\'est la boutique de votre quartier en version digitale. Électronique, mode, maison et loisirs — commandez en ligne, payez par Mobile Money et recevez chez vous partout au Togo.');
define('SITE_DESCRIPTION_SHORT', 'Magasin Central, votre boutique de confiance au Togo. Commandez en ligne, payez par Mobile Money et recevez rapidement chez vous.');
define('SITE_KEYWORDS', 'magasin en ligne, boutique Togo, e-commerce, livraison, Mobile Money, Flooz, TMoney');
define('SITE_EMAIL', 'contact@magasincentral.tg');
define('SITE_PHONE', '+228 93 81 46 45');
define('SITE_WHATSAPP', '22893814645');
define('SITE_ADDRESS', '123 Rue Tech, Lomé, Togo');
define('SITE_PROMO_CODE', 'BIENVENUE15');
define('SITE_HERO_TITLE', 'Bienvenue chez Magasin Central');
define('SITE_HERO_TEXT', 'Électronique, mode, maison et loisirs — des produits de qualité, livrés partout au Togo avec paiement Flooz & TMoney.');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>