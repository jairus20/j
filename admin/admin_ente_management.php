<?php
require_once 'admin_header.php';
require_once __DIR__ . '/../src/config/database.php';

// Obtener lista de entes
$stmt = $pdo->query('SELECT * FROM ente ORDER BY nombre');
$entes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="main-content">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Gestión de Entes</h2>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEnteModal">
                    <i class='bx bx-plus-circle'></i> Nuevo Ente
                </button>
                <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#importExcelModal">
                    <i class='bx bx-upload'></i> Importar Excel
                </button>
            </div>
        </div>

        <!-- Filtros -->
        <div class="filtros-wrapper">
            <div class="filtros-row">
                <div class="filtro-grupo">
                    <label>Escuela:</label>
                    <select id="filtroEscuela" onchange="aplicarFiltros()">
                        <option value="">Todas las Escuelas</option>
                        <option value="Arquitectura">Arquitectura</option>
                        <option value="Ing Civil">Ing Civil</option>
                        <option value="Ing Sistemas">Ing Sistemas</option>
                        <option value="Ing Ambiental">Ing Ambiental</option>
                        <option value="Ing Industrial">Ing Industrial</option>
                        <option value="UI-FIA">UI-FIA</option>
                    </select>
                </div>
                
                <div class="filtro-grupo">
                    <label>Tipo:</label>
                    <select id="filtroTipo" onchange="aplicarFiltros()">
                        <option value="">Todos los Tipos</option>
                        <option value="CIRCULO">Círculo</option>
                        <option value="CENTRO">Centro</option>
                        <option value="GRUPO">Grupo</option>
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

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover" id="entesTable">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Tipo</th>
                                <th>Escuela</th>
                                <th>Email Contacto</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($entes as $e): ?>
                            <tr>
                                <td>
                                    <a href="ente_detail.php?id=<?php echo $e['id_ente']; ?>" class="text-primary">
                                        <?php echo htmlspecialchars($e['nombre']); ?>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars($e['tipo']); ?></td>
                                <td><?php echo htmlspecialchars($e['escuela']); ?></td>
                                <td><?php echo htmlspecialchars($e['email_contacto'] ?? ''); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-info" onclick='editarEnte(<?php echo json_encode($e); ?>)'>
                                        <i class='bx bx-edit'></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="eliminarEnte(<?php echo $e['id_ente']; ?>)">
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

<!-- Modal para Agregar Ente -->
<div class="modal fade" id="addEnteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nuevo Ente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addEnteForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre</label>
                        <input type="text" name="nombre" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipo</label>
                        <select name="tipo" class="form-select" required>
                            <option value="">Seleccione...</option>
                            <option value="CIRCULO">Círculo</option>
                            <option value="CENTRO">Centro</option>
                            <option value="GRUPO">Grupo</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Escuela</label>
                        <select name="escuela" class="form-select" required>
                            <option value="">Seleccione...</option>
                            <option value="Arquitectura">Arquitectura</option>
                            <option value="Ing Civil">Ing Civil</option>
                            <option value="Ing Sistemas">Ing Sistemas</option>
                            <option value="Ing Ambiental">Ing Ambiental</option>
                            <option value="Ing Industrial">Ing Industrial</option>
                            <option value="UI-FIA">UI-FIA</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea name="descripcion" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">URL de Imagen</label>
                        <input type="url" name="imagen" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email de Contacto</label>
                        <input type="email" name="email_contacto" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nombre del Responsable</label>
                        <input type="text" name="nombre_responsable" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Apellido del Responsable</label>
                        <input type="text" name="apellido_responsable" class="form-control">
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

<!-- Modal para Editar Ente -->
<div class="modal fade" id="editEnteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Ente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editEnteForm">
                <input type="hidden" name="id_ente" id="edit_id_ente">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre</label>
                        <input type="text" name="nombre" id="edit_nombre" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipo</label>
                        <select name="tipo" id="edit_tipo" class="form-select" required>
                            <option value="CIRCULO">Círculo</option>
                            <option value="CENTRO">Centro</option>
                            <option value="GRUPO">Grupo</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Escuela</label>
                        <select name="escuela" id="edit_escuela" class="form-select" required>
                            <option value="Arquitectura">Arquitectura</option>
                            <option value="Ing Civil">Ing Civil</option>
                            <option value="Ing Sistemas">Ing Sistemas</option>
                            <option value="Ing Ambiental">Ing Ambiental</option>
                            <option value="Ing Industrial">Ing Industrial</option>
                            <option value="UI-FIA">UI-FIA</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea name="descripcion" id="edit_descripcion" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">URL de Imagen</label>
                        <input type="url" name="imagen" id="edit_imagen" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email de Contacto</label>
                        <input type="email" name="email_contacto" id="edit_email_contacto" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nombre del Responsable</label>
                        <input type="text" name="nombre_responsable" id="edit_nombre_responsable" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Apellido del Responsable</label>
                        <input type="text" name="apellido_responsable" id="edit_apellido_responsable" class="form-control">
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

<!-- Modal para Importar Excel -->
<div class="modal fade" id="importExcelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Importar Entes desde Excel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="importExcelForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Archivo Excel</label>
                        <input type="file" name="file" class="form-control" accept=".xlsx, .xls" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Importar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let entesData = []; // Variable para almacenar todos los entes

function cargarEntes() {
    fetch('../src/controllers/EnteController.php')
        .then(r => r.json())
        .then(data => {
            entesData = data.entes || [];
            aplicarFiltros();
        });
}

function aplicarFiltros() {
    const escuelaSeleccionada = document.getElementById('filtroEscuela').value;
    const tipoSeleccionado = document.getElementById('filtroTipo').value;
    
    const entesFiltrados = entesData.filter(ente => {
        const cumpleEscuela = !escuelaSeleccionada || ente.escuela === escuelaSeleccionada;
        const cumpleTipo = !tipoSeleccionado || ente.tipo === tipoSeleccionado;
        return cumpleEscuela && cumpleTipo;
    });

    // Actualizar el contador de resultados con un formato más legible
    const contadorResultados = document.getElementById('contadorResultados');
    contadorResultados.textContent = `${entesFiltrados.length} ${entesFiltrados.length === 1 ? 'resultado' : 'resultados'}`;
    
    // Actualizar clase del badge según la cantidad de resultados
    contadorResultados.className = 'badge text-white ' + (entesFiltrados.length > 0 ? 'bg-primary' : 'bg-warning');

    const tbody = document.querySelector('#entesTable tbody');
    tbody.innerHTML = '';
    
    entesFiltrados.forEach((e, index) => {
        setTimeout(() => {
            const tr = document.createElement('tr');
            tr.style.opacity = '0';
            tr.style.transform = 'translateY(20px)';
            tr.style.transition = 'all 0.5s ease';
            tr.innerHTML = `
                <td>
                    <a href="ente_detail.php?id=${e.id_ente}" class="text-primary">
                        ${e.nombre}
                    </a>
                </td>
                <td>${e.tipo}</td>
                <td>${e.escuela}</td>
                <td>${e.email_contacto || ''}</td>
                <td>
                    <button class="btn btn-sm btn-info" onclick='editarEnte(${JSON.stringify(e)})'>
                        <i class='bx bx-edit'></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="eliminarEnte(${e.id_ente})">
                        <i class='bx bx-trash'></i>
                    </button>
                </td>
            `;
            tbody.appendChild(tr);
            // Forzar un reflow
            tr.offsetHeight;
            // Aplicar la transición
            tr.style.opacity = '1';
            tr.style.transform = 'translateY(0)';
        }, index * 100); // Aumentar el delay entre elementos
    });

    // Mostrar mensaje si no hay resultados
    if (entesFiltrados.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="5" class="text-center">No se encontraron entes con los filtros seleccionados</td>
            </tr>
        `;
    }

    // Actualizar el contador con animación
    contadorResultados.style.animation = 'none';
    contadorResultados.offsetHeight; // Trigger reflow
    contadorResultados.style.animation = 'pulseCount 0.3s ease-out';
}

function limpiarFiltros() {
    document.getElementById('filtroEscuela').value = '';
    document.getElementById('filtroTipo').value = '';
    aplicarFiltros();
}

document.addEventListener('DOMContentLoaded', cargarEntes);

document.getElementById('addEnteForm').onsubmit = function(e) {
    e.preventDefault();
    const form = e.target;
    const data = Object.fromEntries(new FormData(form));
    fetch('../src/controllers/EnteController.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(resp => {
        if (resp.success) {
            Swal.fire('Éxito', resp.message, 'success');
            form.reset();
            bootstrap.Modal.getInstance(document.getElementById('addEnteModal')).hide();
            cargarEntes();
        } else {
            Swal.fire('Error', resp.error || 'No se pudo crear el ente', 'error');
        }
    });
};

function editarEnte(e) {
    document.getElementById('edit_id_ente').value = e.id_ente;
    document.getElementById('edit_nombre').value = e.nombre;
    document.getElementById('edit_tipo').value = e.tipo;
    document.getElementById('edit_escuela').value = e.escuela;
    document.getElementById('edit_descripcion').value = e.descripcion || '';
    document.getElementById('edit_imagen').value = e.imagen || '';
    document.getElementById('edit_email_contacto').value = e.email_contacto || '';
    document.getElementById('edit_nombre_responsable').value = e.nombre_responsable || '';
    document.getElementById('edit_apellido_responsable').value = e.apellido_responsable || '';
    new bootstrap.Modal(document.getElementById('editEnteModal')).show();
}

document.getElementById('editEnteForm').onsubmit = function(e) {
    e.preventDefault();
    const form = e.target;
    const id = document.getElementById('edit_id_ente').value;
    const data = Object.fromEntries(new FormData(form));
    fetch(`../src/controllers/EnteController.php?id=${id}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(resp => {
        if (resp.success) {
            Swal.fire('Éxito', resp.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('editEnteModal')).hide();
            cargarEntes();
        } else {
            Swal.fire('Error', resp.error || 'No se pudo actualizar', 'error');
        }
    });
};

function eliminarEnte(id) {
    Swal.fire({
        title: '¿Eliminar ente?',
        text: 'Esta acción no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then(result => {
        if (result.isConfirmed) {
            fetch(`../src/controllers/EnteController.php?id=${id}`, {
                method: 'DELETE'
            })
            .then(r => r.json())
            .then(resp => {
                if (resp.success) {
                    Swal.fire('Eliminado', resp.message, 'success');
                    cargarEntes();
                } else {
                    Swal.fire('Error', resp.error || 'No se pudo eliminar', 'error');
                }
            });
        }
    });
}

document.getElementById('importExcelForm').onsubmit = function(e) {
    e.preventDefault();
    const formData = new FormData(e.target);

    fetch('../src/controllers/ImportController.php', {
        method: 'POST',
        body: formData
    })
    .then(r => {
        if (!r.ok) {
            throw new Error(`Error en la respuesta del servidor: ${r.status}`);
        }
        return r.json();
    })
    .then(resp => {
        if (resp.success) {
            Swal.fire('Éxito', resp.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('importExcelModal')).hide();
            cargarEntes(); // Recargar la tabla de entes
        } else {
            Swal.fire('Error', resp.error || 'No se pudo importar el archivo', 'error');
        }
    })
    .catch(err => {
        Swal.fire('Error', 'Hubo un problema al procesar el archivo', 'error');
        console.error(err);
    });
};
</script>
<?php require_once 'admin_footer.php'; ?>
