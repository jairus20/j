<?php
require_once 'admin_header.php';
require_once __DIR__ . '/../src/config/database.php';

// Obtener el ID del ente
$id_ente = $_GET['id'] ?? '';

if (empty($id_ente)) {
    die('ID de ente no proporcionado');
}

// Obtener datos del ente
$stmt = $pdo->prepare("SELECT * FROM ente WHERE id_ente = ?");
$stmt->execute([$id_ente]);
$ente = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ente) {
    die('Ente no encontrado');
}

// Obtener miembros del ente
$stmt = $pdo->prepare("
    SELECT m.*, r.nombre_rol as cargo 
    FROM miembros m 
    JOIN roles_miembros r ON m.id_rol = r.id_rol 
    WHERE m.id_ente = ? 
    ORDER BY r.id_rol
");
$stmt->execute([$id_ente]);
$miembros = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener roles disponibles
$roles = $pdo->query("SELECT * FROM roles_miembros ORDER BY nombre_rol")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid mt-4">
    <!-- Panel de detalles del ente -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class='bx bx-info-circle me-2'></i>
                        Datos Generales
                    </h5>
                    <a href="admin_ente_management.php" class="btn btn-light btn-sm">
                        <i class='bx bx-arrow-back'></i> Volver
                    </a>
                </div>
                <div class="card-body">
                    <form id="enteForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="text-muted">Nombre</label>
                                    <input type="text" class="form-control" name="nombre" value="<?php echo htmlspecialchars($ente['nombre']); ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="text-muted">Tipo</label>
                                    <select class="form-select" name="tipo">
                                        <option value="CIRCULO" <?php echo $ente['tipo'] == 'CIRCULO' ? 'selected' : ''; ?>>Círculo</option>
                                        <option value="CENTRO" <?php echo $ente['tipo'] == 'CENTRO' ? 'selected' : ''; ?>>Centro</option>
                                        <option value="GRUPO" <?php echo $ente['tipo'] == 'GRUPO' ? 'selected' : ''; ?>>Grupo</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="text-muted">Escuela</label>
                                    <select class="form-select" name="escuela">
                                        <option value="Arquitectura" <?php echo $ente['escuela'] == 'Arquitectura' ? 'selected' : ''; ?>>Arquitectura</option>
                                        <option value="Ing Civil" <?php echo $ente['escuela'] == 'Ing Civil' ? 'selected' : ''; ?>>Ing. Civil</option>
                                        <option value="Ing Sistemas" <?php echo $ente['escuela'] == 'Ing Sistemas' ? 'selected' : ''; ?>>Ing. Sistemas</option>
                                        <option value="Ing Ambiental" <?php echo $ente['escuela'] == 'Ing Ambiental' ? 'selected' : ''; ?>>Ing. Ambiental</option>
                                        <option value="Ing Industrial" <?php echo $ente['escuela'] == 'Ing Industrial' ? 'selected' : ''; ?>>Ing. Industrial</option>
                                        <option value="UI-FIA" <?php echo $ente['escuela'] == 'UI-FIA' ? 'selected' : ''; ?>>UI-FIA</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="text-muted">Sede/Filial</label>
                                    <input type="text" class="form-control" name="sede" value="Larapa Grande – San Jerónimo – Cusco">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="text-muted">Email de Contacto</label>
                                    <input type="email" class="form-control" name="email_contacto" value="<?php echo htmlspecialchars($ente['email_contacto'] ?? ''); ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="text-muted">Página Web</label>
                                    <input type="url" class="form-control" name="pagina_web" value="<?php echo htmlspecialchars($ente['pagina_web'] ?? ''); ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="text-muted">Resolución de Creación</label>
                                    <input type="text" class="form-control" name="resolucion" value="<?php echo htmlspecialchars($ente['resolucion'] ?? ''); ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="text-muted">Estado</label>
                                    <select class="form-select" name="estado">
                                        <option value="ACTIVO" selected>ACTIVO</option>
                                        <option value="CERRADO">CERRADO</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="text-end mt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class='bx bx-save'></i> Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Panel de miembros -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class='bx bx-group me-2'></i>
                        Integrantes
                    </h5>
                    <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#addMiembroModal">
                        <i class='bx bx-user-plus'></i> Agregar Miembro
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="miembrosTable">
                            <thead>
                                <tr>
                                    <th>Cargo</th>
                                    <th>Nombres</th>
                                    <th>Apellidos</th>
                                    <th>Email</th>
                                    <th>DNI</th>
                                    <th>Vínculo</th>
                                    <th>Observaciones</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($miembros as $m): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($m['cargo']); ?></td>
                                    <td><?php echo htmlspecialchars($m['nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($m['apellido']); ?></td>
                                    <td><?php echo htmlspecialchars($m['email']); ?></td>
                                    <td><?php echo htmlspecialchars($m['dni'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($m['vinculo']); ?></td>
                                    <td><?php echo htmlspecialchars($m['observaciones'] ?? ''); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-info" onclick='editarMiembro(<?php echo json_encode($m); ?>)'>
                                            <i class='bx bx-edit'></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="eliminarMiembro(<?php echo $m['id_miembro']; ?>)">
                                            <i class='bx bx-trash'></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Agregar/Editar Miembro -->
<div class="modal fade" id="miembroModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="miembroModalTitle">Agregar Miembro</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="miembroForm">
                <input type="hidden" name="id_miembro" id="id_miembro">
                <input type="hidden" name="id_ente" value="<?php echo $id_ente; ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Cargo</label>
                        <select name="id_rol" class="form-select" required>
                            <?php foreach ($roles as $rol): ?>
                            <option value="<?php echo $rol['id_rol']; ?>">
                                <?php echo htmlspecialchars($rol['nombre_rol']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Nombres</label>
                                <input type="text" name="nombre" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Apellidos</label>
                                <input type="text" name="apellido" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email Institucional</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">DNI</label>
                        <input type="text" name="dni" class="form-control" pattern="[0-9]{8}" title="DNI debe tener 8 dígitos">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Vínculo</label>
                        <select name="vinculo" class="form-select" required>
                            <option value="DOCENTE">Docente</option>
                            <option value="ESTUDIANTE">Estudiante</option>
                            <option value="ADMINISTRATIVO">Administrativo</option>
                            <option value="EXTERNO">Externo</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Observaciones</label>
                        <textarea name="observaciones" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('editActividadForm').onsubmit = function(e) {
    e.preventDefault();
    const formData = new FormData(e.target);

    fetch('../src/controllers/ActividadController.php', {
        method: 'PUT',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire('Éxito', 'Actividad actualizada correctamente', 'success');
            location.reload();
        } else {
            Swal.fire('Error', data.error || 'No se pudo actualizar la actividad', 'error');
        }
    })
    .catch(err => {
        Swal.fire('Error', 'Hubo un problema al actualizar la actividad', 'error');
        console.error(err);
    });
};
</script>

<?php require_once 'admin_footer.php'; ?>
