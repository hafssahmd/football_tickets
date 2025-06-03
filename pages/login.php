<?php
require_once '../config/session.php';
require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../classes/User.php';

// Initialize session
SessionManager::init();

$error = '';
$success = '';
$debug_info = [];

// Redirect if already logged in
if (SessionManager::has('user_id')) {
    header('Location: ' . BASE_URL . 'index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $rememberMe = isset($_POST['remember']);

    // Debug information
    $debug_info[] = "Email reçu: " . $email;
    $debug_info[] = "Password length: " . strlen($password);
    $debug_info[] = "Remember me: " . ($rememberMe ? 'Oui' : 'Non');

    if (empty($email) || empty($password)) {
        $error = 'Veuillez remplir tous les champs';
    } else {
        try {
            // Create User instance and attempt login
            $user = new User();
            $loginResult = $user->login($email, $password, $rememberMe);

            $debug_info[] = "Résultat login: " . json_encode($loginResult);

            if ($loginResult['success']) {
                $success = $loginResult['message'];
                $debug_info[] = "Login réussi - redirection en cours";

                // Determine redirect URL
                $redirect = $_GET['redirect'] ?? BASE_URL . 'index.php';

                // If user is admin, redirect to admin panel
                if (isset($loginResult['user']['role']) && $loginResult['user']['role'] === 'admin') {
                    $redirect = BASE_URL . 'admin/index.php';
                }

                // Redirect after 2 seconds to show success message
                header("Refresh: 2; URL=" . $redirect);
            } else {
                $error = $loginResult['message'] ?? 'Erreur de connexion';
                $debug_info[] = "Login échoué: " . $error;
            }

        } catch (Exception $e) {
            $error = 'Erreur système: ' . $e->getMessage();
            $debug_info[] = "Exception: " . $e->getMessage();
        }
    }
}

// Show debug info if requested
$showDebug = isset($_GET['debug']) || (defined('ENVIRONMENT') && ENVIRONMENT === 'development');
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Football Tickets Maroc</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <?php if (file_exists('../assets/css/auth.css')): ?>
        <link rel="stylesheet" href="../assets/css/auth.css">
    <?php endif; ?>
    <style>
        /* Enhanced CSS with better error/success display */
        .auth-main {
            min-height: 80vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 2rem 1rem;
        }

        .auth-container {
            max-width: 500px;
            width: 100%;
        }

        .auth-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            margin-bottom: 1rem;
        }

        .debug-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            font-family: monospace;
            font-size: 0.8rem;
            border: 1px solid #dee2e6;
        }

        .debug-card h3 {
            margin-top: 0;
            color: #495057;
        }

        .debug-list {
            list-style: none;
            padding: 0;
        }

        .debug-list li {
            padding: 0.25rem 0;
            border-bottom: 1px solid #e9ecef;
        }

        .auth-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .auth-header h1 {
            color: #333;
            margin-bottom: 0.5rem;
        }

        .auth-header p {
            color: #666;
            font-size: 0.9rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e1e1e1;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s;
            box-sizing: border-box;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }

        .password-toggle {
            position: absolute;
            right: 10px;
            top: 38px;
            background: none;
            border: none;
            cursor: pointer;
            color: #666;
            font-size: 1.2rem;
        }

        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .forgot-link {
            color: #667eea;
            text-decoration: none;
        }

        .forgot-link:hover {
            text-decoration: underline;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-full {
            width: 100%;
        }

        .btn-social {
            width: 100%;
            margin-bottom: 0.5rem;
            background: white;
            border: 2px solid #e1e1e1;
            color: #333;
        }

        .btn-social:hover {
            background: #f8f9fa;
        }

        .auth-footer {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid #e1e1e1;
        }

        .auth-footer a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }

        .auth-footer a:hover {
            text-decoration: underline;
        }

        .social-login {
            margin-top: 1.5rem;
        }

        .divider {
            text-align: center;
            margin: 1.5rem 0;
            position: relative;
        }

        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #e1e1e1;
        }

        .divider span {
            background: white;
            padding: 0 1rem;
            color: #666;
            font-size: 0.9rem;
        }

        .alert {
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            border: 1px solid;
        }

        .alert-error {
            background: #fee;
            color: #c33;
            border-color: #fcc;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }

        .test-credentials {
            background: #e3f2fd;
            border: 1px solid #bbdefb;
            border-radius: 5px;
            padding: 1rem;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .test-credentials:hover {
            background: #e1f5fe;
        }

        .test-credentials h4 {
            margin-top: 0;
            color: #1976d2;
        }

        .loading {
            display: none;
            text-align: center;
            margin-top: 1rem;
        }

        .spinner {
            border: 2px solid #f3f3f3;
            border-top: 2px solid #667eea;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
            display: inline-block;
            margin-right: 0.5rem;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        /* Additional CSS for enhanced interactions */
        .form-group.focused input {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-group.has-value label {
            color: #667eea;
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .alert {
            opacity: 1;
            transition: opacity 0.3s ease;
        }

        @media (max-width: 768px) {
            .auth-main {
                padding: 1rem;
            }

            .auth-card {
                padding: 1.5rem;
            }

            .test-credentials {
                font-size: 0.8rem;
            }

            .form-options {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }
        }

        /* Loading animation enhancement */
        .loading {
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        /* Test credentials hover effect */
        .test-credentials:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .user-dropdown-menu, .cart-dropdown-menu { display: none; }
        .user-dropdown-menu.show, .cart-dropdown-menu.show { display: block; }
    </style>
</head>

<body>
    <?php
    if (file_exists('../includes/header.php')):
        include '../includes/header.php';
    endif;
    ?>

    <main class="auth-main">
        <div class="auth-container">
            <div class="auth-card">
                <div class="auth-header">
                    <h1>Connexion</h1>
                    <p>Connectez-vous pour réserver vos billets</p>
                </div>

                <!-- Test Credentials Info -->
                <div class="test-credentials" onclick="fillTestCredentials()"
                    title="Cliquez pour remplir automatiquement">
                    <h4>🔑 Identifiants de test</h4>
                    <p><strong>Admin:</strong> admin@test.com / admin123</p>
                    <p><strong>Utilisateur:</strong> user@test.com / user123</p>
                    <small>Cliquez ici pour remplir automatiquement</small>
                </div>

                <!-- Error/Success Messages -->
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <strong>Erreur:</strong> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <strong>Succès:</strong> <?= htmlspecialchars($success) ?>
                        <div class="loading">
                            <div class="spinner"></div>
                            Redirection en cours...
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Login Form -->
                <form method="POST" action="" id="loginForm">
                    <div class="form-group">
                        <label for="email">Adresse email</label>
                        <input type="email" id="email" name="email"
                            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="votre@email.com"
                            required autocomplete="email">
                    </div>

                    <div class="form-group">
                        <label for="password">Mot de passe</label>
                        <input type="password" id="password" name="password" placeholder="Votre mot de passe" required
                            autocomplete="current-password">
                        <button type="button" class="password-toggle" onclick="togglePassword()">
                            👁️
                        </button>
                    </div>

                    <div class="form-options">
                        <label class="checkbox-label">
                            <input type="checkbox" name="remember" id="remember">
                            Se souvenir de moi
                        </label>
                        <a href="forgot-password.php" class="forgot-link">Mot de passe oublié ?</a>
                    </div>

                    <button type="submit" class="btn btn-primary btn-full">
                        Se connecter
                    </button>
                </form>

                <!-- Social Login -->
                <div class="social-login">
                    <div class="divider">
                        <span>ou</span>
                    </div>

                    <button type="button" class="btn btn-social" onclick="socialLogin('google')">
                        <svg width="18" height="18" viewBox="0 0 24 24">
                            <path fill="#4285F4"
                                d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
                            <path fill="#34A853"
                                d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
                            <path fill="#FBBC05"
                                d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" />
                            <path fill="#EA4335"
                                d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" />
                        </svg>
                        Continuer avec Google
                    </button>

                    <button type="button" class="btn btn-social" onclick="socialLogin('facebook')">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="#1877F2">
                            <path
                                d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                        </svg>
                        Continuer avec Facebook
                    </button>
                </div>

                <!-- Auth Footer -->
                <div class="auth-footer">
                    <p>Pas encore de compte ? <a href="register.php">Créer un compte</a></p>
                    <p><a href="../index.php">← Retour à l'accueil</a></p>
                </div>
            </div>

            <!-- Debug Information (shown only in development) -->
            <?php if ($showDebug && !empty($debug_info)): ?>
                <div class="debug-card">
                    <h3>🐛 Informations de débogage</h3>
                    <ul class="debug-list">
                        <?php foreach ($debug_info as $info): ?>
                            <li><?= htmlspecialchars($info) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <hr>
                    <h4>Variables de session:</h4>
                    <pre><?= htmlspecialchars(print_r($_SESSION ?? [], true)) ?></pre>

                    <h4>Données POST:</h4>
                    <pre><?= htmlspecialchars(print_r($_POST ?? [], true)) ?></pre>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php
    if (file_exists('../includes/footer.php')):
        include '../includes/footer.php';
    endif;
    ?>

    <script>
        // Toggle password visibility
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleButton = document.querySelector('.password-toggle');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleButton.textContent = '🙈';
            } else {
                passwordInput.type = 'password';
                toggleButton.textContent = '👁️';
            }
        }

        // Fill test credentials
        function fillTestCredentials() {
            const isAdmin = confirm('Voulez-vous utiliser les identifiants Admin ?\n\nOK = Admin (admin@test.com)\nAnnuler = Utilisateur (user@test.com)');

            if (isAdmin) {
                document.getElementById('email').value = 'admin@test.com';
                document.getElementById('password').value = 'admin123';
            } else {
                document.getElementById('email').value = 'user@test.com';
                document.getElementById('password').value = 'user123';
            }

            // Focus on submit button
            document.querySelector('.btn-primary').focus();
        }

        // Social login handlers
        function socialLogin(provider) {
            alert(`Connexion ${provider} - Fonctionnalité à implémenter`);
            // TODO: Implement actual social login
            /*
            switch(provider) {
                case 'google':
                    window.location.href = 'auth/google.php';
                    break;
                case 'facebook':
                    window.location.href = 'auth/facebook.php';
                    break;
            }
            */
        }

        // Form validation
        document.getElementById('loginForm').addEventListener('submit', function (e) {
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;

            if (!email || !password) {
                e.preventDefault();
                alert('Veuillez remplir tous les champs');
                return false;
            }

            if (!isValidEmail(email)) {
                e.preventDefault();
                alert('Veuillez saisir une adresse email valide');
                document.getElementById('email').focus();
                return false;
            }

            // Show loading state
            const submitBtn = document.querySelector('.btn-primary');
            const originalText = submitBtn.textContent;
            submitBtn.innerHTML = '<div class="spinner"></div> Connexion...';
            submitBtn.disabled = true;

            // Re-enable button after 10 seconds (failsafe)
            setTimeout(() => {
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            }, 10000);
        });

        // Email validation helper
        function isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }

        // Show loading animation if success message is displayed
        <?php if ($success): ?>
            document.addEventListener('DOMContentLoaded', function () {
                const loading = document.querySelector('.loading');
                if (loading) {
                    loading.style.display = 'block';
                }
            });
        <?php endif; ?>

        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function () {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                if (!alert.classList.contains('alert-success')) {
                    setTimeout(() => {
                        alert.style.opacity = '0';
                        setTimeout(() => alert.remove(), 300);
                    }, 5000);
                }
            });
        });

        // Handle remember me checkbox persistence
        document.addEventListener('DOMContentLoaded', function () {
            const rememberCheckbox = document.getElementById('remember');
            const emailInput = document.getElementById('email');

            // Load remembered email if available
            const rememberedEmail = localStorage.getItem('rememberedEmail');
            if (rememberedEmail && !emailInput.value) {
                emailInput.value = rememberedEmail;
                rememberCheckbox.checked = true;
            }

            // Save/remove email based on checkbox
            rememberCheckbox.addEventListener('change', function () {
                if (this.checked && emailInput.value) {
                    localStorage.setItem('rememberedEmail', emailInput.value);
                } else {
                    localStorage.removeItem('rememberedEmail');
                }
            });

            // Update stored email when changed
            emailInput.addEventListener('change', function () {
                if (rememberCheckbox.checked) {
                    localStorage.setItem('rememberedEmail', this.value);
                }
            });
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function (e) {
            // Alt+T to fill test credentials
            if (e.altKey && e.key.toLowerCase() === 't') {
                e.preventDefault();
                fillTestCredentials();
            }

            // Alt+A for admin credentials
            if (e.altKey && e.key.toLowerCase() === 'a') {
                e.preventDefault();
                document.getElementById('email').value = 'admin@test.com';
                document.getElementById('password').value = 'admin123';
            }

            // Alt+U for user credentials
            if (e.altKey && e.key.toLowerCase() === 'u') {
                e.preventDefault();
                document.getElementById('email').value = 'user@test.com';
                document.getElementById('password').value = 'user123';
            }
        });

        // Add visual feedback for form interactions
        document.querySelectorAll('input').forEach(input => {
            input.addEventListener('focus', function () {
                this.parentElement.classList.add('focused');
            });

            input.addEventListener('blur', function () {
                this.parentElement.classList.remove('focused');
                if (this.value) {
                    this.parentElement.classList.add('has-value');
                } else {
                    this.parentElement.classList.remove('has-value');
                }
            });
        });

        // Place this in your main JS file or in a <script> tag before </body>
        document.addEventListener('DOMContentLoaded', function() {
            // User dropdown
            const userToggle = document.querySelector('.user-toggle');
            const userDropdown = document.querySelector('.user-dropdown-menu');
            if (userToggle && userDropdown) {
                userToggle.addEventListener('click', function(e) {
                    e.stopPropagation();
                    userDropdown.classList.toggle('show');
                });
                document.addEventListener('click', function() {
                    userDropdown.classList.remove('show');
                });
            }

            // Cart dropdown
            const cartToggle = document.getElementById('cart-toggle');
            const cartDropdown = document.getElementById('cart-dropdown');
            if (cartToggle && cartDropdown) {
                cartToggle.addEventListener('click', function(e) {
                    e.stopPropagation();
                    cartDropdown.classList.toggle('show');
                });
                document.addEventListener('click', function() {
                    cartDropdown.classList.remove('show');
                });
            }
        });
    </script>
</body>

</html>