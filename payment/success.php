<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../classes/PayPalManager.php';

SessionManager::init();

if (!SessionManager::has('user_id')) {
    header('Location: ../login.php');
    exit;
}

// Récupérer les paramètres PayPal
$paypalOrderId = $_GET['token'] ?? null;
$payerId = $_GET['PayerID'] ?? null;

if (!$paypalOrderId) {
    header('Location: ../checkout.php?error=invalid_payment');
    exit;
}

// Récupérer la commande locale
$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("SELECT * FROM orders WHERE paypal_order_id = ? AND status = 'pending'");
$stmt->execute([$paypalOrderId]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: ../checkout.php?error=order_not_found');
    exit;
}

// Capturer le paiement
$paypalManager = new PayPalManager();
$result = $paypalManager->captureOrder($paypalOrderId, $order['id']);

if ($result['success']) {
    $success = true;
    $orderId = $result['order_id'];
    $transactionId = $result['transaction_id'];
    
    // Récupérer les détails complets de la commande
    $stmt = $db->prepare("
        SELECT o.*, oi.quantity, oi.unit_price, tc.name as ticket_name,
               m.match_date, ht.name as home_team, at.name as away_team, s.name as stadium_name
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN ticket_categories tc ON oi.ticket_category_id = tc.id
        JOIN matches m ON tc.match_id = m.id
        JOIN teams ht ON m.home_team_id = ht.id
        JOIN teams at ON m.away_team_id = at.id
        JOIN stadiums s ON m.stadium_id = s.id
        WHERE o.id = ?
    ");
    $stmt->execute([$orderId]);
    $orderDetails = $stmt->fetchAll();
    
} else {
    $error = $result['error'];
}

include '../includes/header.php';
?>

<div class="payment-result">
    <?php if (isset($success) && $success): ?>
        <div class="success-container">
            <div class="success-icon">✓</div>
            <h1>Paiement réussi !</h1>
            <p>Votre commande a été confirmée et vos billets vous seront envoyés par email.</p>
            
            <div class="order-details">
                <h2>Détails de votre commande</h2>
                <p><strong>Numéro de commande:</strong> <?= htmlspecialchars($orderDetails[0]['order_number']) ?></p>
                <p><strong>Transaction ID:</strong> <?= htmlspecialchars($transactionId) ?></p>
                <p><strong>Montant total:</strong> <?= number_format($orderDetails[0]['total_amount'], 2) ?> MAD</p>
                
                <div class="tickets-list">
                    <h3>Vos billets</h3>
                    <?php foreach ($orderDetails as $ticket): ?>
                        <div class="ticket-item">
                            <h4><?= htmlspecialchars($ticket['home_team'] . ' vs ' . $ticket['away_team']) ?></h4>
                            <p>
                                <?= date('d/m/Y à H:i', strtotime($ticket['match_date'])) ?><br>
                                <?= htmlspecialchars($ticket['stadium_name']) ?><br>
                                Catégorie: <?= htmlspecialchars($ticket['ticket_name']) ?><br>
                                Quantité: <?= $ticket['quantity'] ?> billet(s)
                            </p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="next-steps">
                <h3>Prochaines étapes</h3>
                <ul>
                    <li>Vous recevrez un email de confirmation dans les prochaines minutes</li>
                    <li>Vos billets PDF seront joints à cet email</li>
                    <li>Présentez vos billets (imprimés ou sur mobile) le jour du match</li>
                </ul>
            </div>
            
            <div class="action-buttons">
                <a href="../index.php" class="btn btn-primary">Retour à l'accueil</a>
                <a href="../account/orders.php" class="btn btn-secondary">Mes commandes</a>
            </div>
        </div>
    <?php else: ?>
        <div class="error-container">
            <div class="error-icon">✗</div>
            <h1>Erreur de paiement</h1>
            <p>Une erreur s'est produite lors du traitement de votre paiement:</p>
            <p class="error-message"><?= htmlspecialchars($error ?? 'Erreur inconnue') ?></p>
            
            <div class="action-buttons">
                <a href="../checkout.php" class="btn btn-primary">Réessayer</a>
                <a href="../cart.php" class="btn btn-secondary">Retour au panier</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.payment-result {
    max-width: 800px;
    margin: 50px auto;
    padding: 20px;
    text-align: center;
}

.success-container, .error-container {
    background: white;
    border-radius: 10px;
    padding: 40px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.success-icon {
    width: 80px;
    height: 80px;
    background: #28a745;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 40px;
    color: white;
    margin: 0 auto 20px;
}

.error-icon {
    width: 80px;
    height: 80px;
    background: #dc3545;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 40px;
    color: white;
    margin: 0 auto 20px;
}

.order-details {
    text-align: left;
    margin: 30px 0;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
}

.ticket-item {
    border: 1px solid #ddd;
    border-radius: 5px;
    padding: 15px;
    margin: 10px 0;
}

.action-buttons {
    margin-top: 30px;
}

.btn {
    display: inline-block;
    padding: 12px 24px;
    margin: 0 10px;
    text-decoration: none;
    border-radius: 5px;
    font-weight: bold;
}

.btn-primary {
    background: #007bff;
    color: white;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}
</style>

<?php include '../includes/footer.php'; ?>