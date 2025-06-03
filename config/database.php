<?php
class Database {
    private static $instance = null;
    private $connection;
    
    // Configuration environnement
    private $config = [
        'development' => [
            'host' => 'localhost',
            'database' => 'football_tickets',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8mb4',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
                PDO::ATTR_PERSISTENT => false
            ]
        ],
        'production' => [
            'host' => 'your_prod_host',
            'database' => 'your_prod_db',
            'username' => 'your_prod_user',
            'password' => 'your_prod_password',
            'charset' => 'utf8mb4',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
                PDO::ATTR_PERSISTENT => true
            ]
        ]
    ];
    
    private function __construct() {
        $env = $_ENV['APP_ENV'] ?? 'development';
        $config = $this->config[$env];
        
        $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}";
        
        try {
            $this->connection = new PDO($dsn, $config['username'], $config['password'], $config['options']);
            
            // Log des connexions en développement
            if ($env === 'development') {
                error_log("Database connected successfully at " . date('Y-m-d H:i:s'));
            }
            
        } catch (PDOException $e) {
            // Log l'erreur sans exposer les détails sensibles
            error_log("Database connection failed: " . $e->getMessage());
            throw new PDOException("Erreur de connexion à la base de données");
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    // Méthode pour les transactions
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    public function commit() {
        return $this->connection->commit();
    }
    
    public function rollback() {
        return $this->connection->rollback();
    }
    
    // Vérification de la connexion
    public function isConnected() {
        try {
            $this->connection->query('SELECT 1');
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    // Prévenir le clonage et la désérialisation
    private function __clone() {}
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

// Initialize database instance
$database = Database::getInstance();
?>