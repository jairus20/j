<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../src/config/config.php';
require_once __DIR__ . '/../src/config/database.php';

// Check if logged in and if admin
if (!isset($_SESSION['user_id'])) {
    header('Location: ../public/login.php');
    exit;
}
if ($_SESSION['role'] !== 'ADMIN') {
    header('Location: ../public/unauthorized.php');
    exit;
}