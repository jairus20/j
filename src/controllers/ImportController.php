<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php'; // PhpSpreadsheet autoload

use PhpOffice\PhpSpreadsheet\IOFactory;

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'No se pudo cargar el archivo']);
            exit;
        }

        $file = $_FILES['file']['tmp_name'];
        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        // Validar encabezados
        $expectedHeaders = ['nombre', 'escuela', 'tipo']; // Normalizados a minúsculas
        $fileHeaders = array_map(fn($header) => strtolower(trim($header)), $rows[0]); // Normalizar encabezados del archivo
        if ($fileHeaders !== $expectedHeaders) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'El archivo no tiene los encabezados esperados: ' . implode(', ', $expectedHeaders)]);
            exit;
        }

        // Mapeo de valores permitidos para la columna "Escuela"
        $escuelaMap = [
            'arquitectura' => 'Arquitectura',
            'ing civil' => 'Ing Civil',
            'ing sistemas' => 'Ing Sistemas',
            'ing ambiental' => 'Ing Ambiental',
            'ing industrial' => 'Ing Industrial',
            'ui-fia' => 'UI-FIA'
        ];

        // Procesar filas
        $stmtCheck = $pdo->prepare('SELECT COUNT(*) FROM ente WHERE nombre = ? AND escuela = ? AND tipo = ?');
        $stmtInsert = $pdo->prepare('INSERT INTO ente (nombre, escuela, tipo, descripcion, email_contacto) VALUES (?, ?, ?, ?, ?)');
        $imported = 0;
        $skipped = 0;

        foreach (array_slice($rows, 1) as $row) {
            if (count($row) < 3) continue; // Saltar filas incompletas
            [$nombre, $escuela, $tipo] = $row;

            // Normalizar el valor de "Escuela"
            $escuelaKey = strtolower(trim($escuela));
            if (!isset($escuelaMap[$escuelaKey])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => "El valor de 'Escuela' no es válido: $escuela"]);
                exit;
            }
            $escuela = $escuelaMap[$escuelaKey];

            // Verificar si el registro ya existe
            $stmtCheck->execute([$nombre, $escuela, $tipo]);
            if ($stmtCheck->fetchColumn() > 0) {
                $skipped++;
                continue; // Saltar si ya existe
            }

            // Agregar valores predeterminados para campos faltantes
            $descripcion = 'Descripción predeterminada';
            $email_contacto = 'placeholder@email.com';

            // Insertar el registro
            $stmtInsert->execute([$nombre, $escuela, $tipo, $descripcion, $email_contacto]);
            $imported++;
        }

        echo json_encode([
            'success' => true,
            'message' => "$imported entes importados correctamente. $skipped registros ya existían y fueron ignorados."
        ]);
        exit;
    }

    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error: ' . $e->getMessage()]);
    exit;
}
