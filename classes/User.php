<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

class User
{
    private $db;
    private $table = 'users';

    public function __construct()
    {
        require_once __DIR__ . '/../config/database.php';
        $database = Database::getInstance();
        $this->db = $database->getConnection();
    }

    /**
     * User registration
     */
    public function register($userData)
    {
        try {
            // Validate data
            $errors = $this->validateRegistrationData($userData);
            if (!empty($errors)) {
                return ['success' => false, 'message' => reset($errors)];
            }

            // Check if email already exists
            if ($this->emailExists($userData['email'])) {
                return ['success' => false, 'message' => 'Cet email est déjà utilisé'];
            }

            // Prepare data
            $hashedPassword = password_hash($userData['password'], PASSWORD_DEFAULT);

            $sql = "INSERT INTO {$this->table} 
                    (email, password_hash, first_name, last_name, phone, 
                     preferred_language, role, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";

            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                $userData['email'],
                $hashedPassword,
                $userData['first_name'],
                $userData['last_name'],
                $userData['phone'] ?? null,
                $userData['preferred_language'] ?? 'fr',
                'user' // Default role
            ]);

            if ($result) {
                $userId = $this->db->lastInsertId();
                return [
                    'success' => true,
                    'user_id' => $userId,
                    'message' => 'Inscription réussie'
                ];
            }

            return ['success' => false, 'message' => 'Erreur lors de l\'inscription'];

        } catch (PDOException $e) {
            error_log("Registration error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur système'];
        }
    }

    /**
     * Enhanced User login with better debugging
     */
    public function login($email, $password, $rememberMe = false)
    {
        try {
            $email = trim(strtolower($email));
            $password = trim($password);

            if (empty($email) || empty($password)) {
                return [
                    'success' => false,
                    'message' => 'Email et mot de passe requis'
                ];
            }

            // Get user by email (simplified)
            $user = $this->getUserByEmailSimple($email);

            if (!$user || empty($user['password_hash']) || !password_verify($password, $user['password_hash'])) {
                return [
                    'success' => false,
                    'message' => 'Email ou mot de passe incorrect'
                ];
            }

            // Update last login
            $this->updateLastLogin($user['id']);

            // Create user session
            $this->createUserSession($user);

            // Handle "Remember me"
            if ($rememberMe) {
                $this->createRememberToken($user['id']);
            }

            return [
                'success' => true,
                'user' => $this->sanitizeUserData($user),
                'message' => 'Connexion réussie'
            ];

        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erreur système'
            ];
        }
    }

    /**
     * Get user by email with detailed information for debugging
     */
    private function getUserByEmailDetailed($email)
    {
        try {
            $email = trim(strtolower($email));
            
            // First, let's see what columns exist
            $columnCheckSql = "SHOW COLUMNS FROM {$this->table}";
            $columnStmt = $this->db->prepare($columnCheckSql);
            $columnStmt->execute();
            $columns = $columnStmt->fetchAll(PDO::FETCH_COLUMN);
            
            error_log("Available columns: " . implode(', ', $columns));
            
            // Build the select query based on available columns
            $selectColumns = ['id', 'email', 'first_name', 'last_name', 'role', 'created_at'];
            
            if (in_array('password_hash', $columns)) {
                $selectColumns[] = 'password_hash';
            }
            if (in_array('password', $columns)) {
                $selectColumns[] = 'password';
            }
            
            $sql = "SELECT " . implode(', ', $selectColumns) . " 
                    FROM {$this->table} 
                    WHERE LOWER(email) = ? AND (deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00')
                    LIMIT 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$email]);
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                error_log("User found: " . $user['email'] . " (ID: " . $user['id'] . ")");
                error_log("Password hash exists: " . (!empty($user['password_hash']) ? 'YES' : 'NO'));
                if (isset($user['password'])) {
                    error_log("Legacy password exists: " . (!empty($user['password']) ? 'YES' : 'NO'));
                }
            } else {
                error_log("No user found for email: " . $email);
                
                // Debug: Check if user exists with different case
                $debugSql = "SELECT email FROM {$this->table} WHERE email LIKE ? LIMIT 5";
                $debugStmt = $this->db->prepare($debugSql);
                $debugStmt->execute(['%' . str_replace(['%', '_'], ['\%', '\_'], $email) . '%']);
                $similarEmails = $debugStmt->fetchAll(PDO::FETCH_COLUMN);
                
                if (!empty($similarEmails)) {
                    error_log("Similar emails found: " . implode(', ', $similarEmails));
                }
            }
            
            return $user;

        } catch (PDOException $e) {
            error_log("Get user by email error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Migrate old password to new password_hash format
     */
    private function migratePassword($userId, $plainPassword)
    {
        try {
            $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);
            $sql = "UPDATE {$this->table} SET password_hash = ? WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$hashedPassword, $userId]);
            
            error_log("Password migrated for user ID: " . $userId);
        } catch (PDOException $e) {
            error_log("Password migration error: " . $e->getMessage());
        }
    }

    /**
     * User logout
     */
    public function logout()
    {
        // Remove remember token cookie
        if (isset($_COOKIE['remember_token'])) {
            $this->removeRememberToken($_COOKIE['remember_token']);
            setcookie('remember_token', '', time() - 3600, '/');
        }

        // Destroy session
        if (class_exists('SessionManager')) {
            SessionManager::destroy();
        } else {
            session_destroy();
            session_unset();
        }

        return ['success' => true, 'message' => 'Déconnexion réussie'];
    }

    /**
     * Get user by ID
     */
    public function getUserById($id)
    {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE id = ? AND (deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00')";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);

            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            return $user ? $this->sanitizeUserData($user) : null;

        } catch (PDOException $e) {
            error_log("Get user by ID error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get user by email - IMPROVED VERSION
     */
    private function getUserByEmail($email)
    {
        return $this->getUserByEmailDetailed($email);
    }

    /**
     * Check if email exists
     */
    private function emailExists($email)
    {
        try {
            $email = trim(strtolower($email));
            $sql = "SELECT COUNT(*) FROM {$this->table} WHERE LOWER(email) = ? AND (deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00')";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$email]);

            return $stmt->fetchColumn() > 0;

        } catch (PDOException $e) {
            error_log("Email exists check error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create user session
     */
    private function createUserSession($user)
    {
        if (!class_exists('SessionManager')) {
            require_once __DIR__ . '/../config/session.php';
        }

        // Set all necessary user data in session
        SessionManager::set('user_id', $user['id']);
        SessionManager::set('user_email', $user['email']);
        SessionManager::set('user_first_name', $user['first_name']);
        SessionManager::set('user_last_name', $user['last_name']);
        SessionManager::set('user_role', $user['role']);
        SessionManager::set('user_created_at', $user['created_at']);
        
        // Set a flag to indicate user is logged in
        SessionManager::set('is_logged_in', true);
        
        // Regenerate session ID for security
        SessionManager::regenerateId();
    }

    /**
     * Check if user is logged in
     */
    public static function isLoggedIn()
    {
        if (class_exists('SessionManager')) {
            return SessionManager::get('is_logged_in', false);
        }
        return isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true;
    }

    /**
     * Check if user is admin
     */
    public static function isAdmin()
    {
        if (class_exists('SessionManager')) {
            return SessionManager::get('user_role') === 'admin';
        }
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    }

    /**
     * Get current logged in user
     */
    public static function getCurrentUser()
    {
        if (!self::isLoggedIn()) {
            return null;
        }

        if (class_exists('SessionManager')) {
            return [
                'id' => SessionManager::get('user_id'),
                'email' => SessionManager::get('user_email'),
                'name' => SessionManager::get('user_name'),
                'role' => SessionManager::get('user_role')
            ];
        }

        return [
            'id' => $_SESSION['user_id'] ?? null,
            'email' => $_SESSION['user_email'] ?? null,
            'name' => $_SESSION['user_name'] ?? null,
            'role' => $_SESSION['user_role'] ?? null
        ];
    }

    /**
     * Update user profile
     */
    public function updateProfile($userId, $userData)
    {
        try {
            // Validate data
            $errors = $this->validateProfileData($userData);
            if (!empty($errors)) {
                return ['success' => false, 'errors' => $errors];
            }

            $sql = "UPDATE {$this->table} 
                    SET first_name = ?, last_name = ?, phone = ?, 
                        preferred_language = ?, updated_at = NOW() 
                    WHERE id = ?";

            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                $userData['first_name'],
                $userData['last_name'],
                $userData['phone'] ?? null,
                $userData['preferred_language'] ?? 'fr',
                $userId
            ]);

            if ($result) {
                // Update session
                if (class_exists('SessionManager')) {
                    SessionManager::set('user_name', $userData['first_name'] . ' ' . $userData['last_name']);
                } else {
                    $_SESSION['user_name'] = $userData['first_name'] . ' ' . $userData['last_name'];
                }
                return ['success' => true, 'message' => 'Profil mis à jour avec succès'];
            }

            return ['success' => false, 'errors' => ['general' => 'Erreur lors de la mise à jour']];

        } catch (PDOException $e) {
            error_log("Update profile error: " . $e->getMessage());
            return ['success' => false, 'errors' => ['general' => 'Erreur système']];
        }
    }

    /**
     * Change password
     */
    public function changePassword($userId, $currentPassword, $newPassword)
    {
        try {
            // Get user with complete data
            $sql = "SELECT * FROM {$this->table} WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                return ['success' => false, 'error' => 'Utilisateur non trouvé'];
            }

            // Verify current password
            $currentPasswordValid = false;
            if (!empty($user['password_hash']) && password_verify($currentPassword, $user['password_hash'])) {
                $currentPasswordValid = true;
            } elseif (!empty($user['password']) && password_verify($currentPassword, $user['password'])) {
                $currentPasswordValid = true;
            } elseif (!empty($user['password']) && $currentPassword === $user['password']) {
                $currentPasswordValid = true;
            }

            if (!$currentPasswordValid) {
                return ['success' => false, 'error' => 'Mot de passe actuel incorrect'];
            }

            // Validate new password
            if (!$this->isValidPassword($newPassword)) {
                return ['success' => false, 'error' => 'Le mot de passe doit contenir au moins 8 caractères'];
            }

            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

            $sql = "UPDATE {$this->table} SET password_hash = ?, updated_at = NOW() WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$hashedPassword, $userId]);

            if ($result) {
                return ['success' => true, 'message' => 'Mot de passe modifié avec succès'];
            }

            return ['success' => false, 'error' => 'Erreur lors de la modification'];

        } catch (PDOException $e) {
            error_log("Change password error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Erreur système'];
        }
    }

    /**
     * DEBUG: Create test users with known passwords
     */
    public function createTestUsers()
    {
        try {
            $testUsers = [
                [
                    'email' => 'admin@test.com',
                    'password' => 'admin123',
                    'first_name' => 'Admin',
                    'last_name' => 'Test',
                    'role' => 'admin'
                ],
                [
                    'email' => 'user@test.com',
                    'password' => 'user123',
                    'first_name' => 'User',
                    'last_name' => 'Test',
                    'role' => 'user'
                ]
            ];

            foreach ($testUsers as $userData) {
                // Check if user already exists
                if (!$this->emailExists($userData['email'])) {
                    $hashedPassword = password_hash($userData['password'], PASSWORD_DEFAULT);
                    
                    $sql = "INSERT INTO {$this->table} 
                            (email, password_hash, first_name, last_name, role, created_at) 
                            VALUES (?, ?, ?, ?, ?, NOW())";
                    
                    $stmt = $this->db->prepare($sql);
                    $stmt->execute([
                        $userData['email'],
                        $hashedPassword,
                        $userData['first_name'],
                        $userData['last_name'],
                        $userData['role']
                    ]);
                    
                    error_log("Test user created: " . $userData['email']);
                }
            }
            
            return ['success' => true, 'message' => 'Test users created/verified'];
            
        } catch (PDOException $e) {
            error_log("Create test users error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error creating test users'];
        }
    }

    /**
     * Validate registration data
     */
    private function validateRegistrationData($data)
    {
        $errors = [];

        if (empty($data['email'])) {
            $errors['email'] = 'L\'email est requis';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Format d\'email invalide';
        }

        if (empty($data['password'])) {
            $errors['password'] = 'Le mot de passe est requis';
        } elseif (strlen($data['password']) < 8) {
            $errors['password'] = 'Le mot de passe doit contenir au moins 8 caractères';
        }

        if (empty($data['password_confirm'])) {
            $errors['password_confirm'] = 'La confirmation du mot de passe est requise';
        } elseif ($data['password'] !== $data['password_confirm']) {
            $errors['password_confirm'] = 'Les mots de passe ne correspondent pas';
        }

        if (empty($data['first_name'])) {
            $errors['first_name'] = 'Le prénom est requis';
        } elseif (strlen($data['first_name']) < 2) {
            $errors['first_name'] = 'Le prénom doit contenir au moins 2 caractères';
        }

        if (empty($data['last_name'])) {
            $errors['last_name'] = 'Le nom est requis';
        } elseif (strlen($data['last_name']) < 2) {
            $errors['last_name'] = 'Le nom doit contenir au moins 2 caractères';
        }

        if (!empty($data['phone']) && !preg_match('/^[0-9+\-\s\(\)]+$/', $data['phone'])) {
            $errors['phone'] = 'Format de téléphone invalide';
        }

        return $errors;
    }

    /**
     * Validate profile data
     */
    private function validateProfileData($data)
    {
        $errors = [];

        if (empty($data['first_name'])) {
            $errors['first_name'] = 'Le prénom est requis';
        } elseif (strlen($data['first_name']) < 2) {
            $errors['first_name'] = 'Le prénom doit contenir au moins 2 caractères';
        }

        if (empty($data['last_name'])) {
            $errors['last_name'] = 'Le nom est requis';
        } elseif (strlen($data['last_name']) < 2) {
            $errors['last_name'] = 'Le nom doit contenir au moins 2 caractères';
        }

        if (!empty($data['phone']) && !preg_match('/^[0-9+\-\s\(\)]+$/', $data['phone'])) {
            $errors['phone'] = 'Format de téléphone invalide';
        }

        return $errors;
    }

    /**
     * Validate password
     */
    private function isValidPassword($password)
    {
        return strlen($password) >= 8;
    }

    /**
     * Sanitize user data (remove sensitive info)
     */
    private function sanitizeUserData($user)
    {
        unset($user['password_hash']);
        unset($user['password']);
        unset($user['verification_token']);
        unset($user['reset_token']);
        unset($user['reset_token_expires']);
        return $user;
    }

    /**
     * Update last login timestamp
     */
    private function updateLastLogin($userId)
    {
        try {
            $sql = "UPDATE {$this->table} SET last_login = NOW() WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
        } catch (PDOException $e) {
            error_log("Update last login error: " . $e->getMessage());
        }
    }

    /**
     * Create remember token
     */
    private function createRememberToken($userId)
    {
        try {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+30 days'));

            // Check if table exists first
            try {
                // Remove old tokens
                $sql = "DELETE FROM user_remember_tokens WHERE user_id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$userId]);

                // Create new token
                $sql = "INSERT INTO user_remember_tokens (user_id, token, expires_at) VALUES (?, ?, ?)";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$userId, hash('sha256', $token), $expires]);

                // Set cookie
                setcookie('remember_token', $token, strtotime('+30 days'), '/', '', false, true);
            } catch (PDOException $e) {
                // Table might not exist, skip remember token functionality
                error_log("Remember token table not available: " . $e->getMessage());
            }

        } catch (Exception $e) {
            error_log("Create remember token error: " . $e->getMessage());
        }
    }

    /**
     * Remove remember token
     */
    private function removeRememberToken($token)
    {
        try {
            $sql = "DELETE FROM user_remember_tokens WHERE token = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([hash('sha256', $token)]);
        } catch (PDOException $e) {
            error_log("Remove remember token error: " . $e->getMessage());
        }
    }

    // Add a new simple method for fetching user by email
    private function getUserByEmailSimple($email)
    {
        try {
            $sql = "SELECT id, email, first_name, last_name, role, created_at, password_hash FROM {$this->table} WHERE LOWER(email) = ? LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([strtolower($email)]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get user by email error: " . $e->getMessage());
            return null;
        }
    }
}
?>