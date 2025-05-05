<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No se pudo cargar el archivo');
    }

    $stats = [
        'imported' => 0,
        'updated' => 0,
        'errors' => 0
    ];

    $file = $_FILES['file']['tmp_name'];
    $spreadsheet = IOFactory::load($file);
    $worksheet = $spreadsheet->getActiveSheet();
    $rows = $worksheet->toArray();

    // Validar encabezados esperados
    $expectedHeaders = [
        'Centro/Grupo', 'Código', 'Actividad Operativa', 'Tip.', 'Escuela',
        '1T', '2T', '3T', '4T', 'Monto', 'Estado', 'Prioridad', 'INFORME'
    ];

    $headers = array_map('trim', $rows[0]);
    if ($headers !== $expectedHeaders) {
        throw new Exception('El formato del archivo no coincide con lo esperado');
    }

    // Iniciar transacción
    $pdo->beginTransaction();

    // Preparar statements
    $stmtEnte = $pdo->prepare("SELECT id_ente FROM ente WHERE nombre = ? AND escuela = ? LIMIT 1");
    $stmtCheckActividad = $pdo->prepare("SELECT id_actividad FROM actividades_poi WHERE codigo_actividad = ?");
    $stmtInsert = $pdo->prepare("
        INSERT INTO actividades_poi (
            codigo_actividad, nombre_actividad, id_ente, categoria,
            porcentaje_1t, porcentaje_2t, porcentaje_3t, porcentaje_4t,
            monto_financiamiento, estado_ejecucion, prioridad, observaciones
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmtUpdate = $pdo->prepare("
        UPDATE actividades_poi SET 
            nombre_actividad = ?, id_ente = ?, categoria = ?,
            porcentaje_1t = ?, porcentaje_2t = ?, porcentaje_3t = ?, porcentaje_4t = ?,
            monto_financiamiento = ?, estado_ejecucion = ?, prioridad = ?, observaciones = ?
        WHERE codigo_actividad = ?
    ");

    // Función de validación
    function validateRow($row, $rowNumber) {
        $errors = [];
        
        list($centroGrupo, $codigo, $actividad, $tipo, $escuela,
             $t1, $t2, $t3, $t4, $monto, $estado, $prioridad, $informe) = array_map('trim', $row);

        // Validar categoría
        $categoriasValidas = ['CAPACITACION', 'INVESTIGACION', 'GESTION', 'ACTIVIDAD'];
        if (!empty($tipo) && !in_array(strtoupper($tipo), $categoriasValidas)) {
            $errors[] = "Fila $rowNumber: Categoría inválida. Debe ser una de: " . implode(', ', $categoriasValidas);
        }

        // Definir procesos válidos según categoría
        $procesosValidos = [
            'CAPACITACION' => [
                'CAPACITACION_SERVICIOS',
                'CAPACITACION_CERTIFICACION',
                'CAPACITACION_EJECUCION'
            ],
            'INVESTIGACION' => [
                'INVESTIGACION_PROYECTO',
                'INVESTIGACION_ARTICULO',
                'INVESTIGACION_PUBLICACION'
            ],
            'GESTION' => [
                'GESTION_ADMINISTRATIVA',
                'GESTION_ACADEMICA',
                'GESTION_FINANCIERA'
            ],
            'ACTIVIDAD' => [
                'ACTIVIDAD_ACADEMICA',
                'ACTIVIDAD_CULTURAL',
                'ACTIVIDAD_EXTENSION'
            ]
        ];

        // Validar Centro/Grupo
        if (empty($centroGrupo)) {
            $errors[] = "Fila $rowNumber: Centro/Grupo está vacío";
        }

        // Validar Código
        if (empty($codigo)) {
            $errors[] = "Fila $rowNumber: Código está vacío";
        } elseif (!preg_match('/^[A-Z0-9-]+$/', $codigo)) {
            $errors[] = "Fila $rowNumber: Formato de código inválido (debe contener solo mayúsculas, números y guiones)";
        }

        // Validar porcentajes
        foreach ([$t1, $t2, $t3, $t4] as $index => $value) {
            $trimValue = str_replace(['%', ','], ['', '.'], $value);
            if (!is_numeric($trimValue)) {
                $errors[] = "Fila $rowNumber: Porcentaje T" . ($index + 1) . " debe ser numérico";
            } elseif (floatval($trimValue) < 0 || floatval($trimValue) > 100) {
                $errors[] = "Fila $rowNumber: Porcentaje T" . ($index + 1) . " debe estar entre 0 y 100";
            }
        }

        // Validar monto
        $montoLimpio = str_replace(['S/', ','], ['', ''], $monto);
        if (!is_numeric($montoLimpio)) {
            $errors[] = "Fila $rowNumber: Monto debe ser numérico";
        } elseif (floatval($montoLimpio) < 0) {
            $errors[] = "Fila $rowNumber: Monto no puede ser negativo";
        }

        // Validar tipo
        $tiposValidos = ['CAPA', 'INVE', 'ACTI', 'GEST'];
        if (!empty($tipo) && !in_array($tipo, $tiposValidos)) {
            $errors[] = "Fila $rowNumber: Tipo inválido. Debe ser uno de: " . implode(', ', $tiposValidos);
        }

        // Validar escuela
        $escuelasValidas = ['Arquitectura', 'Ing Civil', 'Ing Sistemas', 'Ing Ambiental', 'Ing Industrial', 'UI-FIA'];
        if (!in_array($escuela, $escuelasValidas)) {
            $errors[] = "Fila $rowNumber: Escuela inválida. Debe ser una de: " . implode(', ', $escuelasValidas);
        }

        return $errors;
    }

    // Procesar filas con validación
    $allErrors = [];
    foreach (array_slice($rows, 1) as $index => $row) {
        $rowNumber = $index + 2; // +2 porque empezamos desde la segunda fila y los índices empiezan en 0
        $errors = validateRow($row, $rowNumber);
        
        if (!empty($errors)) {
            $allErrors = array_merge($allErrors, $errors);
            $stats['errors']++;
            continue;
        }

        try {
            list(
                $centroGrupo, $codigo, $actividad, $tipo, $escuela,
                $t1, $t2, $t3, $t4, $monto, $estado, $prioridad, $informe
            ) = array_map('trim', $row);

            // Obtener id_ente
            $stmtEnte->execute([$centroGrupo, $escuela]);
            $id_ente = $stmtEnte->fetchColumn();
            if (!$id_ente) {
                $stats['errors']++;
                continue;
            }

            // Limpiar y convertir valores
            $t1 = floatval(str_replace(['%', ','], ['', '.'], $t1));
            $t2 = floatval(str_replace(['%', ','], ['', '.'], $t2));
            $t3 = floatval(str_replace(['%', ','], ['', '.'], $t3));
            $t4 = floatval(str_replace(['%', ','], ['', '.'], $t4));
            $monto = floatval(str_replace(['S/', ','], ['', ''], $monto));

            // Verificar si la actividad existe
            $stmtCheckActividad->execute([$codigo]);
            $exists = $stmtCheckActividad->fetchColumn();

            if ($exists) {
                // Actualizar
                $stmtUpdate->execute([
                    $actividad, $id_ente, $tipo,
                    $t1, $t2, $t3, $t4,
                    $monto, $estado, $prioridad, $informe,
                    $codigo
                ]);
                $stats['updated']++;
            } else {
                // Insertar
                $stmtInsert->execute([
                    $codigo, $actividad, $id_ente, $tipo,
                    $t1, $t2, $t3, $t4,
                    $monto, $estado, $prioridad, $informe
                ]);
                $stats['imported']++;
            }
        } catch (Exception $e) {
            $allErrors[] = "Fila $rowNumber: " . $e->getMessage();
            $stats['errors']++;
            continue;
        }
    }

    // Si hay errores, hacer rollback y mostrar todos los errores de forma detallada
    if (!empty($allErrors)) {
        $pdo->rollBack();
        echo json_encode([
            'success' => false,
            'stats' => $stats,
            'errors' => $allErrors,
            'error_details' => [
                'total_errors' => count($allErrors),
                'error_summary' => implode("\n", array_slice($allErrors, 0, 5)) . 
                    (count($allErrors) > 5 ? "\n...y " . (count($allErrors) - 5) . " errores más." : "")
            ]
        ]);
        exit;
    }

    $pdo->commit();
    echo json_encode([
        'success' => true,
        'message' => 'Importación completada',
        'stats' => $stats
    ]);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'error_type' => 'exception',
        'error_details' => [
            'message' => $e->getMessage(),
            'file' => basename($e->getFile()),
            'line' => $e->getLine()
        ]
    ]);
}
