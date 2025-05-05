<?php
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        // Permitir eliminación por GET para compatibilidad con el frontend actual
        if (isset($_GET['delete'])) {
            $id = $_GET['delete'];
            // Obtener ruta para borrar archivo físico
            $stmt = $pdo->prepare('SELECT ruta, codigo_actividad, codigo_tarea FROM documentos WHERE id_documento = ?');
            $stmt->execute([$id]);
            $doc = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($doc && !empty($doc['ruta'])) {
                $filePath = __DIR__ . '/../../../' . $doc['ruta'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
            $stmt = $pdo->prepare('DELETE FROM documentos WHERE id_documento = ?');
            $stmt->execute([$id]);
            // Si se pide HTML, recarga la tabla
            if (isset($_GET['html']) && !empty($doc['codigo_actividad']) && !empty($doc['codigo_tarea'])) {
                $_GET['codigo_actividad'] = $doc['codigo_actividad'];
                $_GET['codigo_tarea'] = $doc['codigo_tarea'];
                $_GET['html'] = 1;
                // Recargar la tabla como en el GET normal
                $where = 'WHERE d.codigo_actividad = ? AND d.codigo_tarea = ?';
                $params = [$doc['codigo_actividad'], $doc['codigo_tarea']];
                $stmt = $pdo->prepare('SELECT d.*, t.descripcion AS nombre_tarea FROM documentos d LEFT JOIN tareas_actividad t ON d.codigo_actividad = t.codigo_actividad AND d.codigo_tarea = t.codigo_tarea ' . $where . ' ORDER BY d.fecha_subida DESC');
                $stmt->execute($params);
                $documentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo '<table class="table table-striped">';
                echo '<thead><tr><th>Nombre</th><th>Descripción</th><th>Fecha</th><th>Acciones</th></tr></thead><tbody>';
                foreach ($documentos as $doc) {
                    echo '<tr>';
                    echo '<td><a href="../' . htmlspecialchars($doc['ruta']) . '" target="_blank">' . htmlspecialchars($doc['nombre_documento']) . '</a></td>';
                    echo '<td>' . htmlspecialchars($doc['descripcion']) . '</td>';
                    echo '<td>' . htmlspecialchars($doc['fecha_subida']) . '</td>';
                    echo '<td>
                        <a href="../' . htmlspecialchars($doc['ruta']) . '" class="btn btn-sm btn-info" target="_blank"><i class="bx bx-download"></i></a>
                        <button class="btn btn-sm btn-danger" onclick="deleteDocumento(' . $doc['id_documento'] . ')"><i class="bx bx-trash"></i></button>
                    </td>';
                    echo '</tr>';
                }
                echo '</tbody></table>';
                exit;
            }
            // Si no, responde en JSON
            echo json_encode(['success' => true, 'message' => 'Documento eliminado']);
            exit;
        }

        // Listar documentos (puede filtrar por codigo_actividad y codigo_tarea si se pasa por GET)
        $where = '';
        $params = [];
        if (!empty($_GET['codigo_actividad']) && !empty($_GET['codigo_tarea'])) {
            $where = 'WHERE d.codigo_actividad = ? AND d.codigo_tarea = ?';
            $params[] = $_GET['codigo_actividad'];
            $params[] = $_GET['codigo_tarea'];
        } elseif (!empty($_GET['codigo_actividad'])) {
            $where = 'WHERE d.codigo_actividad = ?';
            $params[] = $_GET['codigo_actividad'];
        }
        $stmt = $pdo->prepare('SELECT d.*, t.descripcion AS nombre_tarea FROM documentos d LEFT JOIN tareas_actividad t ON d.codigo_actividad = t.codigo_actividad AND d.codigo_tarea = t.codigo_tarea ' . $where . ' ORDER BY d.fecha_subida DESC');
        $stmt->execute($params);
        $documentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (isset($_GET['html'])) {
            echo '<table class="table table-striped">';
            echo '<thead><tr><th>Nombre</th><th>Descripción</th><th>Fecha</th><th>Acciones</th></tr></thead><tbody>';
            foreach ($documentos as $doc) {
                echo '<tr>';
                echo '<td><a href="../' . htmlspecialchars($doc['ruta']) . '" target="_blank">' . htmlspecialchars($doc['nombre_documento']) . '</a></td>';
                echo '<td>' . htmlspecialchars($doc['descripcion']) . '</td>';
                echo '<td>' . htmlspecialchars($doc['fecha_subida']) . '</td>';
                echo '<td>
                    <a href="../' . htmlspecialchars($doc['ruta']) . '" class="btn btn-sm btn-info" target="_blank"><i class="bx bx-download"></i></a>
                    <button class="btn btn-sm btn-danger" onclick="deleteDocumento(' . $doc['id_documento'] . ')"><i class="bx bx-trash"></i></button>
                </td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
            exit;
        }

        echo json_encode(['success' => true, 'documentos' => $documentos]);
        exit;
    }

    // Para POST multipart/form-data (subida de archivo)
    if ($method === 'POST') {
        if (empty($_POST['nombre_documento']) || !isset($_FILES['file']) || empty($_POST['codigo_actividad']) || empty($_POST['codigo_tarea'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Faltan campos obligatorios']);
            exit;
        }
        $nombre = $_POST['nombre_documento'];
        $descripcion = $_POST['descripcion'] ?? null;
        $codigo_actividad = $_POST['codigo_actividad'];
        $codigo_tarea = $_POST['codigo_tarea'];
        $file = $_FILES['file'];
        $uploadDir = __DIR__ . '/../../../public/uploads/';
        $relativeDir = 'public/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $filename = uniqid() . '_' . basename($file['name']);
        $ruta = $relativeDir . $filename;
        $destino = $uploadDir . $filename;
        if (!move_uploaded_file($file['tmp_name'], $destino)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Error al subir el archivo']);
            exit;
        }
        $stmt = $pdo->prepare('INSERT INTO documentos (codigo_actividad, codigo_tarea, nombre_documento, ruta, descripcion) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([
            $codigo_actividad,
            $codigo_tarea,
            $nombre,
            $ruta,
            $descripcion
        ]);
        echo json_encode(['success' => true, 'message' => 'Documento subido correctamente']);
        exit;
    }

    // Para DELETE
    if ($method === 'DELETE') {
        if (empty($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Falta el id del documento']);
            exit;
        }
        $id = $_GET['id'];
        // Obtener ruta para borrar archivo físico
        $stmt = $pdo->prepare('SELECT ruta FROM documentos WHERE id_documento = ?');
        $stmt->execute([$id]);
        $doc = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($doc && !empty($doc['ruta'])) {
            $filePath = __DIR__ . '/../../../' . $doc['ruta'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
        $stmt = $pdo->prepare('DELETE FROM documentos WHERE id_documento = ?');
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'Documento eliminado']);
        exit;
    }

    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de base de datos: ' . $e->getMessage()]);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error: ' . $e->getMessage()]);
    exit;
}
