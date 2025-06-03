<?php
declare(strict_types=1);

// Error handling
ini_set('display_errors', 0);
error_reporting(E_ALL);

function handleError($errno, $errstr, $errfile, $errline) {
    error_log("PHP Error [$errno] $errstr on line $errline in file $errfile");
    sendJsonResponse(false, 'Une erreur est survenue');
    exit;
}
set_error_handler('handleError');

// Required files
require_once '../config/database.php';
require_once '../classes/Cart.php';
require_once '../config/session.php';

// Initialize session
SessionManager::init();

// Set content type to JSON
header('Content-Type: application/json');

// Helper function for JSON responses
function sendJsonResponse(bool $success, string $message, array $data = []): void {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// Validate AJAX request
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    sendJsonResponse(false, 'Invalid request method');
}

// Check if user is logged in
if (!SessionManager::isLoggedIn()) {
    sendJsonResponse(false, 'Vous devez être connecté pour ajouter des articles au panier');
}

// Get and validate JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    sendJsonResponse(false, 'Invalid JSON data');
}

// Validate required fields
$requiredFields = ['category_id', 'match_id', 'quantity'];
foreach ($requiredFields as $field) {
    if (!isset($input[$field])) {
        sendJsonResponse(false, "Le champ '$field' est requis");
    }
}

// Sanitize and validate input
$categoryId = filter_var($input['category_id'], FILTER_VALIDATE_INT);
$matchId = filter_var($input['match_id'], FILTER_VALIDATE_INT);
$quantity = filter_var($input['quantity'], FILTER_VALIDATE_INT);

if ($categoryId === false || $matchId === false || $quantity === false) {
    sendJsonResponse(false, 'Données invalides');
}

// Validate quantity
if ($quantity <= 0) {
    sendJsonResponse(false, 'La quantité doit être supérieure à 0');
}

try {
    $cart = new Cart();
    $userId = SessionManager::get('user_id');
    
    // Add item to cart
    $result = $cart->addItem($categoryId, $matchId, $quantity);

    if ($result) {
        // Optionally fetch the item just added/updated
        $items = $cart->getItems();
        $item = null;
        foreach ($items as $i) {
            if ($i['category_id'] == $categoryId && $i['match_id'] == $matchId) {
                $item = $i;
                break;
            }
        }
        sendJsonResponse(true, 'Article ajouté au panier', [
            'cartCount' => $cart->getItemCount(),
            'item' => $item
        ]);
    } else {
        sendJsonResponse(false, 'Erreur lors de l\'ajout au panier');
    }
} catch (Exception $e) {
    error_log("Add to cart error: " . $e->getMessage());
    sendJsonResponse(false, 'Une erreur est survenue lors de l\'ajout au panier');
} 

$user = new User();
$user->logout();
// Redirect to login or home 