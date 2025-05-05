<?php
require_once 'admin_header.php';
require_once __DIR__ . '/../src/config/config.php';
require_once __DIR__ . '/../src/config/database.php';

function tieneDocumentosRequeridos($pdo, $codigo_actividad) {
    $requeridos = ['Solicitud', 'Formato 14', 'Datos expositor'];
    $stmt = $pdo->prepare("SELECT nombre_documento FROM documentos WHERE codigo_actividad = ?");
    $stmt->execute([$codigo_actividad]);
    $docs = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($requeridos as $req) {
        $encontrado = false;
        foreach ($docs as $doc) {
            if (stripos($doc, $req) !== false) {
                $encontrado = true;
                break;
            }
        }
        if (!$encontrado) return false;
    }
    return true;
}

// Obtener lista de entes para el select
$entes = $pdo->query("SELECT id_ente, nombre FROM ente")->fetchAll(PDO::FETCH_ASSOC);
// Obtener lista de actividades con nombre de ente
$stmt = $pdo->query("SELECT a.*, e.nombre AS nombre_ente FROM actividades_poi a JOIN ente e ON a.id_ente = e.id_ente");
$actividades = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Procesar solicitudes POST (crear, actualizar, eliminar)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    // Validar CSRF token
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die(json_encode(['status' => 'error', 'message' => 'Error de validación CSRF']));
    }
    $response = ['status' => 'success', 'message' => ''];
    try {
        switch ($_POST['action']) {
            case 'create':
                if (isset($_POST['codigo_actividad'], $_POST['nombre_actividad'], $_POST['monto_financiamiento'], $_POST['id_ente'])) {
                    $stmt = $pdo->prepare("INSERT INTO actividades_poi (codigo_actividad, nombre_actividad, meta, fecha_inicio, fecha_fin, monto_financiamiento, id_ente, estado_ejecucion, observaciones) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $_POST['codigo_actividad'],
                        $_POST['nombre_actividad'],
                        $_POST['meta'] ?? null,
                        $_POST['fecha_inicio'] ?? null,
                        $_POST['fecha_fin'] ?? null,
                        $_POST['monto_financiamiento'],
                        $_POST['id_ente'],
                        $_POST['estado_ejecucion'] ?? 'NO_INICIADA',
                        $_POST['observaciones'] ?? null
                    ]);
                    $response['message'] = 'Actividad creada exitosamente';
                }
                break;
            case 'update':
                if (isset($_POST['codigo_actividad'], $_POST['nombre_actividad'], $_POST['monto_financiamiento'], $_POST['id_ente'], $_POST['estado_ejecucion'])) {
                    $stmt = $pdo->prepare("UPDATE actividades_poi SET nombre_actividad = ?, meta = ?, fecha_inicio = ?, fecha_fin = ?, monto_financiamiento = ?, id_ente = ?, estado_ejecucion = ?, observaciones = ? WHERE codigo_actividad = ?");
                    $stmt->execute([
                        $_POST['nombre_actividad'],
                        $_POST['meta'] ?? null,
                        $_POST['fecha_inicio'] ?? null,
                        $_POST['fecha_fin'] ?? null,
                        $_POST['monto_financiamiento'],
                        $_POST['id_ente'],
                        $_POST['estado_ejecucion'],
                        $_POST['observaciones'] ?? null,
                        $_POST['codigo_actividad']
                    ]);
                    $response['message'] = 'Actividad actualizada exitosamente';
                }
                break;
            case 'delete':
                if (isset($_POST['codigo_actividad'])) {
                    $stmt = $pdo->prepare("DELETE FROM actividades_poi WHERE codigo_actividad = ?");
                    $stmt->execute([$_POST['codigo_actividad']]);
                    $response['message'] = 'Actividad eliminada exitosamente';
                }
                break;
        }
    } catch (PDOException $e) {
        $response = ['status' => 'error', 'message' => 'Error en la operación: ' . $e->getMessage()];
    }
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
    $_SESSION['flash_message'] = $response;
    header('Location: admin_actividades_management.php');
    exit;
}

// Mostrar mensaje flash si existe
if (isset($_SESSION['flash_message'])) {
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: '{$_SESSION['flash_message']['status']}',
                title: '{$_SESSION['flash_message']['message']}',
                timer: 2000,
                showConfirmButton: false
            });
        });
    </script>";
    unset($_SESSION['flash_message']);
}
?>
<div class="main-content">
    <div class="container-fluid">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="h3 mb-0">Gestión de Actividades</h2>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addActividadModal">
                    <i class='bx bx-plus me-1'></i> Nueva Actividad
                </button>
                <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#importExcelModal">
                    <i class='bx bx-upload me-1'></i> Importar Excel
                </button>
            </div>
        </div>

        <!-- Filtros -->
        <div class="filtros-wrapper">
            <div class="filtros-row">
                <div class="filtro-grupo">
                    <label>Ente:</label>
                    <select id="filtroEnte" onchange="aplicarFiltros()">
                        <option value="">Todos los Entes</option>
                        <?php foreach ($entes as $ente): ?>
                            <option value="<?php echo $ente['id_ente']; ?>">
                                <?php echo htmlspecialchars($ente['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filtro-grupo">
                    <label>Estado:</label>
                    <select id="filtroEstado" onchange="aplicarFiltros()">
                        <option value="">Todos los Estados</option>
                        <option value="NO_INICIADA">No Iniciada</option>
                        <option value="EN_PROGRESO">En Progreso</option>
                        <option value="FINALIZADA">Finalizada</option>
                        <option value="CANCELADA">Cancelada</option>
                    </select>
                </div>

                <div class="filtros-count">
                    <i class='bx bx-data'></i>
                    <span id="contadorResultados">0 resultados</span>
                </div>

                <button class="btn-limpiar-filtros" onclick="limpiarFiltros()">
                    <i class='bx bx-refresh'></i>
                    Limpiar filtros
                </button>
            </div>
        </div>

        <!-- Tabla de actividades -->
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Centro/Grupo</th>
                        <th>Código</th>
                        <th>Actividad Operativa</th>
                        <th>Tip.</th>
                        <th class="text-center">1T %</th>
                        <th class="text-center">2T %</th>
                        <th class="text-center">3T %</th>
                        <th class="text-center">4T %</th>
                        <th class="text-end">Monto</th>
                        <th>Estado</th>
                        <th>Prioridad</th>
                        <th>INFORME</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($actividades as $actividad): ?>
                    <tr class="fadeInUp">
                        <td><?php echo htmlspecialchars($actividad['nombre_ente']); ?></td>
                        <td>
                            <a href="actividad_detail.php?codigo=<?php echo urlencode($actividad['codigo_actividad']); ?>" 
                               class="text-primary text-decoration-none">
                                <?php echo htmlspecialchars($actividad['codigo_actividad']); ?>
                            </a>
                        </td>
                        <td><?php echo htmlspecialchars($actividad['nombre_actividad']); ?></td>
                        <td><?php echo htmlspecialchars($actividad['categoria'] ?? 'CAPA'); ?></td>
                        <td class="text-center"><?php echo number_format($actividad['porcentaje_1t'], 1); ?>%</td>
                        <td class="text-center"><?php echo number_format($actividad['porcentaje_2t'], 1); ?>%</td>
                        <td class="text-center"><?php echo number_format($actividad['porcentaje_3t'], 1); ?>%</td>
                        <td class="text-center"><?php echo number_format($actividad['porcentaje_4t'], 1); ?>%</td>
                        <td class="text-end">S/ <?php echo number_format($actividad['monto_financiamiento'], 2); ?></td>
                        <td>
                            <?php
                            $estados_class = [
                                'NO_INICIADA' => ['label' => 'PENDIENTE', 'class' => 'bg-warning text-dark'],
                                'EN_PROGRESO' => ['label' => 'PROCESO', 'class' => 'bg-info text-dark'],
                                'FINALIZADA'  => ['label' => 'FINALIZADO',  'class' => 'bg-success'],
                                'CANCELADA'   => ['label' => 'CANCELADO',   'class' => 'bg-danger']
                            ];
                            $estado = $estados_class[$actividad['estado_ejecucion']] ?? ['label' => $actividad['estado_ejecucion'], 'class' => 'bg-secondary'];
                            ?>
                            <span class="badge <?php echo $estado['class']; ?>">
                                <?php echo $estado['label']; ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($actividad['prioridad'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($actividad['observaciones'] ?? ''); ?></td>
                        <td>
                            <button class="btn btn-sm btn-info" onclick="editActividad(<?php echo htmlspecialchars(json_encode($actividad)); ?>)">
                                <i class='bx bx-edit'></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="deleteActividad('<?php echo $actividad['codigo_actividad']; ?>')">
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    const addActividadModal = document.getElementById('addActividadModal');
    const codigoActividadInput = addActividadModal.querySelector('input[name="codigo_actividad"]');

    // Fetch the next available codigo_actividad when the modal is shown
    addActividadModal.addEventListener('show.bs.modal', function () {
        fetch('../src/controllers/ActividadController.php?action=nextCodigo')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.codigo_actividad) {
                    codigoActividadInput.value = data.codigo_actividad;
                } else {
                    console.error('Error fetching next codigo_actividad:', data.error);
                }
            })
            .catch(error => console.error('Error:', error));
    });
});

document.getElementById('importActividadesForm').onsubmit = function(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    
    Swal.fire({
        title: 'Importando actividades...',
        html: 'Por favor espere...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    fetch('../src/controllers/ImportActividadesController.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(resp => {
        if (resp.success) {
            Swal.fire({
                title: 'Éxito',
                html: `
                    <p>${resp.message}</p>
                    <div class="text-start">
                        <p><strong>Resumen:</strong></p>
                        <ul>
                            <li>Importadas: ${resp.stats.imported}</li>
                            <li>Actualizadas: ${resp.stats.updated}</li>
                            <li>Errores: ${resp.stats.errors}</li>
                        </ul>
                    </div>
                `,
                icon: 'success'
            });
            bootstrap.Modal.getInstance(document.getElementById('importExcelModal')).hide();
            location.reload();
        } else {
            let errorMessage = '';
            
            if (resp.errors && resp.errors.length > 0) {
                errorMessage = `
                    <div class="text-start">
                        <p><strong>Se encontraron ${resp.error_details.total_errors} errores:</strong></p>
                        <div style="max-height: 200px; overflow-y: auto; background: #f8f9fa; padding: 10px; border-radius: 5px; margin: 10px 0;">
                            ${resp.errors.map(err => `<div class="text-danger">• ${err}</div>`).join('')}
                        </div>
                        <p class="mt-2"><small>Corrija los errores y vuelva a intentar.</small></p>
                    </div>
                `;
            } else {
                errorMessage = resp.error || 'Error desconocido al importar el archivo';
            }

            Swal.fire({
                title: 'Error en la importación',
                html: errorMessage,
                icon: 'error',
                width: '600px'
            });
        }
    })
    .catch(err => {
        console.error(err);
        Swal.fire({
            title: 'Error',
            text: 'Hubo un problema al procesar el archivo',
            icon: 'error'
        });
    });
};

function actualizarTipoProceso(categoria) {
    const tipoProceso = document.getElementById('tipo_proceso');
    const procesos = {
        'CAPACITACION': {
            'CAPACITACION_SERVICIOS': 'Servicios por Terceros',
            'CAPACITACION_CERTIFICACION': 'Certificación',
            'CAPACITACION_EJECUCION': 'Ejecución'
        },
        'INVESTIGACION': {
            'INVESTIGACION_PROYECTO': 'Proyecto de Investigación',
            'INVESTIGACION_ARTICULO': 'Artículo Científico',
            'INVESTIGACION_PUBLICACION': 'Publicación'
        },
        'GESTION': {
            'GESTION_ADMINISTRATIVA': 'Gestión Administrativa',
            'GESTION_ACADEMICA': 'Gestión Académica',
            'GESTION_FINANCIERA': 'Gestión Financiera'
        },
        'ACTIVIDAD': {
            'ACTIVIDAD_ACADEMICA': 'Actividad Académica',
            'ACTIVIDAD_CULTURAL': 'Actividad Cultural',
            'ACTIVIDAD_EXTENSION': 'Actividad de Extensión'
        }
    };

    tipoProceso.innerHTML = '<option value="">Seleccione un proceso</option>';
    
    if (categoria in procesos) {
        Object.entries(procesos[categoria]).forEach(([value, label]) => {
            tipoProceso.innerHTML += `<option value="${value}">${label}</option>`;
        });
    }
}

function editActividad(actividad) {
    document.getElementById('edit_codigo_actividad').value = actividad.codigo_actividad;
    document.getElementById('edit_id_ente').value = actividad.id_ente;
    document.getElementById('edit_nombre_actividad').value = actividad.nombre_actividad;
    document.getElementById('edit_meta').value = actividad.meta;
    document.getElementById('edit_fecha_inicio').value = actividad.fecha_inicio;
    document.getElementById('edit_fecha_fin').value = actividad.fecha_fin;
    document.getElementById('edit_cronograma').value = actividad.cronograma;
    document.getElementById('edit_monto_financiamiento').value = actividad.monto_financiamiento;
    document.getElementById('edit_estado_ejecucion').value = actividad.estado_ejecucion;
    document.getElementById('edit_observaciones').value = actividad.observaciones;
    new bootstrap.Modal(document.getElementById('editActividadModal')).show();
}
</script>

<!-- Modal para Agregar Actividad -->
<div class="modal fade" id="addActividadModal" tabindex="-1" aria-labelledby="addActividadModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addActividadModalLabel">Nueva Actividad</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="action" value="create">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Círculo/Ente</label>
                        <select name="id_ente" class="form-control" required>
                            <option value="">Seleccione...</option>
                            <?php foreach ($entes as $ente): ?>
                                <option value="<?php echo $ente['id_ente']; ?>"><?php echo htmlspecialchars($ente['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Código de Actividad</label>
                        <input type="text" name="codigo_actividad" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nombre de Actividad</label>
                        <input type="text" name="nombre_actividad" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Categoría</label>
                        <select name="categoria" class="form-control" required>
                            <option value="CAPACITACION">Capacitación</option>
                            <option value="INVESTIGACION">Investigación</option>
                            <option value="GESTION">Gestión</option>
                            <option value="ACTIVIDAD">Actividad</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Meta</label>
                        <textarea name="meta" class="form-control"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Fecha Inicio</label>
                        <input type="date" name="fecha_inicio" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Fecha Fin</label>
                        <input type="date" name="fecha_fin" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Porcentajes Trimestrales</label>
                        <div class="d-flex gap-2">
                            <input type="number" step="0.1" name="porcentaje_1t" class="form-control" placeholder="1T (%)" min="0" max="100" required>
                            <input type="number" step="0.1" name="porcentaje_2t" class="form-control" placeholder="2T (%)" min="0" max="100" required>
                            <input type="number" step="0.1" name="porcentaje_3t" class="form-control" placeholder="3T (%)" min="0" max="100" required>
                            <input type="number" step="0.1" name="porcentaje_4t" class="form-control" placeholder="4T (%)" min="0" max="100" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Monto Financiamiento</label>
                        <input type="number" step="0.01" name="monto_financiamiento" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Estado Ejecución</label>
                        <select name="estado_ejecucion" class="form-control">
                            <option value="NO_INICIADA">No iniciada</option>
                            <option value="EN_PROGRESO">En progreso</option>
                            <option value="FINALIZADA">Finalizada</option>
                            <option value="CANCELADA">Cancelada</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Prioridad</label>
                        <input type="text" name="prioridad" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Observaciones</label>
                        <textarea name="observaciones" class="form-control"></textarea>
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

<!-- Modal para Editar Actividad -->
<div class="modal fade" id="editActividadModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Actividad</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="codigo_actividad" id="edit_codigo_actividad">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Círculo/Ente</label>
                        <select name="id_ente" id="edit_id_ente" class="form-control" required>
                            <option value="">Seleccione...</option>
                            <?php foreach ($entes as $ente): ?>
                                <option value="<?php echo $ente['id_ente']; ?>"><?php echo htmlspecialchars($ente['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Código de Actividad</label>
                        <input type="text" name="codigo_actividad" id="edit_codigo_actividad" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nombre de Actividad</label>
                        <input type="text" name="nombre_actividad" id="edit_nombre_actividad" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Meta</label>
                        <textarea name="meta" id="edit_meta" class="form-control"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Fecha Inicio</label>
                        <input type="date" name="fecha_inicio" id="edit_fecha_inicio" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Fecha Fin</label>
                        <input type="date" name="fecha_fin" id="edit_fecha_fin" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Cronograma</label>
                        <input type="text" name="cronograma" id="edit_cronograma" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Porcentajes Trimestrales</label>
                        <div class="d-flex gap-2">
                            <input type="number" step="0.1" name="porcentaje_1t" id="edit_porcentaje_1t" class="form-control" placeholder="1T (%)" min="0" max="100" required>
                            <input type="number" step="0.1" name="porcentaje_2t" id="edit_porcentaje_2t" class="form-control" placeholder="2T (%)" min="0" max="100" required>
                            <input type="number" step="0.1" name="porcentaje_3t" id="edit_porcentaje_3t" class="form-control" placeholder="3T (%)" min="0" max="100" required>
                            <input type="number" step="0.1" name="porcentaje_4t" id="edit_porcentaje_4t" class="form-control" placeholder="4T (%)" min="0" max="100" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Monto Financiamiento</label>
                        <input type="number" step="100" name="monto_financiamiento" id="edit_monto_financiamiento" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Estado Ejecución</label>
                        <select name="estado_ejecucion" id="edit_estado_ejecucion" class="form-control">
                            <option value="NO_INICIADA">No iniciada</option>
                            <option value="EN_PROGRESO">En progreso</option>
                            <option value="FINALIZADA">Finalizada</option>
                            <option value="CANCELADA">Cancelada</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Observaciones</label>
                        <textarea name="observaciones" id="edit_observaciones" class="form-control"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Actualizar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Documentos Mejorado -->
<div class="modal fade" id="documentosModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Documentos de la Actividad</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <!-- Formulario -->
                    <div class="col-md-5 border-end">
                        <!-- Documentos requeridos -->
                        <div class="alert alert-info mb-3">
                            <strong>Documentos requeridos:</strong>
                            <ul class="mb-0">
                                <li>Solicitud</li>
                                <li>Formato 14</li>
                                <li>Datos expositor</li>
                            </ul>
                            <small class="text-muted">Sube cada documento con el nombre correspondiente.</small>
                        </div>
                        <form id="uploadDocForm" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <input type="hidden" name="action" value="upload_doc">
                            <input type="hidden" name="codigo_actividad" id="doc_codigo_actividad">
                            <div class="mb-3">
                                <label class="form-label">Nombre del Documento <span class="text-danger">*</span></label>
                                <input type="text" name="nombre_documento" class="form-control" placeholder="Ej: Solicitud, Formato 14, Datos expositor" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Archivo <span class="text-danger">*</span></label>
                                <input type="file" name="file" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Descripción</label>
                                <input type="text" name="descripcion" class="form-control" placeholder="Descripción">
                            </div>
                            <div class="mb-3">
                                <button type="submit" class="btn btn-primary w-100">Subir Documento</button>
                            </div>
                        </form>
                    </div>
                    <!-- Tabla -->
                    <div class="col-md-7">
                        <div id="documentosTableContainer"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Importar Excel -->
<div class="modal fade" id="importExcelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Importar Actividades desde Excel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="importActividadesForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <strong>Formato esperado:</strong>
                        <ul class="mb-0">
                            <li>Centro/Grupo</li>
                            <li>Código</li>
                            <li>Actividad Operativa</li>
                            <li>Tip.</li>
                            <li>1T, 2T, 3T, 4T (%)</li>
                            <li>Monto</li>
                            <li>Estado</li>
                            <li>Prioridad</li>
                            <li>INFORME</li>
                        </ul>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Archivo Excel (.xlsx)</label>
                        <input type="file" name="file" class="form-control" accept=".xlsx" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class='bx bx-upload'></i> Importar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Usar JS externo -->
<script src="../public/js/admin_actividades.js"></script>
<?php require_once 'admin_footer.php'; ?>