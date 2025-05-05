<?php
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        // Listar entes
        $stmt = $pdo->query('SELECT * FROM ente');
        $entes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'entes' => $entes]);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);

    if ($method === 'POST') {
        // Crear ente
        $required = ['nombre', 'tipo', 'escuela'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                http_response_code(400);
                echo json_encode(['error' => "Falta el campo: $field"]);
                exit;
            }
        }
        $stmt = $pdo->prepare('INSERT INTO ente (nombre, tipo, escuela, descripcion, imagen, email_contacto) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $data['nombre'],
            $data['tipo'],
            $data['escuela'],
            $data['descripcion'] ?? null,
            $data['imagen'] ?? null,
            $data['email_contacto'] ?? null
        ]);

        // Asignar rol predeterminado según el tipo de ente
        $id_ente = $pdo->lastInsertId();
        $stmt = $pdo->prepare('SELECT id_rol FROM roles_miembros WHERE nombre_rol = ?');
        $rol = match ($data['tipo']) {
            'GRUPO' => 'COORDINADOR',
            'CIRCULO' => 'PRESIDENTE',
            default => 'ASESOR'
        };
        $stmt->execute([$rol]);
        $id_rol = $stmt->fetchColumn();

        if (!$id_rol) {
            http_response_code(500);
            echo json_encode(['error' => 'No se encontró el rol correspondiente']);
            exit;
        }

        // Validar que el rol sea consistente con el tipo de ente
        if (
            ($rol === 'COORDINADOR' && $data['tipo'] !== 'GRUPO') ||
            ($rol === 'PRESIDENTE' && $data['tipo'] !== 'CIRCULO') ||
            ($rol === 'ASESOR' && !in_array($data['tipo'], ['GRUPO', 'CIRCULO']))
        ) {
            http_response_code(400);
            echo json_encode(['error' => 'El rol no es válido para el tipo de ente']);
            exit;
        }

        $stmt = $pdo->prepare('INSERT INTO miembros (id_ente, nombre, apellido, email, id_rol) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([
            $id_ente,
            $data['nombre_responsable'] ?? 'Responsable',
            $data['apellido_responsable'] ?? 'Predeterminado',
            $data['email_contacto'] ?? 'sin_email@ejemplo.com',
            $id_rol
        ]);

        echo json_encode(['success' => true, 'message' => 'Ente creado con rol asignado']);
        exit;
    }

    if ($method === 'PUT') {
        // Actualizar ente
        if (empty($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Falta el id del ente']);
            exit;
        }
        $id = $_GET['id'];
        
        // Validar campos requeridos
        $required = ['nombre', 'tipo', 'escuela'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                http_response_code(400);
                echo json_encode(['error' => "Falta el campo: $field"]);
                exit;
            }
        }
        
        // Actualizar campos adicionales
        $stmt = $pdo->prepare('
            UPDATE ente 
            SET nombre = ?, 
                tipo = ?, 
                escuela = ?, 
                descripcion = ?, 
                imagen = ?, 
                email_contacto = ?,
                pagina_web = ?,
                resolucion = ?,
                estado = ?
            WHERE id_ente = ?
        ');
        
        $stmt->execute([
            $data['nombre'],
            $data['tipo'],
            $data['escuela'],
            $data['descripcion'] ?? null,
            $data['imagen'] ?? null,
            $data['email_contacto'] ?? null,
            $data['pagina_web'] ?? null,
            $data['resolucion'] ?? null,
            $data['estado'] ?? 'ACTIVO',
            $id
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Ente actualizado']);
        exit;
    }

    if ($method === 'DELETE') {
        // Eliminar ente
        if (empty($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Falta el id del ente']);
            exit;
        }
        $id = $_GET['id'];
        $stmt = $pdo->prepare('DELETE FROM ente WHERE id_ente = ?');
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'Ente eliminado']);
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
