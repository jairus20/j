function isAuthenticated() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /login.php');
        return false;
    }
    return true;
}

function hasRole($role) {
    if ($_SESSION['role'] !== $role) {
        header('Location: /unauthorized.php');
        return false;
    }
    return true;
}
