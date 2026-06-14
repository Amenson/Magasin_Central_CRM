'use strict';

// modal variables
const modal = document.querySelector('[data-modal]');
const modalCloseBtn = document.querySelector('[data-modal-close]');
const modalCloseOverlay = document.querySelector('[data-modal-overlay]');

// modal function
const modalCloseFunc = function () { modal.classList.add('closed') }

// modal eventListener
modalCloseOverlay.addEventListener('click', modalCloseFunc);
modalCloseBtn.addEventListener('click', modalCloseFunc);





// notification toast variables
const notificationToast = document.querySelector('[data-toast]');
const toastCloseBtn = document.querySelector('[data-toast-close]');

// notification toast eventListener
toastCloseBtn.addEventListener('click', function () {
  notificationToast.classList.add('closed');
});





// mobile menu variables
const mobileMenuOpenBtn = document.querySelectorAll('[data-mobile-menu-open-btn]');
const mobileMenu = document.querySelectorAll('[data-mobile-menu]');
const mobileMenuCloseBtn = document.querySelectorAll('[data-mobile-menu-close-btn]');
const overlay = document.querySelector('[data-overlay]');

for (let i = 0; i < mobileMenuOpenBtn.length; i++) {

  // mobile menu function
  const mobileMenuCloseFunc = function () {
    mobileMenu[i].classList.remove('active');
    overlay.classList.remove('active');
  }

  mobileMenuOpenBtn[i].addEventListener('click', function () {
    mobileMenu[i].classList.add('active');
    overlay.classList.add('active');
  });

  mobileMenuCloseBtn[i].addEventListener('click', mobileMenuCloseFunc);
  overlay.addEventListener('click', mobileMenuCloseFunc);

}





// accordion variables
const accordionBtn = document.querySelectorAll('[data-accordion-btn]');
const accordion = document.querySelectorAll('[data-accordion]');

for (let i = 0; i < accordionBtn.length; i++) {

  accordionBtn[i].addEventListener('click', function () {

    const clickedBtn = this.nextElementSibling.classList.contains('active');

    for (let i = 0; i < accordion.length; i++) {

      if (clickedBtn) break;

      if (accordion[i].classList.contains('active')) {

        accordion[i].classList.remove('active');
        accordionBtn[i].classList.remove('active');

      }

    }

    this.nextElementSibling.classList.toggle('active');
    this.classList.toggle('active');

  });

}


     
        // Gestion des clics sur les boutons de la section
        document.querySelectorAll('.step').forEach(step => {
            step.addEventListener('click', () => {
                const stepId = step.getAttribute('data-step');
                showSection(stepId);
            });
        });
   
        
        // Gestion des clics sur le bouton "Retour"
        document.querySelectorAll('.back-button').forEach(button => {
            button.addEventListener('click', () => {
                const sectionId = button.getAttribute('data-section-id');
                showSection(sectionId);
            });
        });
   

        // Gestion des clics sur la navigation
        document.querySelectorAll('nav a').forEach(link => {
            link.addEventListener('click', (e) => {
                const href = link.getAttribute('href');
                if (href.startsWith('#')) {
                    e.preventDefault();
                    const sectionId = href.substring(1);
                    showSection(sectionId);
                    if (sectionId !== 'order') {
                        resetOrder();
                    }
                }
            });

        });

        // Gestion des clics sur les liens du footer
        document.querySelectorAll('footer a').forEach(link => {
            link.addEventListener('click', (e) => {
                const href = link.getAttribute('href');
                if (href.startsWith('#')) {
                    e.preventDefault();
                    const sectionId = href.substring(1);
                    showSection(sectionId);
                    if (sectionId !== 'order') {
                        resetOrder();
                    }
                }
            });
        });


               // Vérifier si l'utilisateur est connecté
        fetch('check_session.php')
            .then(response => response.json())
            .then(data => {
                if (data.loggedIn) {
                    document.getElementById('login-link').parentElement.style.display = 'none';
                    document.getElementById('signup-link').parentElement.style.display = 'none';
                    document.getElementById('user-link').style.display = 'block';
                    document.getElementById('user-name').textContent = data.username;
                }
            });

        // Afficher une modale
        function showModal(type) {
            document.getElementById(`${type}-modal`).style.display = 'flex';
        }

        // Fermer une modale
        function closeModal(type) {
            document.getElementById(`${type}-modal`).style.display = 'none';
            document.getElementById(`${type}-error`).style.display = 'none';
            document.getElementById(`${type}-form`).reset();
        }

        // Gestion des erreurs via URL
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('error')) {
            const error = urlParams.get('error');
            if (urlParams.get('type') === 'login') {
                document.getElementById('login-error').textContent = error;
                document.getElementById('login-error').style.display = 'block';
                showModal('login');
            } else if (urlParams.get('type') === 'signup') {
                document.getElementById('signup-error').textContent = error;
                document.getElementById('signup-error').style.display = 'block';
                showModal('signup');
            }
            window.history.replaceState({}, document.title, window.location.pathname);
        }

        // Fermer les modales en cliquant à l'extérieur
        window.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal')) {
                closeModal('login');
                closeModal('signup');
            }
        });


        document.addEventListener('DOMContentLoaded', () => {
    const categoryFilter = document.getElementById('category-filter');
    const priceFilter = document.getElementById('price-filter');
    const brandFilter = document.getElementById('brand-filter');
    const stockFilter = document.getElementById('stock-filter');
    const productGrid = document.getElementById('product-grid');
    const productCards = Array.from(productGrid.getElementsByClassName('product-card'));

    // Gestion du panier
    let cart = JSON.parse(localStorage.getItem('cart')) || [];

    // Sauvegarder le panier dans localStorage
    function saveCart() {
        localStorage.setItem('cart', JSON.stringify(cart));
    }

    // Ajouter un produit au panier
    window.addToCart = function(productName, price) {
        const existingItem = cart.find(item => item.name === productName);
        if (existingItem) {
            existingItem.quantity += 1;
        } else {
            cart.push({ name: productName, price: price, quantity: 1 });
        }
        saveCart();
        updateCartDisplay();
        alert(`${productName} a été ajouté au panier !`);
    };

    // Supprimer un produit du panier
    function removeFromCart(productName) {
        cart = cart.filter(item => item.name !== productName);
        saveCart();
        updateCartDisplay();
    }

    // Mettre à jour l'affichage du panier
    function updateCartDisplay() {
        const cartContainer = document.createElement('div');
        cartContainer.id = 'cart-container';
        cartContainer.style.position = 'fixed';
        cartContainer.style.top = '10px';
        cartContainer.style.right = '10px';
        cartContainer.style.background = '#fff';
        cartContainer.style.border = '1px solid #ccc';
        cartContainer.style.padding = '10px';
        cartContainer.style.maxWidth = '300px';
        cartContainer.style.zIndex = '1000';

        if (cart.length === 0) {
            cartContainer.innerHTML = '<p>Panier vide</p>';
        } else {
            let total = 0;
            let cartHTML = '<h3>Panier</h3><ul>';
            cart.forEach(item => {
                const itemTotal = item.price * item.quantity;
                total += itemTotal;
                cartHTML += `
                    <li>
                        ${item.name} x${item.quantity} - ${itemTotal}€
                        <button onclick="removeFromCart('${item.name}')">Supprimer</button>
                    </li>
                `;
            });
            cartHTML += `</ul><p>Total: ${total}CFA</p>`;
            cartContainer.innerHTML = cartHTML;
        }

        const existingCart = document.getElementById('cart-container');
        if (existingCart) {
            existingCart.remove();
        }
        document.body.appendChild(cartContainer);
    }

    // Fonction pour filtrer et trier les produits
    function updateProductDisplay() {
        let filteredProducts = [...productCards];

        // Filtre par catégorie
        const selectedCategory = categoryFilter.value;
        if (selectedCategory !== 'all') {
            filteredProducts = filteredProducts.filter(card =>
                card.dataset.category === selectedCategory
            );
        }

        // Filtre par marque
        const selectedBrand = brandFilter.value;
        if (selectedBrand !== 'all') {
            filteredProducts = filteredProducts.filter(card =>
                card.dataset.brand === selectedBrand
            );
        }

        // Filtre par stock
        const selectedStock = stockFilter.value;
        if (selectedStock !== 'all') {
            filteredProducts = filteredProducts.filter(card =>
                card.dataset.stock === selectedStock
            );
        }

        // Tri par prix
        const selectedPriceSort = priceFilter.value;
        if (selectedPriceSort !== 'default') {
            filteredProducts.sort((a, b) => {
                const priceA = parseFloat(a.dataset.price);
                const priceB = parseFloat(b.dataset.price);
                return selectedPriceSort === 'asc' ? priceA - priceB : priceB - priceA;
            });
        }

        // Mettre à jour l'affichage
        productGrid.innerHTML = '';
        filteredProducts.forEach(card => productGrid.appendChild(card));
    }

    // Ajouter des écouteurs d'événements pour chaque filtre
    categoryFilter.addEventListener('change', updateProductDisplay);
    priceFilter.addEventListener('change', updateProductDisplay);
    brandFilter.addEventListener('change', updateProductDisplay);
    stockFilter.addEventListener('change', updateProductDisplay);

    // Initialiser l'affichage
    updateProductDisplay();
    updateCartDisplay();
});

// Exposer removeFromCart globalement pour les boutons de suppression
window.removeFromCart = function(productName) {
    const cart = JSON.parse(localStorage.getItem('cart')) || [];
    const updatedCart = cart.filter(item => item.name !== productName);
    localStorage.setItem('cart', JSON.stringify(updatedCart));
    // Recharger le panier
    const event = new Event('DOMContentLoaded');
    document.dispatchEvent(event);
};



