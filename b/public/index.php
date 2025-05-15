<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Routes\Router;
use App\Controllers\HomeController;

// Start the session
session_start();

$router = new Router();

// Define routes
$router->get('/', [HomeController::class, 'index']);
$router->get('/about', function() {
    return "About page";
});

$router->run();
?>