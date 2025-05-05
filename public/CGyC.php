<?php
require_once __DIR__ . '/../src/config/config.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/views/header.php';
?>
<link rel="stylesheet" href="<?php echo BASE_URL; ?>css/cgyc.css">
<?php
// Obtener las escuelas (valores definidos en la base de datos) de forma dinámica
$sqlEscuelas = "SELECT DISTINCT escuela FROM ente ORDER BY escuela ASC";
$stmtEscuelas = $pdo->prepare($sqlEscuelas);
$stmtEscuelas->execute();
$escuelas = $stmtEscuelas->fetchAll(PDO::FETCH_COLUMN);

// --- LÓGICA PARA APLICAR FILTROS ---
$where = [];
$params = [];

if(isset($_GET['tipo']) && $_GET['tipo'] !== ''){
    $where[] = "tipo = :tipo";
    $params['tipo'] = $_GET['tipo'];
}
if(isset($_GET['escuela']) && $_GET['escuela'] !== ''){
    $where[] = "escuela = :escuela";
    $params['escuela'] = $_GET['escuela'];
}
if(isset($_GET['nombre']) && $_GET['nombre'] !== ''){
    $where[] = "nombre LIKE :nombre";
    $params['nombre'] = "%" . $_GET['nombre'] . "%";
}

$sql = "SELECT * FROM ente";
if(count($where) > 0){
    $sql .= " WHERE " . implode(' AND ', $where);
}
$sql .= " ORDER BY fecha_creacion DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$entes = $stmt->fetchAll();
?>

<!-- Contenedor con fondo degradado para el título -->
<div class="hero-title-cgyc">
  <h2>Centros, Grupos y Círculos</h2>
</div>

<div class="container my-5">
  <div class="row">
    <!-- Columna Izquierda: Filtros -->
    <div class="col-md-3 col-sm-12 mb-4">
      <div class="filters-section">
        <h4 class="mb-4">Filtros</h4>
        <form method="GET" action="">
          <!-- Filtro: Tipo -->
          <div class="mb-3">
            <label for="tipo" class="form-label">Tipo:</label>
            <select name="tipo" id="tipo" class="form-control" onchange="this.form.submit()">
              <option value="" <?php if(!isset($_GET['tipo']) || $_GET['tipo'] === '') echo 'selected'; ?>>Todos</option>
              <option value="CENTRO" <?php if(isset($_GET['tipo']) && $_GET['tipo'] === 'CENTRO') echo 'selected'; ?>>Centro</option>
              <option value="GRUPO" <?php if(isset($_GET['tipo']) && $_GET['tipo'] === 'GRUPO') echo 'selected'; ?>>Grupo</option>
              <option value="CIRCULO" <?php if(isset($_GET['tipo']) && $_GET['tipo'] === 'CIRCULO') echo 'selected'; ?>>Círculo</option>
            </select>
          </div>

          <!-- Filtro: Escuela -->
          <div class="mb-3">
            <label for="escuela" class="form-label">Escuela:</label>
            <select name="escuela" id="escuela" class="form-control" onchange="this.form.submit()">
              <option value="" <?php if(!isset($_GET['escuela']) || $_GET['escuela'] === '') echo 'selected'; ?>>Todas</option>
              <?php foreach($escuelas as $escuela): ?>
                <option value="<?php echo htmlspecialchars($escuela); ?>" 
                        <?php if(isset($_GET['escuela']) && $_GET['escuela'] === $escuela) echo 'selected'; ?>>
                  <?php echo htmlspecialchars($escuela); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Filtro: Búsqueda por Nombre -->
          <div class="mb-3">
            <label for="nombre" class="form-label">Nombre:</label>
            <div class="input-group">
              <input 
                type="text" 
                name="nombre" 
                id="nombre" 
                value="<?php echo isset($_GET['nombre']) ? htmlspecialchars($_GET['nombre']) : ''; ?>" 
                placeholder="Buscar por nombre"
                class="form-control"
              >
              <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i>
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <!-- Columna Derecha: Resultados -->
    <div class="col-md-9 col-sm-12">
      <div class="cards-container">
        <?php if(count($entes) > 0): ?>
          <?php foreach($entes as $ente): ?>
            <a href="<?php echo BASE_URL; ?>ente?id=<?php echo $ente['id_ente']; ?>" class="card-link">
              <div class="card mb-3">
                <?php if($ente['imagen']): ?>
                  <img src="<?php echo htmlspecialchars($ente['imagen']); ?>" alt="<?php echo htmlspecialchars($ente['nombre']); ?>" class="card-img-top">
                <?php endif; ?>
                <div class="card-body">
                  <h5><?php echo htmlspecialchars($ente['nombre']); ?></h5>
                  <p><?php echo htmlspecialchars($ente['descripcion']); ?></p>
                </div>
                <div class="card-footer">
                  <small><?php echo htmlspecialchars($ente['tipo']); ?> - <?php echo htmlspecialchars($ente['escuela']); ?></small>
                </div>
              </div>
            </a>
          <?php endforeach; ?>
        <?php else: ?>
          <p>No se encontraron registros.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script src="<?php echo BASE_URL; ?>js/prevent-enter.js"></script>
<script src="<?php echo BASE_URL; ?>js/cgyc.js"></script>
<?php
require_once __DIR__ . '/../src/views/footer.php';
?>
