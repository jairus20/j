<?php
require_once __DIR__ . '/../src/config/config.php';
require_once 'admin_auth.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Administrativo - <?php echo APP_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/bootstrap.min.css">
    <!-- Admin CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/admin.css">
    <!-- BoxIcons CSS -->
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="<?php echo BASE_URL; ?>js/admin.js"></script>
</head>
<body>
    <!-- SIDEBAR -->
    <section id="sidebar" class="<?php echo isset($_COOKIE['sidebarState']) && $_COOKIE['sidebarState'] === 'collapsed' ? 'collapsed' : ''; ?>">
        <a href="#" class="brand">
            <i class='bx bx-building-house'></i>
            <span class="text">Admin UI</span>
        </a>
        <ul class="side-menu top">
            <li class="<?php echo basename($_SERVER['PHP_SELF']) === 'admin_dashboard.php' ? 'active' : ''; ?>">
                <a href="admin_dashboard.php">
                    <i class='bx bxs-dashboard'></i>
                    <span class="text">Dashboard</span>
                </a>
            </li>
            <li class="<?php echo basename($_SERVER['PHP_SELF']) === 'admin_ente_management.php' ? 'active' : ''; ?>">
                <a href="admin_ente_management.php">
                    <i class='bx bxs-group'></i>
                    <span class="text">Gestión de Entes</span>
                </a>
            </li>
            <li class="<?php echo basename($_SERVER['PHP_SELF']) === 'admin_actividades_management.php' ? 'active' : ''; ?>">
                <a href="admin_actividades_management.php">
                    <i class='bx bxs-calendar'></i>
                    <span class="text">Gestión de Actividades</span>
                </a>
            </li>
            <li class="<?php echo basename($_SERVER['PHP_SELF']) === 'admin_user_management.php' ? 'active' : ''; ?>">
                <a href="admin_user_management.php">
                    <i class='bx bxs-user'></i>
                    <span class="text">Gestión de Usuarios</span>
                </a>
            </li>
        </ul>
        <ul class="side-menu">
            <li>
                <a href="<?php echo BASE_URL; ?>logout.php" class="logout">
                    <i class='bx bx-log-out'></i>
                    <span class="text">Cerrar Sesión</span>
                </a>
            </li>
        </ul>
    </section>

    <!-- CONTENT -->
    <section id="content">
        <nav>
            <button type="button" id="sidebar-toggle" class="btn">
                <i class='bx bx-menu'></i>
            </button>
            <div class="nav-content">
                <input type="checkbox" id="switch-mode" hidden>
                <label for="switch-mode" class="switch-mode"></label>
                <a href="#" class="profile">
                    <img src="<?php echo BASE_URL; ?>img/default-avatar.png" alt="Profile">
                </a>
            </div>
        </nav>

        <main>
            <div class="container-fluid">