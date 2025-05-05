<?php
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        if (isset($_GET['action']) && $_GET['action'] === 'nextCodigo') {
            try {
                $stmt = $pdo->query("SELECT MAX(codigo_actividad) AS max_codigo FROM actividades_poi");
                $maxCodigo = $stmt->fetch(PDO::FETCH_ASSOC)['max_codigo'];

                if ($maxCodigo) {
                    // Extract the numeric part of the code and increment it
                    preg_match('/ACT-(\d+)/', $maxCodigo, $matches);
                    $nextNumber = isset($matches[1]) ? (int)$matches[1] + 1 : 1;
                } else {
                    $nextNumber = 1; // Start with 1 if no activities exist
                }

                $nextCodigo = 'ACT-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
                echo json_encode(['success' => true, 'codigo_actividad' => $nextCodigo]);
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Error fetching next codigo_actividad: ' . $e->getMessage()]);
            }
            exit;
        }

        // Listar actividades con nombre de ente
        $stmt = $pdo->query('SELECT a.*, e.nombre AS nombre_ente FROM actividades_poi a JOIN ente e ON a.id_ente = e.id_ente');
        $actividades = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'actividades' => $actividades]);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);

    if ($method === 'POST') {
        $data = $_POST;

        // Validar categoría
        $categorias_validas = ['CAPACITACION', 'INVESTIGACION', 'GESTION', 'ACTIVIDAD'];
        if (!in_array($data['categoria'], $categorias_validas)) {
            echo json_encode(['success' => false, 'error' => 'Categoría inválida']);
            exit;
        }

        // Insertar actividad
        $stmt = $pdo->prepare("INSERT INTO actividades_poi (codigo_actividad, nombre_actividad, categoria, id_ente, meta, fecha_inicio, fecha_fin, monto_financiamiento, estado_ejecucion, observaciones) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $data['codigo_actividad'],
            $data['nombre_actividad'],
            $data['categoria'],
            $data['id_ente'],
            $data['meta'],
            $data['fecha_inicio'],
            $data['fecha_fin'],
            $data['monto_financiamiento'],
            $data['estado_ejecucion'],
            $data['observaciones']
        ]);

        echo json_encode(['success' => true, 'message' => 'Actividad creada']);
        exit;
    }

    if ($method === 'PUT') {
        parse_str(file_get_contents('php://input'), $data);

        if (empty($data['codigo_actividad'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Falta el código de la actividad']);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE actividades_poi SET nombre_actividad = ?, meta = ?, fecha_inicio = ?, fecha_fin = ?, monto_financiamiento = ?, estado_ejecucion = ?, observaciones = ? WHERE codigo_actividad = ?");
        $stmt->execute([
            $data['nombre_actividad'],
            $data['meta'],
            $data['fecha_inicio'],
            $data['fecha_fin'],
            $data['monto_financiamiento'],
            $data['estado_ejecucion'],
            $data['observaciones'],
            $data['codigo_actividad']
        ]);

        echo json_encode(['success' => true, 'message' => 'Actividad actualizada']);
        exit;
    }

    if ($method === 'DELETE') {
        // Eliminar actividad
        if (empty($_GET['codigo_actividad'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Falta el código de la actividad']);
            exit;
        }
        $codigo_actividad = $_GET['codigo_actividad'];
        $stmt = $pdo->prepare('DELETE FROM actividades_poi WHERE codigo_actividad = ?');
        $stmt->execute([$codigo_actividad]);
        echo json_encode(['success' => true, 'message' => 'Actividad eliminada']);
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
