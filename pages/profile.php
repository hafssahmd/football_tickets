<?php
session_start();
require_once '../classes/User.php';

$user = new User();
$user->logout();

// Redirect to login page or homepage
header('Location: login.php');
exit();
