<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title><?php echo APP_NAME; ?></title>

  <!-- Favicon -->
  <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>../public/img/LOGO UAndina.png">

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  
  <!-- Bootstrap CSS -->
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

  <!-- Custom CSS -->
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>../public/css/header.css">
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>../public/css/index.css">
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>../public/css/cgyc.css">
</head>
<body class="hero-anime">

<!-- Header Section -->
<div class="navigation-wrap bg-glass start-header start-style">
  <div class="container">
    <div class="row">
      <div class="col-12">
        <nav class="navbar navbar-expand-md navbar-light">
          <a class="navbar-brand" href="index.php">
            <img src="<?php echo BASE_URL; ?>../public/img/Logotipo Horizontal - Transparente UAC.png" alt="Logo" class="brand-logo">
          </a>
          <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
          </button>
          <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav ml-auto py-4 py-md-0">
              <li class="nav-item pl-4 pl-md-0 ml-0 ml-md-4">
                <a class="nav-link" href="index.php"><i class="fas fa-home"></i> Inicio</a>
              </li>
              <li class="nav-item pl-4 pl-md-0 ml-0 ml-md-4">
                <a class="nav-link" href="eventos.php"><i class="fas fa-calendar"></i> Eventos</a>
              </li>
              <li class="nav-item pl-4 pl-md-0 ml-0 ml-md-4">
                <a class="nav-link" href="cgyc.php"><i class="fas fa-users"></i> CGyC</a>
              </li>
              <li class="nav-item pl-4 pl-md-0 ml-0 ml-md-4">
                <a class="nav-link" href="docentes.php"><i class="fas fa-chalkboard-teacher"></i> Docentes</a>
              </li>
              <!-- Add Login Button -->
              <li class="nav-item pl-4 pl-md-0 ml-0 ml-md-4">
                <a class="nav-link login-btn" href="login.php">
                  <i class="fas fa-sign-in-alt"></i> Login
                </a>
              </li>
            </ul>
          </div>
        </nav>
      </div>
    </div>
  </div>
</div>
</body>
</html>
