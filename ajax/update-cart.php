<?php
require_once '../config/database.php';
require_once '../classes/Cart.php';
require_once '../config/session.php';

header('Content-Type: application/json');

// Vérifier si la requête est en AJAX
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    die(json_encode(['success' => false, 'message' => 'Requête invalide']));
}

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'message' => 'Vous devez être connecté pour modifier le panier']));
}

// Récupérer et valider les données
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['item_id']) || !isset($input['quantity'])) {
    die(json_encode(['success' => false, 'message' => 'Données manquantes']));
}

$itemId = (int)$input['item_id'];
$quantity = (int)$input['quantity'];

if ($quantity < 1) {
    die(json_encode(['success' => false, 'message' => 'Quantité invalide']));
}

try {
    $cart = new Cart();
    $result = $cart->updateQuantity($_SESSION['user_id'], $itemId, $quantity);
    
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'message' => 'Quantité mise à jour',
            'data' => [
                'item_id' => $itemId,
                'quantity' => $quantity,
                'price' => $result['price'],
                'total' => $result['total']
            ],
            'cartCount' => $result['cartCount']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => $result['message']
        ]);
    }
} catch (Exception $e) {
    error_log("Update cart error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Une erreur est survenue lors de la mise à jour du panier'
    ]);
} 