// assets/js/main.js

document.addEventListener('DOMContentLoaded', function() {

    // Gestion des notifications toast (utilise Bootstrap 5)
    function showToast(message, type = 'success') {
        const container = document.querySelector('.toast-container') || document.body;
        
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-bg-${type} border-0 shadow-lg mb-3`;
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body fw-medium">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;
        
        container.appendChild(toast);
        const bsToast = new bootstrap.Toast(toast, { delay: 4000 });
        bsToast.show();
        
        setTimeout(() => toast.remove(), 4500);
    }

    // Ajout au panier via boutons (AJAX-like simple)
    document.querySelectorAll('.add-to-cart-btn').forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.dataset.productId;
            const quantityInput = document.querySelector(`input.quantity[data-product-id="${productId}"]`);
            const quantity = quantityInput ? parseInt(quantityInput.value) : 1;

            fetch('cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=add&product_id=${productId}&quantity=${quantity}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message || 'Produit ajouté au panier !', 'success');
                    // Mettre à jour le compteur panier dans la navbar
                    const cartCount = document.querySelector('.cart-count');
                    if (cartCount) {
                        let current = parseInt(cartCount.textContent) || 0;
                        cartCount.textContent = current + quantity;
                    }
                } else {
                    showToast(data.message || 'Erreur lors de l\'ajout', 'danger');
                }
            })
            .catch(() => showToast('Erreur réseau', 'danger'));
        });
    });

    // Gestion quantité sur page produit
    const qtyInput = document.querySelector('input.quantity');
    const qtyButtons = document.querySelectorAll('.qty-btn');
    
    if (qtyButtons.length && qtyInput) {
        qtyButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                let val = parseInt(qtyInput.value);
                const min = parseInt(qtyInput.min) || 1;
                const max = parseInt(qtyInput.max) || 999;
                
                if (this.dataset.action === 'minus' && val > min) {
                    qtyInput.value = val - 1;
                }
                if (this.dataset.action === 'plus' && val < max) {
                    qtyInput.value = val + 1;
                }
            });
        });
    }
});