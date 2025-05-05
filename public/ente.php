<?php
require_once __DIR__ . '/../src/config/config.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/views/header.php';

// Obtener el ID del ente desde la URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    die("ID de ente inválido.");
}

// Consulta para obtener los datos del ente
$stmt = $pdo->prepare("SELECT * FROM ente WHERE id_ente = :id");
$stmt->execute(['id' => $id]);
$ente = $stmt->fetch();

if (!$ente) {
    die("Ente no encontrado.");
}

// Consulta para obtener los integrantes del ente
$stmtIntegrantes = $pdo->prepare("SELECT * FROM miembros WHERE id_ente = :id");
$stmtIntegrantes->execute(['id' => $id]);
$integrantes = $stmtIntegrantes->fetchAll();

// Consulta para obtener las actividades del ente
$stmtActividades = $pdo->prepare("SELECT * FROM actividades WHERE id_ente = :id");
$stmtActividades->execute(['id' => $id]);
$actividades = $stmtActividades->fetchAll();

// Consulta para obtener los documentos del ente
$stmtDocumentos = $pdo->prepare("SELECT * FROM documentos WHERE id_ente = :id");
$stmtDocumentos->execute(['id' => $id]);
$documentos = $stmtDocumentos->fetchAll();

// Consulta para obtener el asesor del ente
$stmtAsesor = $pdo->prepare("SELECT * FROM encargados WHERE id_ente = :id AND rol = 'ASESOR'");
$stmtAsesor->execute(['id' => $id]);
$asesor = $stmtAsesor->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die("CSRF token validation failed.");
    }
    // Handle POST actions securely
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($ente['nombre']); ?> - Detalle del Ente</title>
    <style>
        /* Color Variables */
        :root {
          --primary-color: #2c3e50;
          --accent-color: #125f92;
          --hover-color: #2980b9;
          --text-light: #ecf0f1;
          --text-dark: #2c3e50;
          --gradient-start: #e8f4f8;
          --gradient-end: #b6d4e8;
          --box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
          --card-radius: 15px;
          --civil-color: rgba(46, 204, 113, 0.9);
          --industrial-color: rgba(231, 76, 60, 0.9);
          --sistemas-color: rgba(52, 152, 219, 0.9);
          --arquitectura-color: rgba(155, 89, 182, 0.9);
        }

        body {
          font-family: 'Segoe UI', Arial, sans-serif;
          margin: 0;
          padding: 0;
          background-color: var(--gradient-start);
          color: var(--text-dark);
          line-height: 1.6;
        }

        .header {
          position: relative;
          height: 200px;
          overflow: hidden;
          background-color: var(--primary-color);
        }
        .header img {
          width: 100%;
          height: 100%;
          object-fit: cover;
          opacity: 0.8;
        }
        .header .logo {
          position: absolute;
          bottom: 20px;
          left: 30px;
          color: white;
          font-size: 2.5em;
          font-weight: bold;
          text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }

        .tabs {
          background: white;
          padding: 20px 30px 0;
          border-bottom: 1px solid #e1e1e1;
          display: flex;
          gap: 20px;
        }
        .tab {
          padding: 10px 20px;
          cursor: pointer;
          border-bottom: 2px solid transparent;
        }
        .tab.active {
          border-bottom: 2px solid var(--secondary-color);
          color: var(--secondary-color);
          font-weight: 500;
        }

        .content {
          max-width: 1200px;
          margin: 30px auto;
          padding: 0 20px;
        }

        .description {
          background: white;
          padding: 25px;
          border-radius: var(--border-radius);
          box-shadow: var(--box-shadow);
          margin-bottom: 30px;
        }

        .members {
          margin-bottom: 30px;
        }
        .members h2 {
          color: var(--primary-color);
          margin-bottom: 20px;
          font-size: 1.5em;
        }

        .member-boxes {
          display: grid;
          grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
          gap: 20px;
        }
        .member-box {
          background: white;
          padding: 15px;
          border-radius: var(--border-radius);
          box-shadow: var(--box-shadow);
          transition: transform 0.2s;
        }
        .member-box:hover {
          transform: translateY(-3px);
        }

        .footer {
          background: white;
          padding: 20px;
          border-radius: var(--border-radius);
          box-shadow: var(--box-shadow);
          text-align: center;
        }
        .footer a {
          color: var(--secondary-color);
          text-decoration: none;
          font-weight: 500;
        }
        .footer a:hover {
          text-decoration: underline;
        }

        /* Hero Section */
        .ente-hero {
          min-height: 60vh;
          background: linear-gradient(rgba(44, 62, 80, 0.7), rgba(52, 152, 219, 0.7)),
                      url('../public/img/background.jpg');
          background-size: cover;
          background-position: center;
          margin-bottom: -50px;
          padding: 50px 20px;
          display: flex;
          align-items: center;
          justify-content: center;
        }

        .ente-hero::before {
          content: '';
          position: absolute;
          top: 0;
          left: 0;
          right: 0;
          bottom: 0;
          background: var(--ente-color);
          opacity: 0.9;
        }

        .ente-hero-content {
          max-width: 1200px;
          width: 100%;
          display: flex;
          align-items: center;
          gap: 50px;
          color: var(--text-light);
          animation: fadeIn 1s ease-out;
        }

        .ente-hero-image {
          flex-shrink: 0;
        }

        .ente-logo {
          width: 200px;
          height: 200px;
          object-fit: contain;
          border-radius: 50%;
          background: white;
          padding: 1rem;
          box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .ente-hero-text h1 {
          font-size: 2.5rem;
          margin-bottom: 1rem;
          color: white;
          text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .ente-type, .ente-school {
          font-size: 1.2rem;
          margin: 0.5rem 0;
          opacity: 0.9;
        }

        /* Content Container */
        .ente-container {
          max-width: 1200px;
          margin: -50px auto 50px;
          padding: 0 20px;
          position: relative;
          z-index: 2;
        }

        /* Tabs */
        .ente-tabs {
          display: flex;
          gap: 1rem;
          margin-bottom: 2rem;
          background: white;
          padding: 1rem;
          border-radius: 15px;
          box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .tab-btn {
          padding: 1rem 2rem;
          border: none;
          background: transparent;
          color: #666;
          font-weight: 500;
          cursor: pointer;
          transition: all 0.3s ease;
          border-radius: 8px;
        }

        .tab-btn:hover {
          background: rgba(0, 0, 0, 0.05);
        }

        .tab-btn.active {
          background: var(--ente-color);
          color:  #125f92;
        }

        /* Content Cards */
        .tab-content {
          display: none;
          animation: fadeIn 0.5s ease;
        }

        .tab-content.active {
          display: block;
        }

        .info-card {
          background: white;
          border-radius: 15px;
          padding: 2rem;
          box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        /* Member Cards */
        .members-section {
          margin-bottom: 2rem;
        }

        .members-grid {
          display: grid;
          grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
          gap: 1.5rem;
          margin-top: 1rem;
        }

        .member-card {
          background: white;
          border-radius: var(--card-radius);
          padding: 20px;
          display: flex;
          align-items: center;
          gap: 15px;
          box-shadow: var(--box-shadow);
          transition: transform 0.3s ease;
        }

        .member-card:hover {
          transform: translateY(-5px);
        }

        .member-avatar {
          width: 60px;
          height: 60px;
          background: var(--ente-color);
          border-radius: 50%;
          display: flex;
          align-items: center;
          justify-content: center;
          color: white;
          font-size: 1.5rem;
        }

        /* Activities Section */
        .activities-list {
          display: grid;
          grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
          gap: 20px;
        }

        .activity-card {
          background: white;
          border-radius: var(--card-radius);
          padding: 20px;
          box-shadow: var(--box-shadow);
          transition: all 0.3s ease;
        }

        .activity-card:hover {
          transform: translateY(-5px);
          box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        /* Documents Grid */
        .documents-grid {
          display: grid;
          grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
          gap: 20px;
        }

        .document-card {
          background: white;
          border-radius: var(--card-radius);
          padding: 25px;
          text-align: center;
          text-decoration: none;
          color: var(--text-dark);
          box-shadow: var(--box-shadow);
          transition: all 0.3s ease;
        }

        .document-card:hover {
          transform: translateY(-5px);
          color: var(--accent-color);
        }

        .document-card i {
          font-size: 2.5rem;
          color: var(--ente-color);
          margin-bottom: 1rem;
        }

        /* Animations */
        @keyframes fadeIn {
          from {
            opacity: 0;
          }
          to {
            opacity: 1;
          }
        }

        @keyframes fadeInUp {
          from {
            opacity: 0;
            transform: translateY(20px);
          }
          to {
            opacity: 1;
            transform: translateY(0);
          }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
          .ente-hero-content {
            flex-direction: column;
            text-align: center;
          }

          .ente-logo {
            width: 150px;
            height: 150px;
          }

          .ente-hero-text h1 {
            font-size: 2rem;
          }

          .ente-tabs {
            flex-direction: column;
          }

          .tab-btn {
            width: 100%;
            text-align: center;
          }

          .logo {
            font-size: 1.8em;
          }
          
          .content {
            padding: 0 15px;
          }
          
          .member-boxes {
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
          }

          .members-grid,
          .activities-list,
          .documents-grid {
            grid-template-columns: 1fr;
          }

          .info-section {
            padding: 20px;
          }

          .ente-details {
            padding: 20px 15px;
          }
        }
    </style>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <!-- Hero Section -->
    <div class="ente-hero">
        <div class="ente-hero-content">
            <div class="ente-hero-image">
                <?php if (!empty($ente['imagen'])): ?>
                    <img src="<?php echo htmlspecialchars($ente['imagen']); ?>" alt="Logo de <?php echo htmlspecialchars($ente['nombre']); ?>" class="ente-logo">
                <?php endif; ?>
            </div>
            <div class="ente-hero-text">
                <h1><?php echo htmlspecialchars($ente['nombre']); ?></h1>
                <p class="ente-type"><?php echo htmlspecialchars($ente['tipo']); ?></p>
                <p class="ente-school"><?php echo htmlspecialchars($ente['escuela']); ?></p>
            </div>
        </div>
    </div>

    <div class="ente-container">
        <!-- Tabs Navigation -->
        <div class="ente-tabs">
            <button class="tab-btn active" onclick="openTab('info')">
                <i class="fas fa-info-circle"></i> Información
            </button>
            <button class="tab-btn" onclick="openTab('members')">
                <i class="fas fa-users"></i> Integrantes
            </button>
            <button class="tab-btn" onclick="openTab('activities')">
                <i class="fas fa-calendar-alt"></i> Actividades
            </button>
            <button class="tab-btn" onclick="openTab('documents')">
                <i class="fas fa-file-alt"></i> Documentos
            </button>
        </div>

        <!-- Tab Contents -->
        <div id="info" class="tab-content active">
            <div class="info-card">
                <h2>Descripción</h2>
                <p><?php echo nl2br(htmlspecialchars($ente['descripcion'])); ?></p>
                <p><strong>Fecha de Creación:</strong> <?php echo htmlspecialchars($ente['fecha_creacion']); ?></p>
                <?php if ($asesor): ?>
                    <div class="asesor-info">
                        <h3>Asesor Académico</h3>
                        <div class="member-card">
                            <div class="member-avatar">
                                <?php echo strtoupper(substr($asesor['nombre'], 0, 1)); ?>
                            </div>
                            <div class="member-info">
                                <h4><?php echo htmlspecialchars($asesor['nombre'] . ' ' . $asesor['apellido']); ?></h4>
                                <p><?php echo htmlspecialchars($asesor['email']); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div id="members" class="tab-content">
            <div class="info-card">
                <h2>Integrantes</h2>
                <div class="members-grid">
                    <?php foreach ($integrantes as $integrante): ?>
                        <div class="member-card">
                            <div class="member-avatar">
                                <?php echo strtoupper(substr($integrante['nombre'], 0, 1)); ?>
                            </div>
                            <div class="member-info">
                                <h4><?php echo htmlspecialchars($integrante['nombre'] . ' ' . $integrante['apellido']); ?></h4>
                                <p><?php echo htmlspecialchars($integrante['cargo'] ?? 'Miembro'); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div id="activities" class="tab-content">
            <div class="info-card">
                <h2>Actividades</h2>
                <div class="activities-list">
                    <?php foreach ($actividades as $actividad): ?>
                        <div class="activity-card">
                            <h3><?php echo htmlspecialchars($actividad['titulo']); ?></h3>
                            <p><?php echo htmlspecialchars($actividad['descripcion']); ?></p>
                            <div class="activity-meta">
                                <span class="date"><?php echo htmlspecialchars($actividad['fecha']); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div id="documents" class="tab-content">
            <div class="info-card">
                <h2>Documentos</h2>
                <div class="documents-grid">
                    <?php foreach ($documentos as $documento): ?>
                        <a href="<?php echo htmlspecialchars($documento['ruta']); ?>" class="document-card" download>
                            <i class="fas fa-file-alt"></i>
                            <h4><?php echo htmlspecialchars($documento['nombre_documento']); ?></h4>
                            <p>Descargar</p>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab Functionality JS -->
    <script>
        function openTab(tabName) {
            // Ocultar todos los contenidos de tabs
            var tabContents = document.getElementsByClassName("tab-content");
            for (var i = 0; i < tabContents.length; i++) {
                tabContents[i].classList.remove("active");
            }

            // Remover la clase active de todos los botones
            var tabButtons = document.getElementsByClassName("tab-btn");
            for (var i = 0; i < tabButtons.length; i++) {
                tabButtons[i].classList.remove("active");
            }

            // Mostrar el contenido del tab seleccionado y activar el botón
            document.getElementById(tabName).classList.add("active");
            event.currentTarget.classList.add("active");
        }

        // Asegurarse de que solo el primer tab esté visible al cargar
        document.addEventListener('DOMContentLoaded', function() {
            // Ocultar todos los tabs excepto el primero
            var tabContents = document.getElementsByClassName("tab-content");
            for (var i = 0; i < tabContents.length; i++) {
                if (i === 0) {
                    tabContents[i].classList.add("active");
                } else {
                    tabContents[i].classList.remove("active");
                }
            }
        });
    </script>

    <?php require_once __DIR__ . '/../src/views/footer.php'; ?>
</body>
</html>
