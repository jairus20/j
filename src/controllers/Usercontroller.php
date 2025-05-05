<?php
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        // Listar usuarios
        $stmt = $pdo->query('SELECT id_usuario, username, email, tipo_usuario, nombre, apellido FROM usuarios');
        $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'usuarios' => $usuarios]);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);

    if ($method === 'POST') {
        // Crear usuario
        $required = ['username', 'email', 'password', 'tipo_usuario', 'nombre', 'apellido'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                http_response_code(400);
                echo json_encode(['error' => "Falta el campo: $field"]);
                exit;
            }
        }
        // Verificar duplicados
        $check = $pdo->prepare('SELECT COUNT(*) FROM usuarios WHERE username = ? OR email = ?');
        $check->execute([$data['username'], $data['email']]);
        if ($check->fetchColumn() > 0) {
            http_response_code(409);
            echo json_encode(['error' => 'El usuario o email ya existe']);
            exit;
        }
        $stmt = $pdo->prepare('INSERT INTO usuarios (username, password_hash, tipo_usuario, email, nombre, apellido) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $data['username'],
            password_hash($data['password'], PASSWORD_DEFAULT),
            $data['tipo_usuario'],
            $data['email'],
            $data['nombre'],
            $data['apellido']
        ]);
        echo json_encode(['success' => true, 'message' => 'Usuario creado']);
        exit;
    }

    if ($method === 'PUT') {
        // Actualizar usuario
        if (empty($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Falta el id de usuario']);
            exit;
        }
        $id = $_GET['id'];
        $required = ['username', 'email', 'tipo_usuario', 'nombre', 'apellido'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                http_response_code(400);
                echo json_encode(['error' => "Falta el campo: $field"]);
                exit;
            }
        }
        // Verificar duplicados
        $check = $pdo->prepare('SELECT COUNT(*) FROM usuarios WHERE (username = ? OR email = ?) AND id_usuario != ?');
        $check->execute([$data['username'], $data['email'], $id]);
        if ($check->fetchColumn() > 0) {
            http_response_code(409);
            echo json_encode(['error' => 'El usuario o email ya existe']);
            exit;
        }
        $sql = 'UPDATE usuarios SET username = ?, tipo_usuario = ?, email = ?, nombre = ?, apellido = ?';
        $params = [$data['username'], $data['tipo_usuario'], $data['email'], $data['nombre'], $data['apellido']];
        if (!empty($data['password'])) {
            $sql .= ', password_hash = ?';
            $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        $sql .= ' WHERE id_usuario = ?';
        $params[] = $id;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        echo json_encode(['success' => true, 'message' => 'Usuario actualizado']);
        exit;
    }

    if ($method === 'DELETE') {
        // Eliminar usuario
        if (empty($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Falta el id de usuario']);
            exit;
        }
        $id = $_GET['id'];
        $stmt = $pdo->prepare('DELETE FROM usuarios WHERE id_usuario = ?');
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'Usuario eliminado']);
        exit;
    }

    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de base de datos: ' . $e->getMessage()]);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error: ' . $e->getMessage()]);
    exit;
}
