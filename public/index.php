<?php
require_once __DIR__ . '/../src/config/config.php';
require_once __DIR__ . '/../src/views/header.php';
?>

<!-- Hero Section -->
<div class="hero-section">
  <div class="hero-content">
    <div class="hero-title">
      <h1 class="animate-title">Unidad de Investigación</h1>
      <p class="animate-subtitle">Facultad de Ingeniería y Arquitectura</p>
    </div>
    
    <div class="hero-shield">
      <img src="<?php echo BASE_URL; ?>../public/img/FIA Logo.png" alt="Escudo UAC" class="animate-shield">
    </div>
  </div>
</div>

<!-- Cards Section -->
<div id="cards" class="cards-section">
  <div class="cards-container">
    <div class="card" onclick="window.location.href='docentes.php'">
      <div class="card-icon">
        <i class="fas fa-users"></i>
      </div>
      <h3>Docentes de investigación</h3>
      <p>Conoce a nuestros investigadores</p>
    </div>
    
    <div class="card" onclick="window.location.href='cgyc.php'">
      <div class="card-icon">
        <i class="fas fa-layer-group"></i>
      </div>
      <h3>Centros / grupos y círculos</h3>
      <p>Explora nuestros grupos de investigación</p>
    </div>
    
    <div class="card" onclick="window.location.href='eventos.php'">
      <div class="card-icon">
        <i class="fas fa-calendar-alt"></i>
      </div>
      <h3>Eventos</h3>
      <p>Próximos eventos y actividades</p>
    </div>
    
    <div class="card" onclick="window.location.href='contacto.php'">
      <div class="card-icon">
        <i class="fas fa-envelope"></i>
      </div>
      <h3>Contactos</h3>
      <p>Comunícate con nosotros</p>
    </div>
  </div>
</div>

<?php
require_once __DIR__ . '/../src/views/footer.php';
?>
