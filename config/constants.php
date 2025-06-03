<?php
// Constantes de l'application
define('APP_NAME', 'Football Tickets Maroc');
define('APP_VERSION', '1.0.0');
define('APP_ENV', $_ENV['APP_ENV'] ?? 'development');

// URLs de base
define('BASE_URL', 'http://localhost/football_tickets/');
define('ADMIN_URL', BASE_URL . 'admin/');
define('ASSETS_URL', BASE_URL . 'assets/');

// Chemins de fichiers
define('ROOT_PATH', dirname(__DIR__) . '/');
define('UPLOADS_PATH', ROOT_PATH . 'assets/uploads/');
define('LOGS_PATH', ROOT_PATH . 'logs/');

// Configuration des uploads
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif']);

// Configuration PayPal
define('PAYPAL_ENVIRONMENT', APP_ENV === 'production' ? 'live' : 'sandbox');
define('PAYPAL_CURRENCY', 'MAD');

// Configuration email
define('MAIL_FROM', 'noreply@footballtickets.ma');
define('MAIL_FROM_NAME', APP_NAME);

// Statuts des commandes
define('ORDER_STATUS_PENDING', 'pending');
define('ORDER_STATUS_PAID', 'paid');
define('ORDER_STATUS_CANCELLED', 'cancelled');
define('ORDER_STATUS_REFUNDED', 'refunded');

// Rôles utilisateurs
define('USER_ROLE_USER', 'user');
define('USER_ROLE_ADMIN', 'admin');

// Messages de succès et d'erreur
define('MSG_SUCCESS_LOGIN', 'Connexion réussie');
define('MSG_ERROR_LOGIN', 'Email ou mot de passe incorrect');
define('MSG_SUCCESS_REGISTER', 'Inscription réussie');
define('MSG_ERROR_REGISTER', 'Erreur lors de l\'inscription');
define('MSG_SUCCESS_CART_ADD', 'Article ajouté au panier');
define('MSG_ERROR_CART_ADD', 'Impossible d\'ajouter l\'article au panier');
?>