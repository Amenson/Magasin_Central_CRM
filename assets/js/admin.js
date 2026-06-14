// assets/js/admin.js
// JavaScript complet pour le panel admin Magasin Central
// Fonctionnalités : Recherche produits, filtre commandes, mise à jour statut AJAX, tri tableaux, toasts

document.addEventListener('DOMContentLoaded', function () {

    /* ========================================
       1. Toast Notification (fonction globale admin)
    ======================================== */
    window.showToast = function (message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `alert alert-${type === 'success' ? 'success' : type === 'warning' ? 'warning' : 'danger'} toast-notification shadow-lg`;
        toast.innerHTML = `
            <strong>${type === 'success' ? '✓' : type === 'warning' ? '⚠' : '✗'}</strong> ${message}
            <button type="button" class="btn-close float-end" data-bs-dismiss="alert"></button>
        `;
        document.getElementById('toastContainer')?.appendChild(toast) || document.body.appendChild(toast);

        setTimeout(() => toast.classList.add('show'), 100);
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 500);
        }, 5000);
    };

    /* ========================================
       2. Recherche instantanée produits
    ======================================== */
    const searchProductInput = document.getElementById('searchProduct');
    if (searchProductInput) {
        searchProductInput.addEventListener('keyup', function () {
            const filter = this.value.toLowerCase();
            document.querySelectorAll('#productsTable tbody tr').forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        });
    }

    /* ========================================
       3. Filtre par statut commandes
    ======================================== */
    const statusFilter = document.getElementById('statusFilter');
    if (statusFilter) {
        statusFilter.addEventListener('change', function () {
            const status = this.value.toLowerCase();
            document.querySelectorAll('#ordersTable tbody tr').forEach(row => {
                const rowStatus = row.getAttribute('data-status');
                row.style.display = (status === 'all' || rowStatus === status) ? '' : 'none';
            });
        });
    }

    /* ========================================
       4. Mise à jour statut commande via AJAX
    ======================================== */
    document.querySelectorAll('.status-select').forEach(select => {
        select.addEventListener('change', function () {
            const orderId = this.dataset.orderId;
            const newStatus = this.value;
            const oldStatus = this.dataset.current;
            const row = this.closest('tr');

            fetch(window.location.href, {  // Utilise l'URL actuelle (dashboard.php)
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=update_status&order_id=${orderId}&new_status=${newStatus}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Mise à jour visuelle
                    row.setAttribute('data-status', newStatus);
                    this.className = `form-select form-select-sm badge-status status-${newStatus} status-select`;
                    this.dataset.current = newStatus;

                    showToast(`Statut de la commande #${orderId} mis à jour : ${this.options[this.selectedIndex].text}`, 'success');
                } else {
                    showToast('Erreur lors de la mise à jour du statut.', 'danger');
                    this.value = oldStatus; // Revenir à l'ancien
                }
            })
            .catch(error => {
                console.error('Erreur AJAX :', error);
                showToast('Erreur de connexion serveur.', 'danger');
                this.value = oldStatus;
            });
        });
    });

    /* ========================================
       5. Tri des colonnes de tableaux (produits & commandes)
    ======================================== */
    document.querySelectorAll('table th').forEach((th, index) => {
        th.style.cursor = 'pointer';
        th.addEventListener('click', () => {
            const table = th.closest('table');
            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));

            // Toggle direction
            const isAsc = th.classList.toggle('asc');
            th.classList.toggle('desc', !isAsc);

            rows.sort((a, b) => {
                let aText = a.children[index].textContent.trim();
                let bText = b.children[index].textContent.trim();

                // Gestion prix/stock (supprime espaces et CFA)
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
       6. Prévisualisation image (add/edit product)
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
});