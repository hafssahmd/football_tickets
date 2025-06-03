<?php
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../classes/Cart.php';
require_once __DIR__.'/../config/session.php';

SessionManager::init();

$cart = new Cart();
$cartItems = $cart->getCartItems();
$cartTotal = $cart->getCartTotal();

require_once 'includes/header.php';
?>

<main class="main-content">
    <div class="container">
        <div class="page-header">
            <h1>Mon panier</h1>
        </div>

        <?php if (empty($cartItems)): ?>
        <div class="empty-cart">
            <div class="empty-cart-icon">
                <i class="icon-cart-empty"></i>
            </div>
            <h3>Votre panier est vide</h3>
            <p>Découvrez nos matchs disponibles et ajoutez vos billets préférés</p>
            <a href="matches.php" class="btn btn-primary">Voir les matchs</a>
        </div>
        <?php else: ?>
        <div class="cart-content">
            <div class="cart-items">
                <?php foreach ($cartItems as $item): ?>
                <div class="cart-item" data-item-id="<?= $item['id'] ?>">
                    <div class="item-match-info">
                        <div class="match-teams">
                            <strong><?= htmlspecialchars($item['home_team']) ?> vs <?= htmlspecialchars($item['away_team']) ?></strong>
                        </div>
                        <div class="match-details">
                            <span class="match-date"><?= date('d/m/Y à H:i', strtotime($item['match_date'])) ?></span>
                            <span class="match-venue"><?= htmlspecialchars($item['stadium_name']) ?></span>
                        </div>
                        <div class="ticket-category">
                            Catégorie : <strong><?= htmlspecialchars($item['category_name']) ?></strong>
                        </div>
                    </div>
                    
                    <div class="item-quantity">
                        <label>Quantité :</label>
                        <div class="quantity-controls">
                            <button type="button" class="qty-btn qty-minus" data-item-id="<?= $item['id'] ?>">-</button>
                            <input type="number" class="qty-input" value="<?= $item['quantity'] ?>" min="1" max="10" readonly>
                            <button type="button" class="qty-btn qty-plus" data-item-id="<?= $item['id'] ?>">+</button>
                        </div>
                    </div>
                    
                    <div class="item-price">
                        <div class="unit-price"><?= number_format($item['unit_price'], 2) ?> MAD</div>
                        <div class="total-price"><?= number_format($item['unit_price'] * $item['quantity'], 2) ?> MAD</div>
                    </div>
                    
                    <div class="item-actions">
                        <button type="button" class="btn-remove" data-item-id="<?= $item['id'] ?>">
                            <i class="icon-trash"></i>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="cart-summary">
                <div class="summary-card">
                    <h3>Récapitulatif</h3>
                    
                    <div class="summary-line">
                        <span>Sous-total :</span>
                        <span id="cart-subtotal"><?= number_format($cartTotal, 2) ?> MAD</span>
                    </div>
                    
                    <div class="summary-line">
                        <span>Frais de service :</span>
                        <span>0,00 MAD</span>
                    </div>
                    
                    <div class="summary-line total">
                        <span><strong>Total :</strong></span>
                        <span id="cart-total"><strong><?= number_format($cartTotal, 2) ?> MAD</strong></span>
                    </div>
                    
                    <div class="checkout-actions">
                        <a href="matches.php" class="btn btn-outline btn-full">Continuer mes achats</a>
                        <a href="checkout.php" class="btn btn-primary btn-full">Procéder au paiement</a>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</main>

<?php require_once 'includes/footer.php'; ?>