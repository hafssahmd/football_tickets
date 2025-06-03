<?php
// scripts/migrate_passwords.php
require_once __DIR__ . '/../config/database.php';

$pdo = Database::getInstance()->getConnection();
$table = 'users';

// Fetch all users
$sql = "SELECT id, email, password_hash FROM $table";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($users as $user) {
    $id = $user['id'];
    $email = $user['email'];
    $hash = $user['password_hash'];

    if (!empty($hash)) {
        echo "[OK] $email\n";
    } else {
        echo "[NO PASSWORD HASH] $email\n";
    }
}

echo "Migration check complete.\n";

$email = 'testphpticket@gmail.com';
$newPassword = 'yournewpassword';
$hash = password_hash($newPassword, PASSWORD_DEFAULT);
$stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
$stmt->execute([$hash, $email]);
echo "Password reset for $email\n";

// Remove or comment out these lines:
// $sql = "SELECT email, deleted_at FROM $table WHERE email = ?";
// $stmt = $pdo->prepare($sql);
// $stmt->execute([$email]);
// $result = $stmt->fetch(PDO::FETCH_ASSOC);

// if ($result) {
//     echo "User $email exists in the database.\n";
//     if ($result['deleted_at'] !== null && $result['deleted_at'] !== '0000-00-00 00:00:00') {
//         echo "User $email is marked as deleted.\n";
//     } else {
//         echo "User $email is not marked as deleted.\n";
//     }
// } else {
//     echo "User $email does not exist in the database.\n";
// } 