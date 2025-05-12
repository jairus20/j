<?php
require_once 'admin_header.php';
require_once __DIR__ . '/../src/config/database.php';

// Load workflows
$workflows = include __DIR__ . '/../src/config/task_workflows.php';

// Get activity code
$codigo_actividad = $_GET['codigo'] ?? '';
if (empty($codigo_actividad)) {
    die('Código de actividad no proporcionado');
}

// Fetch activity details
$stmt = $pdo->prepare("
    SELECT a.*, e.nombre as nombre_ente
    FROM actividades_poi a 
    JOIN ente e ON a.id_ente = e.id_ente 
    WHERE a.codigo_actividad = ?
");
$stmt->execute([$codigo_actividad]);
$actividad = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$actividad) {
    die('Actividad no encontrada');
}

// Fetch tasks for the activity
$stmt = $pdo->prepare("SELECT * FROM tareas_actividad WHERE codigo_actividad = ?");
$stmt->execute([$codigo_actividad]);
$tareas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Determine workflow
$workflow = $workflows[$actividad['tipo_proceso']] ?? $workflows[$actividad['categoria']] ?? null;
?>

<style>
/* Estilos para el diagrama de flujo mejorado */
.progress-flow {
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 8px;
    background: #f8f9fa;
    border-radius: 4px;
    overflow-x: auto;
}

.flow-step {
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 4px 8px;
    border-radius: 4px;
    background: #e9ecef;
    white-space: nowrap;
    font-size: 0.85rem;
    color: #6c757d;
    transition: all 0.3s ease;
}

.flow-step i {
    font-size: 1.1rem;
}

.flow-step.active {
    background: #3c8dbc;
    color: white;
}

.flow-step.past {
    background: #28a745;
    color: white;
}

.flow-arrow {
    color: #adb5bd;
    font-size: 0.9rem;
}

.flow-arrow.past {
    color: #28a745;
}

/* Estilos para la tabla */
.descripcion-tarea {
    font-weight: 500;
    margin-bottom: 4px;
}

.fechas-tarea {
    font-size: 0.9rem;
    color: #666;
}

.fechas-tarea i {
    margin-right: 3px;
}

/* Responsive para el diagrama de flujo */
@media (max-width: 768px) {
    .progress-flow {
        flex-wrap: nowrap;
        overflow-x: auto;
    }
    
    .step-label {
        display: none;
    }
    
    .flow-step {
        padding: 4px;
    }
    
    .flow-step i {
        margin: 0;
    }
}

.tarea-row:hover {
    background-color: #f8f9fa;
    transform: scale(1.01);
    transition: all 0.2s ease;
}

.progress {
    background-color: #e9ecef;
    border-radius: 0.25rem;
    overflow: hidden;
}

.progress-bar {
    transition: width 0.6s ease;
}
</style>

<div class="container-fluid mt-4">
    <!-- Activity Details Panel -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class='bx bx-info-circle me-2'></i> Detalles de la Actividad
                    </h5>
                    <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#editActividadModal">
                        <i class='bx bx-edit'></i> Editar Actividad
                    </button>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <label class="text-muted">Código</label>
                            <h5><?= htmlspecialchars($actividad['codigo_actividad']); ?></h5>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted">Nombre</label>
                            <h5><?= htmlspecialchars($actividad['nombre_actividad']); ?></h5>
                        </div>
                        <div class="col-md-3">
                            <label class="text-muted">Estado</label>
                            <?php
                            $estado_classes = [
                                'NO_INICIADA' => 'bg-warning text-dark',
                                'EN_PROGRESO' => 'bg-info text-white',
                                'FINALIZADA' => 'bg-success text-white',
                                'CANCELADA' => 'bg-danger text-white'
                            ];
                            $estado_class = $estado_classes[$actividad['estado_ejecucion']] ?? 'bg-secondary text-white';
                            ?>
                            <span class="badge <?= $estado_class; ?> fs-6">
                                <?= htmlspecialchars($actividad['estado_ejecucion']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-3">
                            <label class="text-muted">Círculo/Ente</label>
                            <h5><?= htmlspecialchars($actividad['nombre_ente']); ?></h5>
                        </div>
                        <div class="col-md-3">
                            <label class="text-muted">Monto Financiamiento</label>
                            <h5>S/ <?= number_format($actividad['monto_financiamiento'], 2); ?></h5>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted">Fechas</label>
                            <p>
                                <strong>Inicio:</strong> <?= $actividad['fecha_inicio'] ? date('d/m/Y', strtotime($actividad['fecha_inicio'])) : 'No definida'; ?>
                                <strong class="ms-3">Fin:</strong> <?= $actividad['fecha_fin'] ? date('d/m/Y', strtotime($actividad['fecha_fin'])) : 'No definida'; ?>
                            </p>
                        </div>
                    </div>
                    <?php if (!empty($actividad['meta']) || !empty($actividad['observaciones'])): ?>
                        <div class="row mt-3">
                            <?php if (!empty($actividad['meta'])): ?>
                                <div class="col-md-6">
                                    <label class="text-muted">Meta</label>
                                    <p><?= nl2br(htmlspecialchars($actividad['meta'])); ?></p>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($actividad['observaciones'])): ?>
                                <div class="col-md-6">
                                    <label class="text-muted">Observaciones</label>
                                    <p><?= nl2br(htmlspecialchars($actividad['observaciones'])); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Tasks Panel -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class='bx bx-task me-2'></i> Tareas de la Actividad
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>CÓDIGO</th>
                                    <th>DESCRIPCIÓN</th>
                                    <th>FECHAS</th>
                                    <th>ESTADO DEL PROCESO</th>
                                    <th class="text-center">ACCIONES</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tareas as $t): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($t['codigo_tarea']); ?></td>
                                        <td><?= htmlspecialchars($t['descripcion']); ?></td>
                                        <td>
                                            <small>
                                                <i class='bx bx-calendar'></i> Inicio: <?= $t['fecha_inicio'] ? date('d/m/Y', strtotime($t['fecha_inicio'])) : 'No definida'; ?>
                                            </small><br>
                                            <small>
                                                <i class='bx bx-calendar-check'></i> Fin: <?= $t['fecha_fin'] ? date('d/m/Y', strtotime($t['fecha_fin'])) : 'No definida'; ?>
                                            </small>
                                        </td>
                                        <td>
                                            <div class="progress-flow">
                                                <?php
                                                $mainStates = [
                                                    'INICIO' => ['label' => 'Inicio', 'icon' => 'bx-play-circle'],
                                                    'EVAL_CGYC' => ['label' => 'CGyC', 'icon' => 'bx-badge-check'],
                                                    'EVAL_UIFIA' => ['label' => 'UI-FIA', 'icon' => 'bx-file'],
                                                    'EVAL_DDA' => ['label' => 'DDA', 'icon' => 'bx-list-check'],
                                                    'EVAL_DIPLA' => ['label' => 'DIPLA', 'icon' => 'bx-money'],
                                                ];
                                                $finalStates = [
                                                    'APROBADO' => ['label' => 'Aprobado', 'icon' => 'bx-check-circle'],
                                                    'RECHAZADO' => ['label' => 'Rechazado', 'icon' => 'bx-x-circle'],
                                                ];
                                                $currentState = $t['estado_flujo'];
                                                $observadoEn = isset($t['observado_en']) && $currentState === 'OBSERVADO'
                                                    ? $t['observado_en']
                                                    : ($currentState === 'OBSERVADO' ? (isset($t['ultimo_estado_real']) ? $t['ultimo_estado_real'] : null) : null);

                                                $stateKeys = array_keys($mainStates);
                                                $currentIndex = array_search($currentState, $stateKeys);

                                                $i = 0;
                                                foreach ($mainStates as $key => $estado) {
                                                    $isObservadoHere = ($currentState === 'OBSERVADO' && $observadoEn === $key);
                                                    $isActive = $key === $currentState;
                                                    $isPast = $i < $currentIndex;
                                                    $stateClass = $isActive ? 'active' : ($isPast ? 'past' : '');
                                                    $extraStyle = $isObservadoHere
                                                        ? 'background:#ffc107 !important;color:#222 !important;border:2px solid #ffc107 !important;'
                                                        : '';
                                                    ?>
                                                    <div class="flow-step <?php echo $stateClass; ?>"
                                                         data-bs-toggle="tooltip"
                                                         title="<?php echo $estado['label']; ?>"
                                                         style="<?php echo $extraStyle; ?>">
                                                        <i class='bx <?php echo $estado['icon']; ?>'></i>
                                                        <span class="step-label"><?php echo $estado['label']; ?></span>
                                                        <?php if ($isObservadoHere): ?>
                                                            <span class="badge" style="background:#ffc107;color:#222;margin-left:6px;">Observado</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <?php
                                                    if ($i < count($mainStates) - 1) {
                                                        echo '<div class="flow-arrow ' . ($isPast ? 'past' : '') . '">→</div>';
                                                    }
                                                    $i++;
                                                }
                                                ?>
                                                <div style="display: flex; flex-direction: column; align-items: center; margin-left: 10px;">
                                                    <?php foreach ($finalStates as $key => $estado): 
                                                        $isActive = $key === $currentState;
                                                        $stateClass = $isActive ? 'active' : '';
                                                    ?>
                                                    <div class="flow-step <?php echo $stateClass; ?>"
                                                         data-bs-toggle="tooltip"
                                                         title="<?php echo $estado['label']; ?>"
                                                         style="margin-bottom:4px;">
                                                        <i class='bx <?php echo $estado['icon']; ?>'></i>
                                                        <span class="step-label"><?php echo $estado['label']; ?></span>
                                                    </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-info" onclick="abrirModalEdicion('<?= $t['codigo_tarea']; ?>', '<?= $codigo_actividad; ?>')">
                                                <i class='bx bx-edit'></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="eliminarTarea('<?= $t['codigo_tarea']; ?>')">
                                                <i class='bx bx-trash'></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <button class="btn btn-primary mt-3" onclick="abrirModalCreacion()">
                        <i class='bx bx-plus'></i> Nueva Tarea
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Creating Task -->
<div class="modal fade" id="createTareaModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nueva Tarea</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="createTareaForm">
                <div class="modal-body">
                    <input type="hidden" name="codigo_actividad" value="<?= htmlspecialchars($codigo_actividad); ?>">
                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <select name="descripcion" class="form-control" required>
                            <option value="">Seleccione...</option>
                            <option value="CAPACITACION_EJECUCION">Ejecución de Capacitación</option>
                            <option value="CAPACITACION_SERVICIOS">Servicios de Capacitación</option>
                            <option value="CAPACITACION_CERTIFICACION">Certificación</option>
                        </select>
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
                        <label class="form-label">Estado del Flujo</label>
                        <select name="estado_flujo" class="form-control" required>
                            <option value="INICIO">Inicio</option>
                            <option value="EVAL_CGYC">Evaluación CGyC</option>
                            <option value="EVAL_UIFIA">Evaluación UI-FIA</option>
                            <option value="EVAL_DDA">Evaluación DDA</option>
                            <option value="EVAL_DIPLA">Evaluación DIPLA</option>
                            <option value="APROBADO">Aprobado</option>
                            <option value="RECHAZADO">Rechazado</option>
                            <option value="OBSERVADO">Observado</option>
                        </select>
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

<!-- Modal for Editing Task -->
<div class="modal fade" id="editTareaModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Tarea</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editTareaForm">
                <input type="hidden" name="codigo_tarea" id="edit_codigo_tarea">
                <input type="hidden" name="codigo_actividad" value="<?= htmlspecialchars($codigo_actividad); ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <select name="descripcion" id="edit_descripcion" class="form-control" required>
                            <option value="CAPACITACION_EJECUCION">Ejecución de Capacitación</option>
                            <option value="CAPACITACION_SERVICIOS">Servicios de Capacitación</option>
                            <option value="CAPACITACION_CERTIFICACION">Certificación</option>
                        </select>
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
                        <label class="form-label">Estado del Flujo</label>
                        <select name="estado_flujo" id="edit_estado_flujo" class="form-control" required>
                            <option value="INICIO">Inicio</option>
                            <option value="EVAL_CGYC">Evaluación CGyC</option>
                            <option value="EVAL_UIFIA">Evaluación UI-FIA</option>
                            <option value="EVAL_DDA">Evaluación DDA</option>
                            <option value="EVAL_DIPLA">Evaluación DIPLA</option>
                            <option value="APROBADO">Aprobado</option>
                            <option value="RECHAZADO">Rechazado</option>
                            <option value="OBSERVADO">Observado</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Función para validar la compatibilidad de la categoría de la tarea
function validarCategoriaTarea(actividadCategoria, tareaTipoProceso) {
    const compatibilidad = {
        'CAPACITACION': [
            'CAPACITACION_EJECUCION', 
            'CAPACITACION_SERVICIOS', 
            'CAPACITACION_CERTIFICACION'
        ]
    };

    return compatibilidad[actividadCategoria] && compatibilidad[actividadCategoria].includes(tareaTipoProceso);
}

// Función para actualizar el flujo según el tipo de proceso
async function actualizarFlujo(context) {
    const tipoProceso = document.getElementById(`${context}_tipo_proceso`).value;
    const estadoFlujo = document.getElementById(`${context}_estado_flujo`);
    const docsContainer = document.getElementById(`${context}_documentos_requeridos`);
    
    if (!tipoProceso) {
        estadoFlujo.innerHTML = '<option value="INICIO">Inicio del proceso</option>';
        docsContainer.innerHTML = '';
        return;
    }

    try {
        const response = await fetch(`../src/controllers/WorkflowController.php?tipo=${encodeURIComponent(tipoProceso)}`);
        if (!response.ok) {
            throw new Error('Error en la petición');
        }
        
        const data = await response.json();
        if (!data.success) {
            throw new Error(data.error || 'Error al cargar la configuración');
        }

        const workflow = data.data;
        
        // Actualizar estados disponibles
        estadoFlujo.innerHTML = Object.entries(workflow.states)
            .map(([key, state]) => `
                <option value="${key}">${state.description}</option>
            `).join('');

        // Mostrar documentos requeridos del estado inicial
        const initialState = workflow.states[workflow.initial_state];
        if (initialState && initialState.required_docs) {
            docsContainer.innerHTML = `
                <div class="required-docs">
                    <strong>Documentos necesarios para este estado:</strong>
                    <ul class="list-unstyled mt-2">
                        ${initialState.required_docs.map(doc => `
                            <li>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="docs[]" value="${doc}">
                                    <label class="form-check-label">${doc}</label>
                                </div>
                            </li>
                        `).join('')}
                    </ul>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error al cargar el workflow:', error);
        Swal.fire('Error', 'No se pudo cargar la configuración del proceso', 'error');
        estadoFlujo.innerHTML = '<option value="INICIO">Inicio del proceso</option>';
        docsContainer.innerHTML = '';
    }
}

// Función para abrir el modal de creación
function abrirModalCreacion() {
    const form = document.getElementById('createTareaForm');
    form.reset();
    new bootstrap.Modal(document.getElementById('createTareaModal')).show();
}

// Función para abrir el modal de edición
function abrirModalEdicion(codigoTarea, codigoActividad) {
    fetch(`../src/controllers/TareaController.php?codigo_tarea=${codigoTarea}&codigo_actividad=${codigoActividad}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const tarea = data.tarea;
                document.getElementById('edit_codigo_tarea').value = tarea.codigo_tarea;
                document.getElementById('edit_descripcion').value = tarea.descripcion;
                document.getElementById('edit_fecha_inicio').value = tarea.fecha_inicio;
                document.getElementById('edit_fecha_fin').value = tarea.fecha_fin;
                document.getElementById('edit_estado_flujo').value = tarea.estado_flujo;

                new bootstrap.Modal(document.getElementById('editTareaModal')).show();
            } else {
                Swal.fire('Error', data.error || 'No se pudo cargar la tarea', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire('Error', 'Error al cargar la tarea', 'error');
        });
}

// Update task creation logic
document.getElementById('createTareaForm').onsubmit = function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const actividadCategoria = '<?php echo $actividad['categoria']; ?>';
    const tareaDescripcion = formData.get('descripcion');

    if (!validarCategoriaTarea(actividadCategoria, tareaDescripcion)) {
        Swal.fire('Error', 'El tipo de proceso no es compatible con la categoría de la actividad.', 'error');
        return;
    }

    fetch('../src/controllers/TareaController.php', {
        method: 'POST',
        body: formData,
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire('Éxito', data.message, 'success').then(() => {
                location.reload();
            });
        } else {
            Swal.fire('Error', data.error || 'Error al crear la tarea', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire('Error', 'Hubo un problema al procesar la solicitud', 'error');
    });
};

// Update task editing logic
document.getElementById('editTareaForm').onsubmit = function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const actividadCategoria = '<?php echo $actividad['categoria']; ?>';
    const tareaDescripcion = formData.get('descripcion');

    if (!validarCategoriaTarea(actividadCategoria, tareaDescripcion)) {
        Swal.fire('Error', 'El tipo de proceso no es compatible con la categoría de la actividad.', 'error');
        return;
    }

    fetch('../src/controllers/TareaController.php', {
        method: 'PUT',
        body: new URLSearchParams(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire('Éxito', data.message, 'success').then(() => {
                location.reload();
            });
        } else {
            Swal.fire('Error', data.error || 'Error al editar la tarea', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire('Error', 'Hubo un problema al procesar la solicitud', 'error');
    });
};

// Handle task deletion
function eliminarTarea(codigoTarea) {
    Swal.fire({
        title: '¿Estás seguro?',
        text: 'Esta acción no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then(result => {
        if (result.isConfirmed) {
            fetch(`../src/controllers/TareaController.php?codigo_tarea=${codigoTarea}`, {
                method: 'DELETE'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Eliminado', data.message, 'success').then(() => location.reload());
                } else {
                    Swal.fire('Error', data.error || 'Error al eliminar la tarea', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error', 'Hubo un problema al procesar la solicitud', 'error');
            });
        }
    });
}

// Inicializar tooltips de Bootstrap
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>

<?php require_once 'admin_footer.php'; ?>
