<?php
require_once 'admin_header.php';
require_once __DIR__ . '/../src/config/database.php';

// Cargar workflows correctamente (usar include en vez de require para evitar return duplicado)
$workflows = include __DIR__ . '/../src/config/task_workflows.php';

// Obtener el código de actividad
$codigo_actividad = $_GET['codigo'] ?? '';

if (empty($codigo_actividad)) {
    die('Código de actividad no proporcionado');
}

// Obtener datos de la actividad
$stmt = $pdo->prepare("
    SELECT a.*, e.nombre as nombre_ente, a.categoria, a.tipo_proceso
    FROM actividades_poi a 
    JOIN ente e ON a.id_ente = e.id_ente 
    WHERE a.codigo_actividad = ?
");
$stmt->execute([$codigo_actividad]);
$actividad = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$actividad) {
    die('Actividad no encontrada');
}

// Obtener tareas de la actividad
$stmt = $pdo->prepare("SELECT * FROM tareas_actividad WHERE codigo_actividad = ?");
$stmt->execute([$codigo_actividad]);
$tareas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Para depuración, imprime la clave buscada y las claves disponibles
// echo '<pre>'; var_dump($actividad['tipo_proceso'], array_keys($workflows)); echo '</pre>';

// Load workflows usando tipo_proceso, si no existe, intenta con categoria
$workflow = null;
if (!empty($actividad['tipo_proceso']) && isset($workflows[$actividad['tipo_proceso']])) {
    $workflow = $workflows[$actividad['tipo_proceso']];
} elseif (!empty($actividad['categoria']) && isset($workflows[$actividad['categoria']])) {
    $workflow = $workflows[$actividad['categoria']];
}
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
    <!-- Panel de detalles de la actividad -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class='bx bx-info-circle me-2'></i>
                        Detalles de la Actividad
                    </h5>
                    <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#editActividadModal">
                        <i class='bx bx-edit'></i> Editar Actividad
                    </button>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="text-muted">Código</label>
                                <h5><?php echo htmlspecialchars($actividad['codigo_actividad']); ?></h5>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="text-muted">Nombre</label>
                                <h5><?php echo htmlspecialchars($actividad['nombre_actividad']); ?></h5>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="text-muted">Estado</label>
                                <?php
                                $estados_class = [
                                    'NO_INICIADA' => 'bg-warning text-dark',
                                    'EN_PROGRESO' => 'bg-info text-white',
                                    'FINALIZADA' => 'bg-success text-white',
                                    'CANCELADA' => 'bg-danger text-white'
                                ];
                                $clase = $estados_class[$actividad['estado_ejecucion']] ?? 'bg-secondary text-white';
                                ?>
                                <br>
                                <span class="badge <?php echo $clase; ?> fs-6">
                                    <?php echo htmlspecialchars($actividad['estado_ejecucion']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="text-muted">Círculo/Ente</label>
                                <h5><?php echo htmlspecialchars($actividad['nombre_ente']); ?></h5>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="text-muted">Monto Financiamiento</label>
                                <h5>S/ <?php echo number_format($actividad['monto_financiamiento'], 2); ?></h5>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="text-muted">Fechas</label>
                                <p class="mb-0">
                                    <strong>Inicio:</strong> <?php echo $actividad['fecha_inicio'] ? date('d/m/Y', strtotime($actividad['fecha_inicio'])) : 'No definida'; ?>
                                    <strong class="ms-3">Fin:</strong> <?php echo $actividad['fecha_fin'] ? date('d/m/Y', strtotime($actividad['fecha_fin'])) : 'No definida'; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    <?php if (!empty($actividad['meta']) || !empty($actividad['observaciones'])): ?>
                    <div class="row mt-3">
                        <?php if (!empty($actividad['meta'])): ?>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="text-muted">Meta</label>
                                <p class="text-justify"><?php echo nl2br(htmlspecialchars($actividad['meta'])); ?></p>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($actividad['observaciones'])): ?>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="text-muted">Observaciones</label>
                                <p class="text-justify"><?php echo nl2br(htmlspecialchars($actividad['observaciones'])); ?></p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <?php if ($workflow): ?>
                    <div class="workflow-container mt-4">
                        <h4>Flujo de Trabajo: <?php echo htmlspecialchars($workflow['name'] ?? $workflow['title'] ?? ''); ?></h4>
                        <ul class="workflow-steps">
                            <?php foreach ($workflow['steps'] as $step => $details): ?>
                            <li>
                                <strong><?php echo htmlspecialchars($step); ?>:</strong>
                                <ul>
                                    <?php foreach ($details as $key => $value): ?>
                                        <?php if (is_array($value)): ?>
                                            <li><?php echo htmlspecialchars($key); ?>:
                                                <ul>
                                                    <?php foreach ($value as $subValue): ?>
                                                        <li><?php echo htmlspecialchars($subValue); ?></li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </li>
                                        <?php else: ?>
                                            <li><?php echo htmlspecialchars($value); ?></li>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </ul>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-warning mt-4">
                        No se encontró configuración de proceso para este tipo de actividad/proceso.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Panel de tareas -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class='bx bx-task me-2'></i>
                        Tareas de la Actividad
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Filtros de tareas -->
                    <div class="filtros-wrapper">
                        <div class="filtros-row">
                            <div class="filtro-grupo">
                                <label>Categoría:</label>
                                <select id="filtroCategoria" onchange="aplicarFiltrosTareas()">
                                    <option value="">Todas las Categorías</option>
                                    <option value="CAPACITACION">Capacitación</option>
                                    <option value="INVESTIGACION">Investigación</option>
                                    <option value="GESTION">Gestión</option>
                                    <option value="ACTIVIDAD">Actividad</option>
                                </select>
                            </div>

                            <div class="filtro-grupo">
                                <label>Estado:</label>
                                <select id="filtroEstado" onchange="aplicarFiltrosTareas()">
                                    <option value="">Todos los Estados</option>
                                    <option value="PENDIENTE">Pendiente</option>
                                    <option value="EN_PROGRESO">En Progreso</option>
                                    <option value="FINALIZADA">Finalizada</option>
                                    <option value="CANCELADA">Cancelada</option>
                                </select>
                            </div>

                            <div class="filtros-count">
                                <i class='bx bx-data'></i>
                                <span id="contadorTareas">0 resultados</span>
                            </div>

                            <button class="btn-limpiar-filtros" onclick="limpiarFiltrosTareas()">
                                <i class='bx bx-refresh'></i>
                                Limpiar filtros
                            </button>
                        </div>
                    </div>

                    <div class="alert alert-info mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong>Monto Total Disponible:</strong> S/ <?php echo number_format($actividad['monto_financiamiento'], 2); ?>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover" id="tareasTable">
                            <thead>
                                <tr>
                                    <th>CÓDIGO</th>
                                    <th width="25%">DESCRIPCIÓN</th>
                                    <th>FECHAS</th>
                                    <th width="30%">ESTADO DEL PROCESO</th>
                                    <th class="text-center">ACCIONES</th>
                                </tr>
                            </thead>
                            <tbody id="tareasTableBody">
                                <?php foreach ($tareas as $t): ?>
                                <tr class="tarea-row fadeInUp"
                                    data-tarea='<?php echo json_encode($t); ?>'>
                                    <td><?php echo htmlspecialchars($t['codigo_tarea']); ?></td>
                                    <td>
                                        <div class="descripcion-tarea">
                                            <?php echo htmlspecialchars($t['descripcion']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fechas-tarea">
                                            <small>
                                                <i class='bx bx-calendar'></i> Inicio: 
                                                <?php echo $t['fecha_inicio'] ? date('d/m/Y', strtotime($t['fecha_inicio'])) : 'No definida'; ?>
                                            </small><br>
                                            <small>
                                                <i class='bx bx-calendar-check'></i> Fin: 
                                                <?php echo $t['fecha_fin'] ? date('d/m/Y', strtotime($t['fecha_fin'])) : 'No definida'; ?>
                                            </small>
                                        </div>
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
                                            // Determinar en qué paso fue observado (si tienes campo, úsalo, si no, usa el último estado antes de OBSERVADO)
                                            $observadoEn = isset($t['observado_en']) && $currentState === 'OBSERVADO'
                                                ? $t['observado_en']
                                                : ($currentState === 'OBSERVADO' ? (isset($t['ultimo_estado_real']) ? $t['ultimo_estado_real'] : null) : null);

                                            $stateKeys = array_keys($mainStates);
                                            $currentIndex = array_search($currentState, $stateKeys);

                                            $i = 0;
                                            foreach ($mainStates as $key => $estado) {
                                                // Si está observado y este es el paso observado, poner amarillo
                                                $isObservadoHere = ($currentState === 'OBSERVADO' && $observadoEn === $key);
                                                $isActive = $key === $currentState;
                                                $isPast = $i < $currentIndex;
                                                $stateClass = $isActive ? 'active' : ($isPast ? 'past' : '');
                                                // Forzar amarillo si es observado aquí
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
                                        <div class="btn-group">
                                            <button class="btn btn-sm btn-info" onclick="abrirModalEdicion('<?php echo $t['codigo_tarea']; ?>', '<?php echo $t['codigo_actividad']; ?>')" title="Editar">
                                                <i class='bx bx-edit'></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="eliminarTarea('<?php echo $t['codigo_tarea']; ?>', '<?php echo $t['codigo_actividad']; ?>')" title="Eliminar">
                                                <i class='bx bx-trash'></i>
                                            </button>
                                        </div>
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

        <!-- Modal para Crear Tarea -->
        <div class="modal fade" id="createTareaModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Nueva Tarea</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form id="createTareaForm">
                        <div class="modal-body">
                            <!-- Campos base -->
                            <input type="hidden" name="codigo_actividad" value="<?php echo htmlspecialchars($codigo_actividad); ?>">
                            
                            <!-- Tipo de Proceso -->
                            <div class="mb-3">
                                <label class="form-label">Tipo de Proceso</label>
                                <select name="tipo_proceso" id="create_tipo_proceso" class="form-control" required onchange="actualizarFlujo('create')">
                                    <option value="">Seleccione...</option>
                                    <option value="CAPACITACION_EJECUCION">Ejecución de Capacitación</option>
                                    <option value="CAPACITACION_SERVICIOS">Servicios de Capacitación</option>
                                    <option value="CAPACITACION_CERTIFICACION">Certificación</option>
                                </select>
                            </div>

                            <!-- Estado del Flujo -->
                            <div class="mb-3">
                                <label class="form-label">Estado del Proceso</label>
                                <select name="estado_flujo" id="create_estado_flujo" class="form-control" required>
                                    <option value="INICIO">Inicio del proceso</option>
                                </select>
                            </div>

                            <!-- Documentos Requeridos -->
                            <div class="mb-3">
                                <label class="form-label">Documentos Requeridos</label>
                                <div id="create_documentos_requeridos" class="border p-3 rounded">
                                    <!-- Se llena dinámicamente -->
                                </div>
                            </div>

                            <!-- Campos adicionales -->
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label">Descripción</label>
                                        <textarea name="descripcion" id="create_descripcion" class="form-control" required rows="3"></textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Fecha Inicio</label>
                                        <input type="date" name="fecha_inicio" id="create_fecha_inicio" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Fecha Fin</label>
                                        <input type="date" name="fecha_fin" id="create_fecha_fin" class="form-control">
                                    </div>
                                </div>
                            </div>

                            <!-- Observaciones -->
                            <div class="mb-3">
                                <label class="form-label">Observaciones</label>
                                <textarea name="observaciones" id="create_observaciones" class="form-control" rows="2"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Crear</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal para Editar Tarea -->
        <div class="modal fade" id="editTareaModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Editar Tarea</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form id="editTareaForm">
                        <input type="hidden" name="codigo_tarea" id="edit_codigo_tarea">
                        <input type="hidden" name="codigo_actividad" value="<?php echo htmlspecialchars($codigo_actividad); ?>">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Descripción</label>
                                <textarea name="descripcion" id="edit_descripcion" class="form-control" required rows="3"></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Fecha Inicio</label>
                                        <input type="date" name="fecha_inicio" id="edit_fecha_inicio" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Fecha Fin</label>
                                        <input type="date" name="fecha_fin" id="edit_fecha_fin" class="form-control" required>
                                    </div>
                                </div>
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
                        Swal.fire('Error', 'No se pudo cargar la tarea', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire('Error', 'Error al cargar la tarea', 'error');
                });
        }

        document.getElementById('createTareaForm').onsubmit = function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
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

        document.getElementById('editTareaForm').onsubmit = function(e) {
            e.preventDefault();
            const formData = new FormData(this);

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

        // Inicializar tooltips de Bootstrap
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
        </script>
    </div>
</div>

<!-- Modal para Editar Actividad -->
<div class="modal fade" id="editActividadModal" tabindex="-1" aria-labelledby="editActividadModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editActividadModalLabel">Editar Actividad</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editActividadForm">
                <input type="hidden" name="codigo_actividad" value="<?php echo htmlspecialchars($actividad['codigo_actividad']); ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Código de Actividad</label>
                        <input type="text" name="codigo_actividad" class="form-control" value="<?php echo htmlspecialchars($actividad['codigo_actividad']); ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nombre de Actividad</label>
                        <input type="text" name="nombre_actividad" class="form-control" value="<?php echo htmlspecialchars($actividad['nombre_actividad']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Meta</label>
                        <textarea name="meta" class="form-control"><?php echo htmlspecialchars($actividad['meta']); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Monto Financiamiento</label>
                        <input type="number" step="100" name="monto_financiamiento" class="form-control" value="<?php echo htmlspecialchars($actividad['monto_financiamiento']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Estado Ejecución</label>
                        <select name="estado_ejecucion" class="form-control">
                            <option value="NO_INICIADA" <?php echo $actividad['estado_ejecucion'] === 'NO_INICIADA' ? 'selected' : ''; ?>>No iniciada</option>
                            <option value="EN_PROGRESO" <?php echo $actividad['estado_ejecucion'] === 'EN_PROGRESO' ? 'selected' : ''; ?>>En progreso</option>
                            <option value="FINALIZADA" <?php echo $actividad['estado_ejecucion'] === 'FINALIZADA' ? 'selected' : ''; ?>>Finalizada</option>
                            <option value="CANCELADA" <?php echo $actividad['estado_ejecucion'] === 'CANCELADA' ? 'selected' : ''; ?>>Cancelada</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Observaciones</label>
                        <textarea name="observaciones" class="form-control"><?php echo htmlspecialchars($actividad['observaciones']); ?></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'admin_footer.php'; ?>
