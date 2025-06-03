<?php
// debug_login.php - Placez ce fichier dans le dossier pages/
session_start();

require_once   'C:/xampp/htdocs/football_tickets/config/constants.php';
require_once  'C:/xampp/htdocs/football_tickets/classes/User.php';

// VOS DONNÉES DE TEST - MODIFIEZ ICI
$test_email = 'testphpticket@gmail.com';
$test_password = 'password';

echo "<!DOCTYPE html>";
echo "<html><head><title>Debug Login</title>";
echo "<style>body{font-family:Arial;margin:40px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>";
echo "</head><body>";
echo "<h1>🔍 Diagnostic de connexion - Football Tickets</h1>";
echo "<hr>";

try {
    // 1. Test de la base de données
    echo "<h2>1. Test de connexion à la base de données</h2>";
    $db = Database::getInstance()->getConnection();
    if ($db) {
        echo "<p class='success'>✅ Connexion à la base de données OK</p>";
    } else {
        echo "<p class='error'>❌ Erreur de connexion à la base de données</p>";
        exit();
    }

    // 2. Vérifier la structure de la table
    echo "<h2>2. Structure de la table users</h2>";
    $stmt = $db->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<table border='1' style='border-collapse:collapse;'>";
    echo "<tr><th>Colonne</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";

    $hasPasswordHash = false;
    $hasPassword = false;

    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . $column['Field'] . "</td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . $column['Key'] . "</td>";
        echo "<td>" . $column['Default'] . "</td>";
        echo "</tr>";

        if ($column['Field'] === 'password_hash')
            $hasPasswordHash = true;
        if ($column['Field'] === 'password')
            $hasPassword = true;
    }
    echo "</table>";

    echo "<p class='info'>📋 password_hash: " . ($hasPasswordHash ? "✅ Présent" : "❌ Absent") . "</p>";
    echo "<p class='info'>📋 password: " . ($hasPassword ? "✅ Présent" : "❌ Absent") . "</p>";

    // 3. Rechercher l'utilisateur
    echo "<h2>3. Recherche de l'utilisateur</h2>";
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$test_email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        echo "<p class='success'>✅ Utilisateur trouvé</p>";
        echo "<ul>";
        echo "<li><strong>ID:</strong> " . $user['id'] . "</li>";
        echo "<li><strong>Email:</strong> " . $user['email'] . "</li>";
        echo "<li><strong>Prénom:</strong> " . ($user['first_name'] ?? 'N/A') . "</li>";
        echo "<li><strong>Nom:</strong> " . ($user['last_name'] ?? 'N/A') . "</li>";

        if (isset($user['password_hash'])) {
            echo "<li><strong>Password_hash:</strong> " . substr($user['password_hash'], 0, 30) . "... (longueur: " . strlen($user['password_hash']) . ")</li>";
        }

        if (isset($user['password'])) {
            echo "<li><strong>Password:</strong> " . substr($user['password'], 0, 30) . "... (longueur: " . strlen($user['password']) . ")</li>";
        }
        echo "</ul>";
    } else {
        echo "<p class='error'>❌ Aucun utilisateur trouvé avec l'email: " . $test_email . "</p>";

        // Afficher tous les emails disponibles
        echo "<h3>📋 Emails disponibles dans la base:</h3>";
        $stmt = $db->query("SELECT email FROM users ORDER BY id DESC LIMIT 10");
        $emails = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($emails)) {
            echo "<p class='error'>❌ Aucun utilisateur dans la base de données</p>";
        } else {
            echo "<ul>";
            foreach ($emails as $email) {
                echo "<li>" . htmlspecialchars($email) . "</li>";
            }
            echo "</ul>";
        }
        exit();
    }

    // 4. Test des mots de passe
    echo "<h2>4. Test de vérification du mot de passe</h2>";
    echo "<p><strong>Mot de passe testé:</strong> '" . htmlspecialchars($test_password) . "'</p>";

    $tests = [
        'password_verify avec password_hash' => isset($user['password_hash']) ? password_verify($test_password, $user['password_hash']) : false,
        'password_verify avec password' => isset($user['password']) ? password_verify($test_password, $user['password']) : false,
        'Comparaison directe' => isset($user['password']) ? ($test_password === $user['password']) : false,
        'MD5' => isset($user['password']) ? (md5($test_password) === $user['password']) : false,
        'SHA1' => isset($user['password']) ? (sha1($test_password) === $user['password']) : false,
    ];

    foreach ($tests as $method => $result) {
        $status = $result ? "<span class='success'>✅ SUCCÈS</span>" : "<span class='error'>❌ ÉCHEC</span>";
        echo "<p><strong>" . $method . ":</strong> " . $status . "</p>";
    }

    // 5. Test avec la classe User
    echo "<h2>5. Test avec la classe User</h2>";
    $userClass = new User();

    echo "<h3>Méthode debugLogin():</h3>";
    $userClass->debugLogin($test_email, $test_password);

    echo "<h3>Méthode login() complète:</h3>";
    $loginResult = $userClass->login($test_email, $test_password);

    echo "<div style='background: #f5f5f5; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h4>Résultat de login():</h4>";
    echo "<pre>";
    print_r($loginResult);
    echo "</pre>";
    echo "</div>";

    if ($loginResult['success']) {
        echo "<p class='success'>✅ Login réussi avec la classe User!</p>";
    } else {
        echo "<p class='error'>❌ Login échoué: " . ($loginResult['message'] ?? 'Erreur inconnue') . "</p>";
    }

    // 6. Test des sessions
    echo "<h2>6. État des sessions</h2>";
    if (session_status() === PHP_SESSION_ACTIVE) {
        echo "<p class='success'>✅ Session active</p>";
        echo "<h3>Données de session:</h3>";
        echo "<div style='background: #f5f5f5; padding: 15px; border-radius: 5px;'>";
        echo "<pre>";
        foreach ($_SESSION as $key => $value) {
            echo htmlspecialchars($key) . " = " . htmlspecialchars(print_r($value, true)) . "\n";
        }
        echo "</pre>";
        echo "</div>";

        // Test des méthodes statiques
        echo "<h3>Tests des méthodes statiques:</h3>";
        echo "<p><strong>User::isLoggedIn():</strong> " . (User::isLoggedIn() ? "✅ OUI" : "❌ NON") . "</p>";
        echo "<p><strong>User::isAdmin():</strong> " . (User::isAdmin() ? "✅ OUI" : "❌ NON") . "</p>";

        $currentUser = User::getCurrentUser();
        if ($currentUser) {
            echo "<h3>Utilisateur actuel:</h3>";
            echo "<div style='background: #f5f5f5; padding: 15px; border-radius: 5px;'>";
            echo "<pre>";
            print_r($currentUser);
            echo "</pre>";
            echo "</div>";
        }
    } else {
        echo "<p class='error'>❌ Aucune session active</p>";
    }

    // 7. Test de récupération par ID
    if (isset($user['id'])) {
        echo "<h2>7. Test getUserById()</h2>";
        $userById = $userClass->getUserById($user['id']);

        if ($userById) {
            echo "<p class='success'>✅ Utilisateur récupéré par ID</p>";
            echo "<div style='background: #f5f5f5; padding: 15px; border-radius: 5px;'>";
            echo "<h4>Données utilisateur (nettoyées):</h4>";
            echo "<pre>";
            print_r($userById);
            echo "</pre>";
            echo "</div>";
        } else {
            echo "<p class='error'>❌ Impossible de récupérer l'utilisateur par ID</p>";
        }
    }

    // 8. Test de différents formats de mot de passe
    echo "<h2>8. Tests avancés de mots de passe</h2>";

    $testPasswords = [
        $test_password,
        trim($test_password),
        strtolower($test_password),
        strtoupper($test_password),
        $test_password . " ", // avec espace à la fin
        " " . $test_password, // avec espace au début
    ];

    echo "<h3>Test avec différentes variantes du mot de passe:</h3>";
    foreach ($testPasswords as $index => $testPwd) {
        $variant = "Variante " . ($index + 1) . " ('" . htmlspecialchars($testPwd) . "')";

        $testResult = false;
        if (isset($user['password_hash'])) {
            $testResult = password_verify($testPwd, $user['password_hash']);
        } elseif (isset($user['password'])) {
            $testResult = ($testPwd === $user['password']) ||
                (md5($testPwd) === $user['password']) ||
                (sha1($testPwd) === $user['password']) ||
                password_verify($testPwd, $user['password']);
        }

        $status = $testResult ? "<span class='success'>✅</span>" : "<span class='error'>❌</span>";
        echo "<p><strong>" . $variant . ":</strong> " . $status . "</p>";
    }

    // 9. Recommandations
    echo "<h2>9. 🎯 Recommandations</h2>";
    echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px; border-left: 4px solid #2196f3;'>";

    if (!$hasPasswordHash && $hasPassword) {
        echo "<p><strong>⚠️ SÉCURITÉ:</strong> Votre base utilise le champ 'password' au lieu de 'password_hash'. Considérez migrer vers password_hash avec password_hash().</p>";
    }

    if ($loginResult['success']) {
        echo "<p class='success'><strong>✅ SUCCÈS:</strong> La connexion fonctionne correctement!</p>";
        echo "<p><strong>Prochaines étapes:</strong></p>";
        echo "<ul>";
        echo "<li>Vérifiez que votre page de connexion utilise les mêmes données de test</li>";
        echo "<li>Supprimez ce fichier de diagnostic après utilisation</li>";
        echo "<li>Testez la connexion dans l'interface utilisateur</li>";
        echo "</ul>";
    } else {
        echo "<p class='error'><strong>❌ PROBLÈME:</strong> La connexion ne fonctionne pas.</p>";
        echo "<p><strong>Actions à effectuer:</strong></p>";
        echo "<ul>";
        echo "<li>Vérifiez que l'email de test existe dans la base</li>";
        echo "<li>Vérifiez que le mot de passe de test est correct</li>";
        echo "<li>Modifiez les variables \$test_email et \$test_password en haut de ce fichier</li>";
        echo "<li>Si nécessaire, créez un utilisateur de test dans votre base</li>";
        echo "</ul>";
    }

    echo "</div>";

    // 10. SQL pour créer un utilisateur de test
    echo "<h2>10. 🛠️ Créer un utilisateur de test</h2>";
    echo "<div style='background: #f5f5f5; padding: 15px; border-radius: 5px;'>";
    echo "<p>Si vous voulez créer un utilisateur de test, utilisez ce SQL :</p>";

    $hashedTestPassword = password_hash($test_password, PASSWORD_DEFAULT);

    echo "<textarea style='width: 100%; height: 120px; font-family: monospace;' readonly>";
    echo "-- Créer un utilisateur de test\n";
    echo "INSERT INTO users (\n";
    echo "    email, \n";
    if ($hasPasswordHash) {
        echo "    password_hash, \n";
    } else {
        echo "    password, \n";
    }
    echo "    first_name, \n";
    echo "    last_name, \n";
    echo "    role, \n";
    echo "    is_verified, \n";
    echo "    created_at\n";
    echo ") VALUES (\n";
    echo "    '" . $test_email . "',\n";
    if ($hasPasswordHash) {
        echo "    '" . $hashedTestPassword . "',\n";
    } else {
        echo "    '" . $test_password . "', -- OU utilisez MD5/SHA1 selon votre système\n";
    }
    echo "    'Test',\n";
    echo "    'User',\n";
    echo "    'user',\n";
    echo "    1,\n";
    echo "    NOW()\n";
    echo ");";
    echo "</textarea>";
    echo "</div>";

    // 11. Informations système
    echo "<h2>11. 📊 Informations système</h2>";
    echo "<div style='background: #f5f5f5; padding: 15px; border-radius: 5px;'>";
    echo "<p><strong>Version PHP:</strong> " . phpversion() . "</p>";
    echo "<p><strong>Extensions:</strong> " . (extension_loaded('pdo') ? "✅ PDO" : "❌ PDO") . " | " . (extension_loaded('pdo_mysql') ? "✅ PDO_MySQL" : "❌ PDO_MySQL") . "</p>";
    echo "<p><strong>Session ID:</strong> " . session_id() . "</p>";
    echo "<p><strong>Date/Heure:</strong> " . date('Y-m-d H:i:s') . "</p>";
    echo "<p><strong>Timezone:</strong> " . date_default_timezone_get() . "</p>";
    echo "</div>";

} catch (Exception $e) {
    echo "<div style='background: #ffebee; padding: 15px; border-radius: 5px; border-left: 4px solid #f44336;'>";
    echo "<h2>❌ ERREUR CRITIQUE</h2>";
    echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Fichier:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Ligne:</strong> " . $e->getLine() . "</p>";
    echo "<p><strong>Trace:</strong></p>";
    echo "<pre style='font-size: 12px; overflow: auto;'>";
    echo htmlspecialchars($e->getTraceAsString());
    echo "</pre>";
    echo "</div>";
}

echo "<hr>";
echo "<div style='background: #fff3e0; padding: 15px; border-radius: 5px; margin-top: 20px;'>";
echo "<h3>⚠️ IMPORTANT - SÉCURITÉ</h3>";
echo "<p><strong style='color: red;'>SUPPRIMEZ CE FICHIER APRÈS UTILISATION !</strong></p>";
echo "<p>Ce fichier contient des informations sensibles et ne doit jamais rester sur un serveur de production.</p>";
echo "<p>Pour supprimer ce fichier : <code>rm pages/debug_login.php</code></p>";
echo "</div>";

echo "</body></html>";
?>