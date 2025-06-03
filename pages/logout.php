<?php
require_once '../config/session.php';
require_once '../classes/User.php';

// Initialize session
SessionManager::init();

// Create User instance and logout
$user = new User();
$logoutResult = $user->logout();

// Redirect to home page
header('Location: ' . BASE_URL . 'index.php');
exit();
