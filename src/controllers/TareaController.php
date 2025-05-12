<?php
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');

function actualizarEstadoActividad($pdo, $codigoActividad) {
    // Obtener todos los estados_flujo de las tareas de la actividad
    $stmt = $pdo->prepare("SELECT estado_flujo FROM tareas_actividad WHERE codigo_actividad = ?");
    $stmt->execute([$codigoActividad]);
    $estados = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!$estados || count($estados) === 0) {
        // Si no hay tareas, dejar la actividad como NO_INICIADA
        $nuevoEstado = 'NO_INICIADA';
    } elseif (in_array('RECHAZADO', $estados)) {
        $nuevoEstado = 'CANCELADA';
    } elseif (count(array_unique($estados)) === 1 && $estados[0] === 'APROBADO') {
        $nuevoEstado = 'FINALIZADA';
    } elseif (count(array_unique($estados)) === 1 && $estados[0] === 'INICIO') {
        $nuevoEstado = 'NO_INICIADA';
    } elseif (in_array('APROBADO', $estados) && count(array_unique($estados)) === 1) {
        $nuevoEstado = 'FINALIZADA';
    } else {
        // Si hay al menos una tarea en proceso (ni INICIO, ni APROBADO, ni RECHAZADO)
        $enProceso = false;
        foreach ($estados as $estado) {
            if (!in_array($estado, ['INICIO', 'APROBADO', 'RECHAZADO'])) {
                $enProceso = true;
                break;
            }
        }
        $nuevoEstado = $enProceso ? 'EN_PROGRESO' : 'NO_INICIADA';
    }

    // Actualizar el estado de la actividad solo si es diferente
    $stmt = $pdo->prepare("UPDATE actividades_poi SET estado_ejecucion = ? WHERE codigo_actividad = ?");
    $stmt->execute([$nuevoEstado, $codigoActividad]);
}

// Permitir peticiones GET para obtener detalles de una tarea
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['codigo_tarea']) && isset($_GET['codigo_actividad'])) {
        $stmt = $pdo->prepare("
            SELECT t.codigo_actividad, t.codigo_tarea, t.descripcion, t.fecha_inicio, t.fecha_fin, t.estado_flujo, t.observado_en, a.nombre_actividad 
            FROM tareas_actividad t 
            LEFT JOIN actividades_poi a ON t.codigo_actividad = a.codigo_actividad 
            WHERE t.codigo_tarea = ? AND t.codigo_actividad = ?
        ");
        $stmt->execute([$_GET['codigo_tarea'], $_GET['codigo_actividad']]);
        $tarea = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($tarea) {
            echo json_encode(['success' => true, 'tarea' => $tarea]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Tarea no encontrada']);
        }
        exit;
    }

    if (isset($_GET['codigo_actividad'])) {
        $stmt = $pdo->prepare("SELECT codigo_actividad, codigo_tarea, descripcion, fecha_inicio, fecha_fin, estado_flujo, observado_en FROM tareas_actividad WHERE codigo_actividad = ? ORDER BY codigo_tarea");
        $stmt->execute([$_GET['codigo_actividad']]);
        $tareas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'tareas' => $tareas]);
        exit;
    }
}

// Crear nueva tarea
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = $_POST;

    // Validar datos requeridos
    $required = ['codigo_actividad', 'descripcion', 'estado_flujo'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Falta el campo: $field"]);
            exit;
        }
    }

    try {
        // Validar que el código de actividad existe
        $stmt = $pdo->prepare("SELECT codigo_actividad FROM actividades_poi WHERE codigo_actividad = ?");
        $stmt->execute([$data['codigo_actividad']]);
        if (!$stmt->fetch()) {
            throw new Exception('El código de actividad no existe');
        }

        $estadoFlujo = strtoupper($data['estado_flujo']);

        // Generar código único para la tarea: ACT-0001-EVAL_CGYC → ACT-0001-EC
        $actividad = $data['codigo_actividad'];
        $iniciales = '';
        if (!empty($data['tipo_proceso'])) {
            // Si se envía tipo_proceso, usar sus iniciales
            $partes = explode('_', $data['tipo_proceso']);
            foreach ($partes as $p) {
                $iniciales .= strtoupper(substr($p, 0, 1));
            }
        } else {
            // Si no, usar las iniciales del estado_flujo
            $partes = explode('_', $estadoFlujo);
            foreach ($partes as $p) {
                $iniciales .= strtoupper(substr($p, 0, 1));
            }
        }
        // Si ya existe una tarea con ese código, agregar un número incremental
        $codigo_tarea_base = $actividad . '-' . $iniciales;
        $codigo_tarea = $codigo_tarea_base;
        $contador = 1;
        while (true) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM tareas_actividad WHERE codigo_actividad = ? AND codigo_tarea = ?");
            $stmt->execute([$actividad, $codigo_tarea]);
            if ($stmt->fetchColumn() == 0) break;
            $codigo_tarea = $codigo_tarea_base . $contador;
            $contador++;
        }

        // Si la tarea se crea en estado OBSERVADO, guardar el estado anterior si se envía
        $observado_en = null;
        if ($estadoFlujo === 'OBSERVADO') {
            $observado_en = !empty($data['observado_en']) ? $data['observado_en'] : 'INICIO';
        }

        $stmt = $pdo->prepare("INSERT INTO tareas_actividad (
            codigo_actividad, codigo_tarea, descripcion, 
            fecha_inicio, fecha_fin, estado_flujo, observado_en
        ) VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $data['codigo_actividad'],
            $codigo_tarea,
            $data['descripcion'],
            $data['fecha_inicio'] ?? null,
            $data['fecha_fin'] ?? null,
            $estadoFlujo,
            $observado_en
        ]);
        
        // Actualizar estado de la actividad
        actualizarEstadoActividad($pdo, $data['codigo_actividad']);
        echo json_encode(['success' => true, 'message' => 'Tarea creada correctamente', 'codigo_tarea' => $codigo_tarea]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Error al crear la tarea: ' . $e->getMessage()]);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Actualizar tarea existente
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    try {
        // Parse the input data
        parse_str(file_get_contents('php://input'), $data);

        // Validate required fields
        $requiredFields = ['codigo_tarea', 'codigo_actividad', 'descripcion', 'fecha_inicio', 'fecha_fin', 'estado_flujo'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                throw new Exception("El campo $field es obligatorio");
            }
        }

        // Leer el estado anterior de la tarea
        $stmt = $pdo->prepare('SELECT estado_flujo, observado_en FROM tareas_actividad WHERE codigo_tarea = ? AND codigo_actividad = ?');
        $stmt->execute([$data['codigo_tarea'], $data['codigo_actividad']]);
        $tareaActual = $stmt->fetch(PDO::FETCH_ASSOC);
        $estadoAnterior = $tareaActual ? $tareaActual['estado_flujo'] : null;

        $nuevoEstado = strtoupper($data['estado_flujo']);
        $observado_en = null;

        if ($nuevoEstado === 'OBSERVADO') {
            // Guardar el estado anterior si se cambia a OBSERVADO
            $observado_en = $estadoAnterior && $estadoAnterior !== 'OBSERVADO' ? $estadoAnterior : 'INICIO';
        } else {
            // Si no está en observado, limpiar el campo
            $observado_en = null;
        }

        // Update the task in the database
        $stmt = $pdo->prepare('
            UPDATE tareas_actividad 
            SET descripcion = ?, fecha_inicio = ?, fecha_fin = ?, estado_flujo = ?, observado_en = ?
            WHERE codigo_tarea = ? AND codigo_actividad = ?
        ');
        $stmt->execute([
            $data['descripcion'],
            $data['fecha_inicio'],
            $data['fecha_fin'],
            $data['estado_flujo'],
            $observado_en,
            $data['codigo_tarea'],
            $data['codigo_actividad']
        ]);

        // Actualizar estado de la actividad
        actualizarEstadoActividad($pdo, $data['codigo_actividad']);
        echo json_encode(['success' => true, 'message' => 'Tarea actualizada correctamente']);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Eliminar tarea
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    parse_str(file_get_contents("php://input"), $data);

    // Permitir que codigo_tarea venga como "ACT-0001-B" o solo la letra
    if (!empty($data['codigo_tarea']) && strpos($data['codigo_tarea'], '-') !== false && empty($data['codigo_actividad'])) {
        // Separar el código completo
        $partes = explode('-', $data['codigo_tarea']);
        $data['codigo_actividad'] = implode('-', array_slice($partes, 0, -1));
        $data['codigo_tarea'] = end($partes);
    }

    if (empty($data['codigo_tarea']) || empty($data['codigo_actividad'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Faltan datos para eliminar la tarea']);
        exit;
    }

    try {
        // Verificar si hay documentos asociados
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM documentos WHERE codigo_tarea = ? AND codigo_actividad = ?");
        $stmt->execute([$data['codigo_tarea'], $data['codigo_actividad']]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception('No se puede eliminar la tarea porque tiene documentos asociados');
        }

        $stmt = $pdo->prepare("DELETE FROM tareas_actividad WHERE codigo_tarea = ? AND codigo_actividad = ?");
        $stmt->execute([$data['codigo_tarea'], $data['codigo_actividad']]);
        
        if ($stmt->rowCount() > 0) {
            // Actualizar estado de la actividad
            actualizarEstadoActividad($pdo, $data['codigo_actividad']);
            echo json_encode(['success' => true, 'message' => 'Tarea eliminada correctamente']);
        } else {
            echo json_encode(['success' => false, 'error' => 'No se encontró la tarea']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Error al eliminar la tarea: ' . $e->getMessage()]);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Método no permitido']);
?>