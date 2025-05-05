function editActividad(actividad) {
    document.getElementById('edit_id_actividad').value = actividad.id_actividad;
    document.getElementById('edit_id_ente').value = actividad.id_ente;
    document.getElementById('edit_codigo_actividad').value = actividad.codigo_actividad;
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

function deleteActividad(id) {
    if (confirm('¿Está seguro de eliminar esta actividad?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="csrf_token" value="${window.csrf_token || ''}">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id_actividad" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function showDocumentos(codigo_actividad, codigo_tarea) {
    document.getElementById('doc_codigo_actividad').value = codigo_actividad;
    document.getElementById('doc_codigo_tarea').value = codigo_tarea;
    fetch(`../src/controllers/DocumentoController.php?codigo_actividad=${codigo_actividad}&codigo_tarea=${codigo_tarea}&html=1`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('documentosTableContainer').innerHTML = html;
        });
    new bootstrap.Modal(document.getElementById('documentosModal')).show();
}

function showTareas(codigo_actividad) {
    document.getElementById('tarea_codigo_actividad').value = codigo_actividad;
    document.getElementById('codigo_actividad_display').value = codigo_actividad;
    cargarTareas(codigo_actividad);
    new bootstrap.Modal(document.getElementById('tareasModal')).show();
}

function cargarTareas(id_actividad) {
    fetch(`../src/controllers/TareaController.php?id_actividad=${id_actividad}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const tbody = document.getElementById('tareasTableBody');
                tbody.innerHTML = '';
                data.tareas.forEach(tarea => {
                    tbody.innerHTML += `
                        <tr>
                            <td>${tarea.descripcion}</td>
                            <td>${tarea.fecha_inicio || 'No definida'}</td>
                            <td>${tarea.fecha_fin || 'No definida'}</td>
                            <td>${tarea.estado_flujo}</td>
                        </tr>
                    `;
                });
            }
        });
}

// Subida de documento por AJAX (recarga la tabla HTML)
const uploadDocForm = document.getElementById('uploadDocForm');
if (uploadDocForm) {
    uploadDocForm.onsubmit = function(e) {
        e.preventDefault();
        const formData = new FormData(uploadDocForm);
        if (!formData.get('file')) {
            Swal.fire({icon:'error',title:'Seleccione un archivo'}); return;
        }
        fetch('../src/controllers/DocumentoController.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.text())
        .then(html => {
            Swal.fire({icon:'success',title:'Documento subido'});
            document.getElementById('documentosTableContainer').innerHTML = html;
            uploadDocForm.reset();
        });
    };
}

// Eliminar documento (recarga la tabla HTML)
function deleteDocumento(id_documento) {
    const codigo_actividad = document.getElementById('doc_codigo_actividad').value;
    const codigo_tarea = document.getElementById('doc_codigo_tarea').value;
    if (confirm('¿Está seguro de eliminar este documento?')) {
        fetch(`../src/controllers/DocumentoController.php?delete=${id_documento}&codigo_actividad=${codigo_actividad}&codigo_tarea=${codigo_tarea}&html=1`)
        .then(response => response.text())
        .then(html => {
            Swal.fire({icon:'success',title:'Documento eliminado'});
            document.getElementById('documentosTableContainer').innerHTML = html;
        });
    }
}

// Manejo del formulario para agregar/editar tarea
const addTareaForm = document.getElementById('addTareaForm');
const deleteTareaBtn = document.getElementById('deleteTareaBtn');

if (addTareaForm) {
    addTareaForm.onsubmit = function (e) {
        e.preventDefault();
        const formData = new FormData(addTareaForm);

        // Enviar los datos del formulario al backend
        fetch('../src/controllers/TareaController.php', {
            method: 'POST',
            body: formData,
        })
            .then((r) => r.json())
            .then((response) => {
                if (response.success) {
                    Swal.fire({ icon: 'success', title: 'Tarea guardada', text: `Código generado: ${response.codigo_tarea}` });
                    cargarTareas(formData.get('codigo_actividad'));
                    addTareaForm.reset();
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: response.error });
                }
            });
    };
}

// Cargar datos de una tarea en el formulario al hacer clic en una fila
document.addEventListener('click', function (e) {
    if (e.target.closest('#tareasTableContainer tr')) {
        const row = e.target.closest('tr');
        const cells = row.querySelectorAll('td');
        const codigoCompleto = cells[0].textContent.trim();
        
        // Mostrar el código completo en el campo de visualización
        document.getElementById('codigo_tarea_display').value = codigoCompleto;
        // Guardar solo la letra en el campo oculto
        document.getElementById('codigo_tarea').value = codigoCompleto.split('-')[1];
        
        document.getElementById('descripcion').value = cells[1].textContent.trim();
        document.getElementById('tipo_tarea').value = cells[2].textContent.trim();
        document.getElementById('porcentaje_1t').value = cells[3].textContent.trim();
        document.getElementById('porcentaje_2t').value = cells[4].textContent.trim();
        document.getElementById('porcentaje_3t').value = cells[5].textContent.trim();
        document.getElementById('porcentaje_4t').value = cells[6].textContent.trim();
        document.getElementById('codigo_tarea_original').value = cells[0].textContent.trim(); // Guardar código original
        deleteTareaBtn.classList.remove('d-none'); // Mostrar botón de eliminar
    }
});

// Eliminar tarea desde el formulario
if (deleteTareaBtn) {
    deleteTareaBtn.onclick = function () {
        const codigoTarea = document.getElementById('codigo_tarea_original').value;
        const codigoActividad = document.getElementById('tarea_codigo_actividad').value;
        if (!codigoTarea || !codigoActividad) return;

        Swal.fire({
            title: '¿Eliminar tarea?',
            text: 'Esta acción no se puede deshacer',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`../src/controllers/TareaController.php`, {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `codigo_tarea=${encodeURIComponent(codigoTarea)}&codigo_actividad=${encodeURIComponent(codigoActividad)}`,
                })
                    .then((r) => r.json())
                    .then((response) => {
                        if (response.success) {
                            Swal.fire('Eliminado', 'La tarea ha sido eliminada', 'success');
                            cargarTareas(codigoActividad);
                            addTareaForm.reset();
                            document.getElementById('codigo_tarea_original').value = ''; // Limpiar campo oculto
                            deleteTareaBtn.classList.add('d-none'); // Ocultar botón de eliminar
                        } else {
                            Swal.fire('Error', response.error || 'No se pudo eliminar la tarea', 'error');
                        }
                    })
                    .catch(() => {
                        Swal.fire('Error', 'No se pudo conectar con el servidor', 'error');
                    });
            }
        });
    };
}

// Exponer csrf_token si es necesario para deleteActividad
window.csrf_token = document.querySelector('input[name="csrf_token"]')?.value || '';
