class CartManager {
    constructor() {
        this.init();
        this.bindEvents();
    }
    
    init() {
        this.updateCartBadge();
    }
    
    bindEvents() {
        // Boutons d'ajout au panier
        document.querySelectorAll('.add-to-cart-btn').forEach(btn => {
            btn.addEventListener('click', (e) => this.handleAddToCart(e));
        });
        
        // Mise à jour des quantités
        document.querySelectorAll('.quantity-select').forEach(input => {
            input.addEventListener('change', (e) => this.handleQuantityChange(e));
        });
        
        // Suppression d'articles
        document.querySelectorAll('.remove-item-btn').forEach(btn => {
            btn.addEventListener('click', (e) => this.handleRemoveItem(e));
        });

        // Fermeture du modal
        document.querySelectorAll('.modal-close').forEach(btn => {
            btn.addEventListener('click', () => {
                const modal = document.getElementById('cart-modal');
                if (modal) {
                    modal.style.display = 'none';
                }
            });
        });

        // Fermeture du modal en cliquant en dehors
        window.addEventListener('click', (event) => {
            const modal = document.getElementById('cart-modal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });
    }
    
    async handleAddToCart(event) {
        event.preventDefault();
        const button = event.target;
        const categoryId = button.dataset.categoryId;
        const categoryName = button.dataset.categoryName;
        const price = button.dataset.price;
        const matchId = button.dataset.matchId;
        const quantitySelect = document.getElementById(`qty_${categoryId}`);
        const quantity = quantitySelect ? parseInt(quantitySelect.value) : 1;
        
        if (!categoryId || !matchId) {
            this.showNotification('Erreur: données de billet invalides', 'error');
            return;
        }
        
        try {
            button.disabled = true;
            button.textContent = 'Ajout en cours...';
            
            const response = await fetch('ajax/add-to-cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    category_id: categoryId,
                    match_id: matchId,
                    quantity: quantity
                })
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                console.error('Response is not JSON:', text);
                throw new Error('La réponse du serveur n\'est pas au format JSON');
            }
            
            const result = await response.json();
            
            if (result.success) {
                this.updateCartBadge(result.cartCount);
                this.showCartModal(categoryName, quantity, price);
                this.showNotification(`${quantity} billet(s) ${categoryName} ajouté(s) au panier`, 'success');
            } else {
                this.showNotification(result.message || 'Erreur lors de l\'ajout au panier', 'error');
            }
            
        } catch (error) {
            console.error('Erreur:', error);
            this.showNotification('Erreur de connexion: ' + error.message, 'error');
        } finally {
            button.disabled = false;
            button.textContent = 'Ajouter au panier';
        }
    }
    
    async handleQuantityChange(event) {
        const input = event.target;
        const itemId = input.dataset.itemId;
        const newQuantity = parseInt(input.value);
        
        if (!itemId || isNaN(newQuantity) || newQuantity < 1) {
            return;
        }
        
        try {
            const response = await fetch('ajax/update-cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    item_id: itemId,
                    quantity: newQuantity
                })
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                console.error('Response is not JSON:', text);
                throw new Error('La réponse du serveur n\'est pas au format JSON');
            }
            
            const result = await response.json();
            
            if (result.success) {
                this.updateCartDisplay(result.data);
                this.updateCartBadge(result.cartCount);
            } else {
                this.showNotification(result.message || 'Erreur lors de la mise à jour', 'error');
            }
            
        } catch (error) {
            console.error('Erreur:', error);
            this.showNotification('Erreur de connexion: ' + error.message, 'error');
        }
    }
    
    async handleRemoveItem(event) {
        event.preventDefault();
        const button = event.target;
        const itemId = button.dataset.itemId;
        
        if (!itemId) {
            return;
        }
        
        if (!confirm('Êtes-vous sûr de vouloir supprimer cet article ?')) {
            return;
        }
        
        try {
            const response = await fetch('ajax/remove-from-cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    item_id: itemId
                })
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                console.error('Response is not JSON:', text);
                throw new Error('La réponse du serveur n\'est pas au format JSON');
            }
            
            const result = await response.json();
            
            if (result.success) {
                const itemElement = document.querySelector(`[data-item-id="${itemId}"]`);
                if (itemElement) {
                    itemElement.remove();
                }
                this.updateCartTotals(result.data);
                this.updateCartBadge(result.cartCount);
                this.showNotification('Article supprimé du panier', 'success');
                
                if (result.data.total_items === 0) {
                    window.location.reload();
                }
            } else {
                this.showNotification(result.message || 'Erreur lors de la suppression', 'error');
            }
            
        } catch (error) {
            console.error('Erreur:', error);
            this.showNotification('Erreur de connexion: ' + error.message, 'error');
        }
    }
    
    updateCartBadge(count) {
        const badge = document.querySelector('.cart-badge');
        if (badge) {
            badge.textContent = count || 0;
            badge.style.display = (count && count > 0) ? 'block' : 'none';
        }
    }
    
    updateCartDisplay(data) {
        const itemElement = document.querySelector(`[data-item-id="${data.item_id}"]`);
        if (itemElement) {
            const quantityElement = itemElement.querySelector('.cart-item-quantity');
            const priceElement = itemElement.querySelector('.cart-item-price');
            if (quantityElement) quantityElement.textContent = data.quantity;
            if (priceElement) priceElement.textContent = data.price;
        }
        this.updateCartTotals(data);
    }
    
    updateCartTotals(data) {
        const totalElement = document.querySelector('.cart-total');
        if (totalElement && data.total) {
            totalElement.textContent = data.total;
        }
    }
    
    showCartModal(categoryName, quantity, price) {
        const modal = document.getElementById('cart-modal');
        const message = document.getElementById('cart-message');
        
        if (modal && message) {
            const total = (quantity * price).toFixed(2);
            message.innerHTML = `
                <strong>${quantity}x ${categoryName}</strong><br>
                Prix unitaire: ${parseFloat(price).toFixed(2)} MAD<br>
                Total: ${total} MAD
            `;
            modal.style.display = 'block';
        }
    }
    
    showNotification(message, type = 'success') {
        // Supprimer les notifications existantes
        const existingNotifications = document.querySelectorAll('.notification');
        existingNotifications.forEach(n => n.remove());
        
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.textContent = message;
        
        // Styles inline pour la notification
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 8px;
            color: white;
            font-weight: 600;
            z-index: 10000;
            transform: translateX(100%);
            transition: transform 0.3s ease;
            max-width: 350px;
            word-wrap: break-word;
            ${type === 'success' ? 'background-color: #27ae60;' : 'background-color: #e74c3c;'}
        `;
        
        document.body.appendChild(notification);
        
        // Animation d'entrée
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
        }, 100);
        
        // Animation de sortie
        setTimeout(() => {
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
}

// Initialiser quand le DOM est prêt
document.addEventListener('DOMContentLoaded', () => {
    new CartManager();
});