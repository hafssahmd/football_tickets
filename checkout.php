<?php
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'classes/Cart.php';
require_once 'classes/PayPalManager.php';

SessionManager::init();

// Vérifier l'authentification
if (!SessionManager::has('user_id')) {
    header('Location: login.php?redirect=checkout');
    exit;
}

$userId = SessionManager::get('user_id');
$cart = new Cart();
$cartItems = $cart->getCartItems($userId);

if (empty($cartItems)) {
    header('Location: cart.php');
    exit;
}

// Calculer les totaux
$subtotal = 0;
$detailedItems = [];

foreach ($cartItems as $item) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("
        SELECT tc.*, m.home_team_id, m.away_team_id, m.match_date,
               ht.name as home_team, at.name as away_team, s.name as stadium_name
        FROM ticket_categories tc
        JOIN matches m ON tc.match_id = m.id
        JOIN teams ht ON m.home_team_id = ht.id
        JOIN teams at ON m.away_team_id = at.id
        JOIN stadiums s ON m.stadium_id = s.id
        WHERE tc.id = ?
    ");
    $stmt->execute([$item['ticket_category_id']]);
    $ticketInfo = $stmt->fetch();
    
    $itemTotal = $ticketInfo['price'] * $item['quantity'];
    $subtotal += $itemTotal;
    
    $detailedItems[] = array_merge($item, $ticketInfo, ['item_total' => $itemTotal]);
}

$total = $subtotal; // Ajouter les frais si nécessaire

// Traitement du formulaire de commande
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_paypal_order'])) {
    if (!SessionManager::verifyCSRFToken($_POST['csrf_token'])) {
        $error = "Token de sécurité invalide";
    } else {
        $paypalManager = new PayPalManager();
        $result = $paypalManager->createOrder($cartItems, $userId);
        
        if ($result['success']) {
            // Rediriger vers PayPal pour l'approbation
            header('Location: ' . $result['approval_url']);
            exit;
        } else {
            $error = "Erreur lors de la création de la commande: " . $result['error'];
        }
    }
}

include 'includes/header.php';
?>

<div class="checkout-container">
    <div class="checkout-header">
        <h1>Finaliser votre commande</h1>
        <div class="progress-bar">
            <div class="step completed">1. Panier</div>
            <div class="step active">2. Checkout</div>
            <div class="step">3. Paiement</div>
            <div class="step">4. Confirmation</div>
        </div>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="checkout-content">
        <div class="order-summary">
            <h2>Résumé de votre commande</h2>
            
            <?php foreach ($detailedItems as $item): ?>
                <div class="order-item">
                    <div class="match-info">
                        <h3><?= htmlspecialchars($item['home_team'] . ' vs ' . $item['away_team']) ?></h3>
                        <p class="match-details">
                            <?= date('d/m/Y à H:i', strtotime($item['match_date'])) ?><br>
                            <?= htmlspecialchars($item['stadium_name']) ?>
                        </p>
                    </div>
                    <div class="ticket-details">
                        <span class="category"><?= htmlspecialchars($item['name']) ?></span>
                        <div class="quantity-price">
                            <span class="quantity"><?= $item['quantity'] ?> billet(s)</span>
                            <span class="unit-price"><?= number_format($item['price'], 2) ?> MAD/billet</span>
                            <span class="total-price"><?= number_format($item['item_total'], 2) ?> MAD</span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <div class="order-totals">
                <div class="subtotal">
                    <span>Sous-total:</span>
                    <span><?= number_format($subtotal, 2) ?> MAD</span>
                </div>
                <div class="total">
                    <span>Total:</span>
                    <span><?= number_format($total, 2) ?> MAD</span>
                </div>
            </div>
        </div>

        <div class="payment-section">
            <h2>Paiement</h2>
            
            <form method="POST" class="checkout-form">
                <input type="hidden" name="csrf_token" value="<?= SessionManager::generateCSRFToken() ?>">
                
                <div class="payment-methods">
                    <div class="payment-method active">
                        <input type="radio" id="paypal" name="payment_method" value="paypal" checked>
                        <label for="paypal">
                            <img src="assets/images/paypal-logo.png" alt="PayPal">
                            Payer avec PayPal
                        </label>
                    </div>
                </div>
                
                <div class="terms-conditions">
                    <input type="checkbox" id="terms" name="accept_terms" required>
                    <label for="terms">
                        J'accepte les <a href="terms.php" target="_blank">conditions générales</a>
                        et la <a href="privacy.php" target="_blank">politique de confidentialité</a>
                    </label>
                </div>
                
                <button type="submit" name="create_paypal_order" class="btn-primary btn-large">
                    <img src="assets/images/paypal-icon.png" alt="PayPal">
                    Payer <?= number_format($total, 2) ?> MAD avec PayPal
                </button>
            </form>
        </div>
    </div>
</div>

<style>
.checkout-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.progress-bar {
    display: flex;
    justify-content: center;
    margin: 20px 0;
}

.step {
    padding: 10px 20px;
    background: #f5f5f5;
    color: #666;
    border-radius: 20px;
    margin: 0 5px;
}

.step.active {
    background: #007bff;
    color: white;
}

.step.completed {
    background: #28a745;
    color: white;
}

.checkout-content {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 30px;
    margin-top: 30px;
}

.order-item {
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

.order-totals {
    border-top: 2px solid #007bff;
    padding-top: 20px;
    margin-top: 20px;
}

.total {
    font-size: 1.2em;
    font-weight: bold;
    color: #007bff;
}

.btn-large {
    width: 100%;
    padding: 15px;
    font-size: 1.1em;
    background: #0070ba;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
}

@media (max-width: 768px) {
    .checkout-content {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include 'includes/footer.php'; ?>