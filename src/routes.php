<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/controllers/EnteController.php';
session_start();

// Removed routing switch; simply include homepage view.
require_once __DIR__ . '/views/home.php';
?>
