<?php
require_once 'admin_header.php';
?>
<div class="main-content">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Gestión de Usuarios</h2>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class='bx bx-plus-circle'></i> Nuevo Usuario
            </button>
        </div>
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="usuariosTable">
                        <thead>
                            <tr>
                                <th>Usuario</th>
                                <th>Nombre</th>
                                <th>Apellido</th>
                                <th>Email</th>
                                <th>Tipo</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Usuarios se cargan por JS -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Agregar Usuario -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nuevo Usuario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addUserForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Usuario</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nombre</label>
                        <input type="text" name="nombre" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Apellido</label>
                        <input type="text" name="apellido" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipo de Usuario</label>
                        <select name="tipo_usuario" class="form-select" required>
                            <option value="">Seleccione...</option>
                            <option value="ADMIN">Admin</option>
                            <option value="DOCENTE">Docente</option>
                            <option value="OTRO">Otro</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contraseña</label>
                        <input type="password" name="password" class="form-control" required>
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

<!-- Modal para Editar Usuario -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Usuario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editUserForm">
                <input type="hidden" name="id_usuario" id="edit_id_usuario">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Usuario</label>
                        <input type="text" name="username" id="edit_username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nombre</label>
                        <input type="text" name="nombre" id="edit_nombre" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Apellido</label>
                        <input type="text" name="apellido" id="edit_apellido" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" id="edit_email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipo de Usuario</label>
                        <select name="tipo_usuario" id="edit_tipo_usuario" class="form-select" required>
                            <option value="ADMIN">Admin</option>
                            <option value="DOCENTE">Docente</option>
                            <option value="OTRO">Otro</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contraseña (dejar vacío para no cambiar)</label>
                        <input type="password" name="password" id="edit_password" class="form-control">
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

<script>
function cargarUsuarios() {
    fetch('../src/controllers/Usercontroller.php')
        .then(r => r.json())
        .then(data => {
            const tbody = document.querySelector('#usuariosTable tbody');
            tbody.innerHTML = '';
            if (data.usuarios) {
                data.usuarios.forEach(u => {
                    tbody.innerHTML += `
                        <tr>
                            <td>${u.username}</td>
                            <td>${u.nombre}</td>
                            <td>${u.apellido}</td>
                            <td>${u.email}</td>
                            <td>${u.tipo_usuario}</td>
                            <td>
                                <button class="btn btn-sm btn-info" onclick='editarUsuario(${JSON.stringify(u)})'>
                                    <i class='bx bx-edit'></i>
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="eliminarUsuario(${u.id_usuario})">
                                    <i class='bx bx-trash'></i>
                                </button>
                            </td>
                        </tr>
                    `;
                });
            }
        });
}

document.addEventListener('DOMContentLoaded', cargarUsuarios);

document.getElementById('addUserForm').onsubmit = function(e) {
    e.preventDefault();
    const form = e.target;
    const data = Object.fromEntries(new FormData(form));
    fetch('../src/controllers/Usercontroller.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(resp => {
        if (resp.success) {
            Swal.fire('Éxito', resp.message, 'success');
            form.reset();
            bootstrap.Modal.getInstance(document.getElementById('addUserModal')).hide();
            cargarUsuarios();
        } else {
            Swal.fire('Error', resp.error || 'No se pudo crear el usuario', 'error');
        }
    });
};

function editarUsuario(u) {
    document.getElementById('edit_id_usuario').value = u.id_usuario;
    document.getElementById('edit_username').value = u.username;
    document.getElementById('edit_nombre').value = u.nombre;
    document.getElementById('edit_apellido').value = u.apellido;
    document.getElementById('edit_email').value = u.email;
    document.getElementById('edit_tipo_usuario').value = u.tipo_usuario;
    document.getElementById('edit_password').value = '';
    new bootstrap.Modal(document.getElementById('editUserModal')).show();
}

document.getElementById('editUserForm').onsubmit = function(e) {
    e.preventDefault();
    const form = e.target;
    const id = document.getElementById('edit_id_usuario').value;
    const data = Object.fromEntries(new FormData(form));
    fetch(`../src/controllers/Usercontroller.php?id=${id}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(resp => {
        if (resp.success) {
            Swal.fire('Éxito', resp.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('editUserModal')).hide();
            cargarUsuarios();
        } else {
            Swal.fire('Error', resp.error || 'No se pudo actualizar', 'error');
        }
    });
};

function eliminarUsuario(id) {
    Swal.fire({
        title: '¿Eliminar usuario?',
        text: 'Esta acción no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then(result => {
        if (result.isConfirmed) {
            fetch(`../src/controllers/Usercontroller.php?id=${id}`, {
                method: 'DELETE'
            })
            .then(r => r.json())
            .then(resp => {
                if (resp.success) {
                    Swal.fire('Eliminado', resp.message, 'success');
                    cargarUsuarios();
                } else {
                    Swal.fire('Error', resp.error || 'No se pudo eliminar', 'error');
                }
            });
        }
    });
}
</script>
<?php require_once 'admin_footer.php'; ?>
