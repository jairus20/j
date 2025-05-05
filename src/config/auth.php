<?php
function checkAuth() {
    session_start();
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../login.php');
        exit;
    }
    return $_SESSION['user_id'];
}

function checkRole($requiredRole) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if ($_SESSION['role'] !== $requiredRole) {
        header('Location: ../unauthorized.php');
        exit;
    }
}