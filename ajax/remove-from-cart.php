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

if (!isset($input['item_id'])) {
    die(json_encode(['success' => false, 'message' => 'Données manquantes']));
}

$itemId = (int)$input['item_id'];

try {
    $cart = new Cart();
    $result = $cart->removeItem($_SESSION['user_id'], $itemId);
    
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'message' => 'Article supprimé du panier',
            'data' => [
                'total_items' => $result['total_items'],
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
    error_log("Remove from cart error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Une erreur est survenue lors de la suppression de l\'article'
    ]);
} 