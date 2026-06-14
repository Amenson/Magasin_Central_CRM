document.addEventListener('DOMContentLoaded', function () {

    /* ========================================
       1. FAQ Accordion (une seule ouverte)
    ======================================== */
    const faqQuestions = document.querySelectorAll('.faq-question');
    faqQuestions.forEach(item => {
        item.addEventListener('click', () => {
            const answer = item.nextElementSibling;
            const isActive = item.classList.contains('active');

            // Fermer toutes
            faqQuestions.forEach(q => {
                q.classList.remove('active');
                q.nextElementSibling.classList.remove('show');
            });

            // Ouvrir celle-ci si pas déjà active
            if (!isActive) {
                item.classList.add('active');
                answer.classList.add('show');
            }
        });
    });

    /* ========================================
       2. Quantité +/- (page produit)
    ======================================== */
    const quantityInput = document.getElementById('quantity');
    if (quantityInput) {
        const decrement = document.getElementById('decrement');
        const increment = document.getElementById('increment');
        const maxStock = parseInt(quantityInput.max);

        increment.addEventListener('click', () => {
            let val = parseInt(quantityInput.value);
            if (val < maxStock) quantityInput.value = val + 1;
        });

        decrement.addEventListener('click', () => {
            let val = parseInt(quantityInput.value);
            if (val > 1) quantityInput.value = val - 1;
        });
    }

    /* ========================================
       3. Zoom image produit au survol
    ======================================== */
    const productImageContainer = document.querySelector('.product-image');
    const mainImage = document.getElementById('mainProductImage');

    if (productImageContainer && mainImage) {
        productImageContainer.addEventListener('mousemove', (e) => {
            const rect = productImageContainer.getBoundingClientRect();
            const x = (e.clientX - rect.left) / rect.width * 100;
            const y = (e.clientY - rect.top) / rect.height * 100;
            mainImage.style.transformOrigin = `${x}% ${y}%`;
        });

        productImageContainer.addEventListener('mouseenter', () => {
            mainImage.style.transform = 'scale(2)';
            productImageContainer.style.cursor = 'zoom-in';
        });

        productImageContainer.addEventListener('mouseleave', () => {
            mainImage.style.transform = 'scale(1)';
        });
    }

    /* ========================================
       4. Prévisualisation image upload (admin)
    ======================================== */
    const imageInput = document.getElementById('imageInput');
    if (imageInput) {
        imageInput.addEventListener('change', function (e) {
            const file = e.target.files[0];
            const preview = document.getElementById('imagePreview');
            if (file) {
                const reader = new FileReader();
                reader.onload = (ev) => {
                    preview.src = ev.target.result;
                    preview.classList.remove('d-none');
                };
                reader.readAsDataURL(file);
            } else {
                preview.classList.add('d-none');
            }
        });
    }

    /* ========================================
       5. Toast Notification globale
    ======================================== */
    window.showToast = function (message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `alert alert-${type === 'success' ? 'success' : 'danger'} toast-notification`;
        toast.innerHTML = `
            <strong>${type === 'success' ? '✓' : '✗'}</strong> ${message}
            <button type="button" class="btn-close float-end" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(toast);

        setTimeout(() => toast.classList.add('show'), 100);
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 500);
        }, 5000);
    };

    /* ========================================
       6. Mise à jour badge panier (exemple si AJAX)
    ======================================== */
    function updateCartBadge(count) {
        const badge = document.querySelector('.cart-badge');
        if (badge) {
            badge.textContent = count;
            badge.style.display = count > 0 ? 'block' : 'none';
        }
    }

    // Appel initial (à adapter selon votre header)
    // updateCartBadge(<?php echo array_sum($_SESSION['cart'] ?? []) ?>);

    /* ========================================
       7. Lazy loading images (si pas déjà via Bootstrap)
    ======================================== */
    const lazyImages = document.querySelectorAll('img[loading="lazy"]');
    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src || img.src;
                    observer.unobserve(img);
                }
            });
        });
        lazyImages.forEach(img => observer.observe(img));
    }
});

// assets/js/app.js
// JavaScript global complet pour gérer tout le site Magasin Central
// Fonctionnalités : FAQ, quantité +/-, zoom image, prévisualisation upload, toasts, lazy loading, etc.

document.addEventListener('DOMContentLoaded', function () {

    /* ========================================
       1. Toast Notification (fonction globale)
    ======================================== */
    window.showToast = function (message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `alert alert-${type === 'success' ? 'success' : type === 'warning' ? 'warning' : 'danger'} toast-notification shadow`;
        toast.innerHTML = `
            <strong>${type === 'success' ? '✓' : type === 'warning' ? '⚠' : '✗'}</strong> ${message}
            <button type="button" class="btn-close float-end" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(toast);

        // Animation d'apparition
        setTimeout(() => toast.classList.add('show'), 100);

        // Auto-disparition après 5 secondes
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 500);
        }, 5000);
    };

    /* ========================================
       2. FAQ Accordion (une seule section ouverte)
    ======================================== */
    const faqQuestions = document.querySelectorAll('.faq-question');
    faqQuestions.forEach(item => {
        item.addEventListener('click', () => {
            const answer = item.nextElementSibling;
            const isActive = item.classList.contains('active');

            // Fermer toutes les sections
            faqQuestions.forEach(q => {
                q.classList.remove('active');
                q.nextElementSibling.classList.remove('show');
            });

            // Ouvrir la section cliquée si elle n'était pas active
            if (!isActive) {
                item.classList.add('active');
                answer.classList.add('show');
            }
        });
    });

    /* ========================================
       3. Boutons Quantité +/- (page produit & panier)
    ======================================== */
    document.querySelectorAll('.quantity-btn').forEach(button => {
        button.addEventListener('click', function (e) {
            e.preventDefault();
            const input = this.closest('.input-group').querySelector('input[type="number"], input[type="text"]');
            const form = this.closest('form');
            let value = parseInt(input.value);

            if (this.classList.contains('increment')) {
                if (value < parseInt(input.max || 999)) value++;
            } else if (this.classList.contains('decrement')) {
                if (value > parseInt(input.min || 1)) value--;
            }

            input.value = value;

            // Soumission automatique du formulaire si présent (panier)
            if (form) form.submit();
        });
    });

    /* ========================================
       4. Zoom sur image produit au survol
    ======================================== */
    const productImageContainer = document.querySelector('.product-image');
    const mainImage = document.getElementById('mainProductImage');

    if (productImageContainer && mainImage) {
        productImageContainer.addEventListener('mousemove', (e) => {
            const rect = productImageContainer.getBoundingClientRect();
            const x = ((e.clientX - rect.left) / rect.width) * 100;
            const y = ((e.clientY - rect.top) / rect.height) * 100;
            mainImage.style.transformOrigin = `${x}% ${y}%`;
        });

        productImageContainer.addEventListener('mouseenter', () => {
            mainImage.style.transform = 'scale(2)';
            productImageContainer.style.cursor = 'zoom-in';
        });

        productImageContainer.addEventListener('mouseleave', () => {
            mainImage.style.transform = 'scale(1)';
        });
    }

    /* ========================================
       5. Prévisualisation image lors de l'upload (admin)
    ======================================== */
    const imageInput = document.getElementById('imageInput');
    if (imageInput) {
        imageInput.addEventListener('change', function (e) {
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
    }

    /* ========================================
       6. Recherche instantanée (produits & admin)
    ======================================== */
    const searchInputs = document.querySelectorAll('input[type="search"], #searchProduct');
    searchInputs.forEach(input => {
        input.addEventListener('keyup', function () {
            const filter = this.value.toLowerCase();
            const table = this.closest('.card')?.querySelector('table') || document.querySelector('#productsTable');
            if (!table) return;

            table.querySelectorAll('tbody tr').forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        });
    });

    /* ========================================
       7. Tri des colonnes de tableau (admin dashboard)
    ======================================== */
    document.querySelectorAll('th.sortable').forEach((th, index) => {
        th.style.cursor = 'pointer';
        th.addEventListener('click', () => {
            const table = th.closest('table');
            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));

            const isAsc = th.classList.toggle('asc');
            if (!isAsc) th.classList.add('desc');
            else th.classList.remove('desc');

            rows.sort((a, b) => {
                const aText = a.children[index].textContent.trim();
                const bText = b.children[index].textContent.trim();

                const aNum = parseFloat(aText.replace(/[^0-9.-]+/g, ''));
                const bNum = parseFloat(bText.replace(/[^0-9.-]+/g, ''));

                if (!isNaN(aNum) && !isNaN(bNum)) {
                    return isAsc ? aNum - bNum : bNum - aNum;
                }
                return isAsc ? aText.localeCompare(bText) : bText.localeCompare(aText);
            });

            rows.forEach(row => tbody.appendChild(row));
        });
    });

    /* ========================================
       8. Lazy loading images (amélioré)
    ======================================== */
    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src || img.src;
                    img.classList.remove('lazy');
                    observer.unobserve(img);
                }
            });
        }, { rootMargin: '50px' });

        document.querySelectorAll('img.lazy, img[loading="lazy"]').forEach(img => {
            observer.observe(img);
        });
    }

     });