<?php
/**
 * SessionManager - Gestionnaire de sessions sécurisé
 * 
 * Ce fichier contient la classe SessionManager qui gère toutes les opérations
 * liées aux sessions de manière sécurisée et centralisée.
 * 
 * @author Football Tickets Maroc
 * @version 1.0
 */

class SessionManager {
    private static $started = false;
    private static $sessionTimeout = 7200; // 2 heures en secondes
    
    /**
     * Initialise la session avec une configuration sécurisée
     */
    public static function init() {
        if (self::$started) {
            return;
        }
        
        // Vérifier si une session est déjà active
        if (session_status() === PHP_SESSION_ACTIVE) {
            self::$started = true;
            self::checkSessionTimeout();
            return;
        }
        
        // Configuration sécurisée des sessions
        if (session_status() === PHP_SESSION_NONE) {
            // Sécurité des cookies de session
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_samesite', 'Lax'); // Changed to Lax for better compatibility
            
            // Activer cookie_secure en HTTPS
            if (self::isHttps()) {
                ini_set('session.cookie_secure', 1);
            }
            
            // Configuration avancée de sécurité
            ini_set('session.use_strict_mode', 1);
            ini_set('session.cookie_path', '/');
            
            // Nom de session personnalisé
            session_name('FOOTBALL_TICKETS_SESSION');
            
            // Durée de vie de la session
            ini_set('session.gc_maxlifetime', self::$sessionTimeout);
            ini_set('session.cookie_lifetime', self::$sessionTimeout);
            
            // Démarrer la session
            session_start();
        }
        
        // Régénération de l'ID de session pour éviter la fixation
        if (!isset($_SESSION['initiated'])) {
            session_regenerate_id(true);
            $_SESSION['initiated'] = true;
            $_SESSION['created_at'] = time();
        }
        
        // Vérification de l'expiration de la session
        self::checkSessionTimeout();
        
        // Marquer comme initialisé
        self::$started = true;
    }
    
    /**
     * Vérifie si la connexion est sécurisée (HTTPS)
     */
    private static function isHttps() {
        return (
            (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ||
            (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ||
            (isset($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] === 'https')
        );
    }
    
    /**
     * Vérifie et gère l'expiration de la session
     */
    private static function checkSessionTimeout() {
        if (isset($_SESSION['last_activity'])) {
            $inactiveTime = time() - $_SESSION['last_activity'];
            
            if ($inactiveTime > self::$sessionTimeout) {
                self::destroy();
                return false;
            }
        }
        
        $_SESSION['last_activity'] = time();
        return true;
    }
    
    /**
     * Définit une valeur dans la session
     */
    public static function set($key, $value) {
        self::init();
        $_SESSION[$key] = $value;
    }
    
    /**
     * Récupère une valeur de la session
     */
    public static function get($key, $default = null) {
        self::init();
        return $_SESSION[$key] ?? $default;
    }
    
    /**
     * Vérifie si une clé existe dans la session
     */
    public static function has($key) {
        self::init();
        return isset($_SESSION[$key]);
    }
    
    /**
     * Supprime une clé de la session
     */
    public static function remove($key) {
        self::init();
        unset($_SESSION[$key]);
    }
    
    /**
     * Détruit complètement la session
     */
    public static function destroy() {
        if (session_status() === PHP_SESSION_ACTIVE) {
            // Supprimer toutes les variables de session
            $_SESSION = array();
            
            // Supprimer le cookie de session
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params["path"], $params["domain"],
                    $params["secure"], $params["httponly"]
                );
            }
            
            // Détruire la session
            session_destroy();
        }
        self::$started = false;
    }
    
    /**
     * Régénère l'ID de session
     */
    public static function regenerateId($deleteOldSession = true) {
        self::init();
        if (session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
            session_regenerate_id($deleteOldSession);
        }
    }
    
    /**
     * Génère un token CSRF
     */
    public static function generateCSRFToken() {
        self::init();
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Vérifie un token CSRF
     */
    public static function verifyCSRFToken($token) {
        self::init();
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Génère un nouveau token CSRF
     */
    public static function refreshCSRFToken() {
        self::init();
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Vérifie si un utilisateur est connecté
     */
    public static function isLoggedIn() {
        self::init();
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    /**
     * Récupère les informations de l'utilisateur connecté
     */
    public static function getCurrentUser() {
        self::init();
        if (self::isLoggedIn()) {
            return [
                'id' => $_SESSION['id'],
                'email' => $_SESSION['email'] ?? '',
                'role' => $_SESSION['role'] ?? 'user',
                'first_name' => $_SESSION['first_name'] ?? '',
                'last_name' => $_SESSION['last_name'] ?? '',
                'phone' => $_SESSION['phone'] ?? '',
                'avatar' => $_SESSION['avatar'] ?? ''
            ];
        }
        return null;
    }
    
    /**
     * Connecte un utilisateur
     */
    public static function login($userData) {
        self::init();
        
        // Régénérer l'ID de session pour éviter la fixation
        self::regenerateId();
        
        // Stocker les informations utilisateur
        $_SESSION['user_id'] = $userData['id'];
        $_SESSION['email'] = $userData['email'];
        $_SESSION['first_name'] = $userData['first_name'] ?? '';
        $_SESSION['last_name'] = $userData['last_name'] ?? '';
        $_SESSION['name'] = ($userData['first_name'] ?? '') . ' ' . ($userData['last_name'] ?? '');
        $_SESSION['role'] = $userData['role'] ?? 'user';
        $_SESSION['phone'] = $userData['phone'] ?? '';
        $_SESSION['avatar'] = $userData['avatar'] ?? '';
        
        // Marquer l'heure de connexion et l'activité
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        $_SESSION['is_logged_in'] = true;
        
        // Initialiser le panier si nécessaire
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        
        // Générer un nouveau token CSRF
        self::refreshCSRFToken();
    }
    
    /**
     * Déconnecte l'utilisateur
     */
    public static function logout() {
        self::init();
        
        // Sauvegarder le panier temporairement si nécessaire
        $tempCart = $_SESSION['cart'] ?? [];
        
        // Supprimer toutes les données de session
        $_SESSION = array();
        
        // Restaurer le panier si nécessaire
        if (!empty($tempCart)) {
            $_SESSION['cart'] = $tempCart;
        }
        
        // Régénérer l'ID de session
        self::regenerateId();
        
        // Générer un nouveau token CSRF
        self::refreshCSRFToken();
    }
    
    /**
     * Force la connexion (redirige vers login si non connecté)
     */
    public static function requireLogin($redirectTo = '../pages/login.php') {
        if (!self::isLoggedIn()) {
            // Stocker l'URL actuelle pour redirection après connexion
            $currentUrl = $_SERVER['REQUEST_URI'];
            $redirectUrl = $redirectTo . '?redirect=' . urlencode($currentUrl);
            
            header('Location: ' . $redirectUrl);
            exit();
        }
    }
    
    /**
     * Vérifie si l'utilisateur a un rôle spécifique
     */
    public static function hasRole($role) {
        self::init();
        return self::get('user_role') === $role;
    }
    
    /**
     * Vérifie si l'utilisateur a l'un des rôles spécifiés
     */
    public static function hasAnyRole($roles) {
        self::init();
        $userRole = self::get('user_role');
        return in_array($userRole, $roles);
    }
    
    /**
     * Force un rôle spécifique
     */
    public static function requireRole($role, $redirectTo = '../pages/unauthorized.php') {
        self::requireLogin();
        
        if (!self::hasRole($role)) {
            header('Location: ' . $redirectTo);
            exit();
        }
    }
    
    /**
     * Force l'un des rôles spécifiés
     */
    public static function requireAnyRole($roles, $redirectTo = '../pages/unauthorized.php') {
        self::requireLogin();
        
        if (!self::hasAnyRole($roles)) {
            header('Location: ' . $redirectTo);
            exit();
        }
    }
    
    /**
     * Obtient le statut de la session
     */
    public static function getStatus() {
        $status = session_status();
        switch ($status) {
            case PHP_SESSION_DISABLED:
                return 'disabled';
            case PHP_SESSION_NONE:
                return 'none';
            case PHP_SESSION_ACTIVE:
                return 'active';
            default:
                return 'unknown';
        }
    }
    
    /**
     * Obtient des informations sur la session
     */
    public static function getSessionInfo() {
        self::init();
        return [
            'status' => self::getStatus(),
            'id' => session_id(),
            'name' => session_name(),
            'started' => self::$started,
            'timeout' => self::$sessionTimeout,
            'last_activity' => self::get('last_activity'),
            'created_at' => self::get('created_at'),
            'login_time' => self::get('login_time'),
            'is_logged_in' => self::isLoggedIn(),
            'user_role' => self::get('user_role'),
            'csrf_token' => self::get('csrf_token')
        ];
    }
    
    /**
     * Flash messages - Ajouter un message flash
     */
    public static function addFlash($type, $message) {
        self::init();
        if (!isset($_SESSION['flash_messages'])) {
            $_SESSION['flash_messages'] = [];
        }
        $_SESSION['flash_messages'][] = [
            'type' => $type,
            'message' => $message,
            'timestamp' => time()
        ];
    }
    
    /**
     * Flash messages - Récupérer et supprimer les messages flash
     */
    public static function getFlashMessages() {
        self::init();
        $messages = $_SESSION['flash_messages'] ?? [];
        unset($_SESSION['flash_messages']);
        return $messages;
    }
    
    /**
     * Flash messages - Vérifier s'il y a des messages flash
     */
    public static function hasFlashMessages() {
        self::init();
        return !empty($_SESSION['flash_messages']);
    }
    
    /**
     * Méthodes de commodité pour les messages flash
     */
    public static function addSuccess($message) {
        self::addFlash('success', $message);
    }
    
    public static function addError($message) {
        self::addFlash('error', $message);
    }
    
    public static function addWarning($message) {
        self::addFlash('warning', $message);
    }
    
    public static function addInfo($message) {
        self::addFlash('info', $message);
    }
    
    /**
     * Mise à jour des informations utilisateur
     */
    public static function updateUserInfo($userData) {
        self::init();
        if (self::isLoggedIn()) {
            foreach ($userData as $key => $value) {
                $_SESSION['user_' . $key] = $value;
            }
            
            // Mettre à jour le nom complet si nécessaire
            if (isset($userData['first_name']) || isset($userData['last_name'])) {
                $_SESSION['user_name'] = ($_SESSION['user_first_name'] ?? '') . ' ' . ($_SESSION['user_last_name'] ?? '');
            }
        }
    }
    
    /**
     * Configuration du timeout de session
     */
    public static function setSessionTimeout($seconds) {
        self::$sessionTimeout = $seconds;
    }
    
    /**
     * Nettoyage périodique des sessions expirées
     */
    public static function cleanupExpiredSessions() {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_gc();
        }
    }

    /**
     * Gestion du panier
     */
    public static function addToCart($item) {
        self::init();
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        $_SESSION['cart'][] = $item;
    }

    public static function getCart() {
        self::init();
        return $_SESSION['cart'] ?? [];
    }

    public static function clearCart() {
        self::init();
        $_SESSION['cart'] = [];
    }

    public static function updateCartItem($index, $quantity) {
        self::init();
        if (isset($_SESSION['cart'][$index])) {
            $_SESSION['cart'][$index]['quantity'] = $quantity;
        }
    }

    public static function removeFromCart($index) {
        self::init();
        if (isset($_SESSION['cart'][$index])) {
            unset($_SESSION['cart'][$index]);
            $_SESSION['cart'] = array_values($_SESSION['cart']); // Réindexer le tableau
        }
    }

    /**
     * Gestion des commandes
     */
    public static function setCurrentOrder($orderData) {
        self::init();
        $_SESSION['current_order'] = $orderData;
    }

    public static function getCurrentOrder() {
        self::init();
        return $_SESSION['current_order'] ?? null;
    }

    public static function clearCurrentOrder() {
        self::init();
        unset($_SESSION['current_order']);
    }
}

// Auto-initialisation lors de l'inclusion du fichier
SessionManager::init();

// Fonctions helper globales (optionnelles)
if (!function_exists('session_get')) {
    function session_get($key, $default = null) {
        return SessionManager::get($key, $default);
    }
}

if (!function_exists('session_set')) {
    function session_set($key, $value) {
        return SessionManager::set($key, $value);
    }
}

if (!function_exists('is_logged_in')) {
    function is_logged_in() {
        return SessionManager::isLoggedIn();
    }
}

if (!function_exists('current_user')) {
    function current_user() {
        return SessionManager::getCurrentUser();
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token() {
        return SessionManager::generateCSRFToken();
    }
}

if (!function_exists('flash_success')) {
    function flash_success($message) {
        SessionManager::addSuccess($message);
    }
}

if (!function_exists('flash_error')) {
    function flash_error($message) {
        SessionManager::addError($message);
    }
}

?>