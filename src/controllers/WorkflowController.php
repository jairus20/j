<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        if (!isset($_GET['tipo'])) {
            throw new Exception('Tipo de proceso no especificado');
        }

        // Cambia require_once por include para que siempre retorne el array
        $workflows = include __DIR__ . '/../config/task_workflows.php';
        $tipo = $_GET['tipo'];

        if (!isset($workflows[$tipo])) {
            throw new Exception('Configuración no encontrada para el tipo de proceso');
        }

        echo json_encode([
            'success' => true,
            'data' => $workflows[$tipo]
        ]);

    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit;
}

http_response_code(405);
echo json_encode([
    'success' => false,
    'error' => 'Método no permitido'
]);
