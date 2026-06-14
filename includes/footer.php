</main>

<footer class="site-footer">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4">
                <a href="index.php" class="footer-brand text-decoration-none mb-3 d-inline-flex">
                    <span class="brand-icon"><i class="bi bi-shop"></i></span>
                    <?= htmlspecialchars(SITE_NAME) ?>
                </a>
                <p class="small pe-lg-4"><?= htmlspecialchars(SITE_DESCRIPTION_SHORT) ?></p>
            </div>
            <div class="col-6 col-lg-2">
                <h6 class="mb-3">Boutique</h6>
                <ul class="list-unstyled small">
                    <li class="mb-2"><a href="index.php">Accueil</a></li>
                    <li class="mb-2"><a href="index.php#products">Produits</a></li>
                    <li class="mb-2"><a href="cart.php">Panier</a></li>
                    <li class="mb-2"><a href="login.php">Connexion</a></li>
                </ul>
            </div>
            <div class="col-6 col-lg-2">
                <h6 class="mb-3">Aide</h6>
                <ul class="list-unstyled small">
                    <li class="mb-2"><a href="contact.php">Contact</a></li>
                    <li class="mb-2"><a href="#">Livraison</a></li>
                    <li class="mb-2"><a href="#">Retours</a></li>
                    <li class="mb-2"><a href="#">Confidentialité</a></li>
                </ul>
            </div>
            <div class="col-lg-4">
                <h6 class="mb-3">Newsletter</h6>
                <p class="small">Recevez nos offres exclusives</p>
                <form id="newsletterForm" class="newsletter-pro mb-3">
                    <input type="email" id="newsletterEmail" placeholder="Votre email" required>
                    <button type="submit"><i class="bi bi-send"></i></button>
                </form>
                <div class="social-pro">
                    <a href="#"><i class="bi bi-facebook"></i></a>
                    <a href="#"><i class="bi bi-instagram"></i></a>
                    <a href="https://wa.me/<?= SITE_WHATSAPP ?>"><i class="bi bi-whatsapp"></i></a>
                </div>
            </div>
        </div>
        <hr class="border-secondary my-4 opacity-25">
        <p class="text-center small mb-0 opacity-75">&copy; <?= date('Y') ?> <?= htmlspecialchars(SITE_NAME) ?> — Tous droits réservés</p>
    </div>
</footer>

<button id="scrollTopBtn" aria-label="Retour en haut"><i class="bi bi-arrow-up"></i></button>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/app.js"></script>
<script>
document.getElementById('newsletterForm')?.addEventListener('submit', e => {
    e.preventDefault();
    alert('Merci pour votre inscription : ' + document.getElementById('newsletterEmail').value);
    e.target.reset();
});
const scrollBtn = document.getElementById('scrollTopBtn');
window.addEventListener('scroll', () => {
    if (scrollBtn) scrollBtn.style.display = window.scrollY > 300 ? 'flex' : 'none';
});
scrollBtn?.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
</script>
</body>
</html>
