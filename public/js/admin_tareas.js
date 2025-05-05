function editarTarea(codigoTarea) {
    // Lógica para cargar datos de la tarea en el formulario
    fetch(`../src/controllers/TareaController.php?codigo_tarea=${codigoTarea}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const tarea = data.tarea;
                document.getElementById('codigo_tarea').value = tarea.codigo_tarea;
                document.getElementById('descripcion').value = tarea.descripcion;
                document.getElementById('tipo_tarea').value = tarea.tipo_tarea;
                document.getElementById('porcentaje_1t').value = tarea.porcentaje_1t;
                document.getElementById('porcentaje_2t').value = tarea.porcentaje_2t;
                document.getElementById('porcentaje_3t').value = tarea.porcentaje_3t;
                document.getElementById('porcentaje_4t').value = tarea.porcentaje_4t;
                document.getElementById('monto').value = tarea.monto;
                document.getElementById('estado_flujo').value = tarea.estado_flujo;
                new bootstrap.Modal(document.getElementById('addTareaModal')).show();
            } else {
                Swal.fire('Error', 'No se pudo cargar la tarea', 'error');
            }
        });
}

function eliminarTarea(codigoTarea) {
    Swal.fire({
        title: '¿Eliminar tarea?',
        text: 'Esta acción no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then(result => {
        if (result.isConfirmed) {
            fetch(`../src/controllers/TareaController.php`, {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `codigo_tarea=${codigoTarea}`
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('Eliminado', 'La tarea ha sido eliminada', 'success');
                        location.reload();
                    } else {
                        Swal.fire('Error', 'No se pudo eliminar la tarea', 'error');
                    }
                });
        }
    });
}

document.addEventListener('DOMContentLoaded', function () {
    const tareaForm = document.getElementById('tareaForm');

    tareaForm.addEventListener('submit', function (e) {
        e.preventDefault(); // Prevenir el comportamiento por defecto del formulario

        const formData = new FormData(tareaForm);
        const isEdit = !!formData.get('codigo_tarea'); // Verificar si es edición o creación
        const url = '../src/controllers/TareaController.php';
        const method = isEdit ? 'PUT' : 'POST';

        fetch(url, {
            method: method,
            body: isEdit ? new URLSearchParams(formData) : formData,
            headers: isEdit ? { 'Content-Type': 'application/x-www-form-urlencoded' } : undefined
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Éxito', data.message, 'success');
                    tareaForm.reset(); // Limpiar el formulario
                    bootstrap.Modal.getInstance(document.getElementById('addTareaModal')).hide(); // Cerrar el modal
                    location.reload(); // Recargar la página para reflejar los cambios
                } else {
                    Swal.fire('Error', data.error || 'No se pudo guardar la tarea', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error', 'Hubo un problema al procesar la solicitud', 'error');
            });
    });

    // Validar campos requeridos antes de enviar
    function validarFormularioTarea() {
        const descripcion = document.getElementById('descripcion').value.trim();
        const fechaInicio = document.getElementById('fecha_inicio').value;
        const fechaFin = document.getElementById('fecha_fin').value;

        if (!descripcion) {
            Swal.fire('Error', 'La descripción es obligatoria', 'error');
            return false;
        }

        if (fechaInicio && fechaFin && new Date(fechaInicio) > new Date(fechaFin)) {
            Swal.fire('Error', 'La fecha de inicio no puede ser posterior a la fecha de fin', 'error');
            return false;
        }

        return true;
    }

    // Asociar la validación al formulario
    tareaForm.addEventListener('submit', function (e) {
        if (!validarFormularioTarea()) {
            e.preventDefault();
        }
    });
});
