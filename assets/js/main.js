// Configuration globale
const CONFIG = {
    baseUrl: 'http://localhost/football_tickets/',
    endpoints: {
        addToCart: 'ajax/add-to-cart.php',
        updateCart: 'ajax/update-cart.php',
        removeFromCart: 'ajax/remove-from-cart.php'
    }
};

// Utilitaires
const Utils = {
    // Afficher les notifications
    showNotification: function(message, type = 'success') {
        const notification = document.createElement('div');
        notification.className = notification notification-${type};
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.classList.add('show');
        }, 100);
        
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 300);
        }, 3000);
    },
    
    // Requête AJAX simple
    ajax: function(url, options = {}) {
        const defaults = {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };
        
        const config = Object.assign(defaults, options);
        
        return fetch(url, config)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erreur réseau');
                }
                return response.json();
            });
    },
    
    // Formatage des prix
    formatPrice: function(price) {
        return new Intl.NumberFormat('fr-FR', {
            style: 'currency',
            currency: 'MAD',
            minimumFractionDigits: 2
        }).format(price);
    }
};

// Gestion du panier
const Cart = {
    init: function() {
        this.bindEvents();
        this.updateCartBadge();
    },
    
    bindEvents: function() {
        // Ajouter au panier
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('add-to-cart-btn')) {
                e.preventDefault();
                this.addToCart(e.target);
            }
        });
        
        // Modifier quantité
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('qty-plus')) {
                this.updateQuantity(e.target.dataset.itemId, 'increase');
            } else if (e.target.classList.contains('qty-minus')) {
                this.updateQuantity(e.target.dataset.itemId, 'decrease');
            }
        });
        
        // Supprimer du panier
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('btn-remove') || e.target.parentElement.classList.contains('btn-remove')) {
                const itemId = e.target.dataset.itemId || e.target.parentElement.dataset.itemId;
                this.removeFromCart(itemId);
            }
        });
    },
    
    addToCart: function(button) {
        const categoryId = button.dataset.categoryId;
        const categoryName = button.dataset.categoryName;
        const price = parseFloat(button.dataset.price);
        const quantitySelect = document.getElementById(qty_${categoryId});
        const quantity = quantitySelect ? parseInt(quantitySelect.value) : 1;
        
        button.disabled = true;
        button.textContent = 'Ajout en cours...';
        
        const data = {
            category_id: categoryId,
            quantity: quantity
        };
        
        Utils.ajax(CONFIG.endpoints.addToCart, {
            method: 'POST',
            body: JSON.stringify(data)
        })
        .then(response => {
            if (response.success) {
                Utils.showNotification(${quantity} billet(s) ${categoryName} ajouté(s) au panier);
                this.updateCartBadge();
                this.showCartModal(categoryName, quantity, price);
            } else {
                Utils.showNotification(response.message || 'Erreur lors de l\'ajout au panier', 'error');
            }
        })
        .catch(error => {
            Utils.showNotification('Erreur de connexion', 'error');
        })
        .finally(() => {
            button.disabled = false;
            button.textContent = 'Ajouter au panier';
        });
    },
    
    updateQuantity: function(itemId, action) {
        const data = {
            item_id: itemId,
            action: action
        };
        
        Utils.ajax(CONFIG.endpoints.updateCart, {
            method: 'POST',
            body: JSON.stringify(data)
        })
        .then(response => {
            if (response.success) {
                this.updateCartDisplay(response.data);
                this.updateCartBadge();
            } else {
                Utils.showNotification(response.message || 'Erreur lors de la mise à jour', 'error');
            }
        })
        .catch(error => {
            Utils.showNotification('Erreur de connexion', 'error');
        });
    },
    
    removeFromCart: function(itemId) {
        if (!confirm('Êtes-vous sûr de vouloir supprimer cet article ?')) {
            return;
        }
        
        const data = {
            item_id: itemId
        };
        
        Utils.ajax(CONFIG.endpoints.removeFromCart, {
            method: 'POST',
            body: JSON.stringify(data)
        })
        .then(response => {
            if (response.success) {
                const itemElement = document.querySelector([data-item-id="${itemId}"]);
                if (itemElement) {
                    itemElement.remove();
                }
                this.updateCartTotals(response.data);
                this.updateCartBadge();
                Utils.showNotification('Article supprimé du panier');
                
                // Rediriger si le panier est vide
                if (response.data.total_items === 0) {
                    window.location.reload();
                }
            } else {
                Utils.showNotification(response.message || 'Erreur lors de la suppression', 'error');
            }
        })
        .catch(error => {
            Utils.showNotification('Erreur de connexion', 'error');
        });
    },
    
    updateCartBadge: function() {
        const badge = document.querySelector('.cart-badge');
        if (badge) {
            // Récupérer le nombre d'articles via AJAX
            Utils.ajax('ajax/get-cart-count.php', { method: 'GET' })
                .then(response => {
                    if (response.success) {
                        badge.textContent = response.count;
                        badge.style.display = response.count > 0 ? 'block' : 'none';
                    }
                })
                .catch(error => {
                    console.error('Erreur lors de la mise à jour du badge panier');
                });
        }
    },
    
    updateCartDisplay: function(data) {
        // Mettre à jour l'affichage de la quantité et du prix
        const itemElement = document.querySelector([data-item-id="${data.item_id}"]);
        if (itemElement) {
            const quantityElement = itemElement.querySelector('.cart-item-quantity');
            const priceElement = itemElement.querySelector('.cart-item-price');
            quantityElement.textContent = data.quantity;
            priceElement.textContent = data.price;
        }
        
        this.updateCartTotals(data);
    }
};