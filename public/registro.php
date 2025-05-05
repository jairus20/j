<?php
require_once '../src/views/header.php';
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<h2>Registro de Usuario</h2>
<form action="/register" method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
    <label for="username">Usuario:</label>
    <input type="text" name="username" id="username" required>
    <br><br>
    <label for="email">Correo:</label>
    <input type="email" name="email" id="email" required>
    <br><br>
    <label for="password">Contraseña:</label>
    <input type="password" name="password" id="password" required>
    <br><br>
    <button type="submit">Registrar</button>
</form>

<?php
require_once '../src/views/footer.php';
?>
