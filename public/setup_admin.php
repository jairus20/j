<?php
require_once __DIR__ . '/../src/config/database.php';


$username = 'MuSgUs'; 
$password = 'S4n4~9s;fYO1'; 
$email    = 'admin@example.com';
$role     = 'ADMIN'; 

// Hash the password
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

// Check if admin already exists
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE username = ?");
$stmt->execute([$username]);
if ($stmt->rowCount() > 0) {
    echo "Admin user already exists.";
    exit;
}

// Insert the admin user
$insert = $pdo->prepare("INSERT INTO usuarios (username, password_hash, tipo_usuario, email) VALUES (?, ?, ?, ?)");
if ($insert->execute([$username, $passwordHash, $role, $email])) {
    echo "Admin created successfully.";
} else {
    echo "Failed to create admin.";
}
?>