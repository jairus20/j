<?php
require_once __DIR__ . '/../config/database.php';
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

header('Content-Type: application/json');

// Constantes para validación
const TIPOS_VALIDOS = ['CAPACITACION', 'INVESTIGACION', 'GESTION', 'ACTIVIDAD'];
const ESCUELAS_VALIDAS = ['Arquitectura', 'Ing Civil', 'Ing Sistemas', 'Ing Ambiental', 'Ing Industrial', 'UI-FIA'];
const COLUMNAS_REQUERIDAS = [
    'Centro/Grupo',      // Nombre del centro (debe existir en tabla ente)
    'Código',           // código_actividad (único)
    'Actividad',        // nombre_actividad
    'Tipo',            // categoria (CAPACITACION, etc)
    'Escuela',         // escuela (debe coincidir con la del ente)
    '1T %',            // porcentaje_1t (0-100)
    '2T %',            // porcentaje_2t (0-100) 
    '3T %',            // porcentaje_3t (0-100)
    '4T %',            // porcentaje_4t (0-100)
    'Monto'            // monto_financiamiento
];

function validarPorcentaje($valor) {
    return is_numeric($valor) && $valor >= 0 && $valor <= 100;
}

function validarCabecerasExcel($worksheet) {
    $cabeceras = [];
    for ($col = 1; $col <= count(COLUMNAS_REQUERIDAS); $col++) {
        $cabeceras[] = $worksheet->getCellByColumnAndRow($col, 1)->getValue();
    }
    
    $faltantes = array_diff(COLUMNAS_REQUERIDAS, $cabeceras);
    if (!empty($faltantes)) {
        throw new Exception('Faltan las siguientes columnas: ' . implode(', ', $faltantes));
    }
    return true;
}

function procesarExcel($archivo) {
    global $pdo;
    $errores = [];
    $stats = ['imported' => 0, 'updated' => 0, 'errors' => 0];

    try {
        $spreadsheet = IOFactory::load($archivo['tmp_name']);
        $worksheet = $spreadsheet->getActiveSheet();
        
        // Validar cabeceras
        validarCabecerasExcel($worksheet);
        $highestRow = $worksheet->getHighestRow();

        // Cache de entes para evitar múltiples consultas
        $entes = [];
        $stmt = $pdo->query("SELECT id_ente, nombre, escuela FROM ente");
        while ($row = $stmt->fetch()) {
            $entes[$row['nombre']] = [
                'id' => $row['id_ente'],
                'escuela' => $row['escuela']
            ];
        }

        // Comenzar transacción
        $pdo->beginTransaction();

        for ($row = 2; $row <= $highestRow; $row++) {
            $centro = $worksheet->getCellByColumnAndRow(1, $row)->getValue();
            $codigo = $worksheet->getCellByColumnAndRow(2, $row)->getValue();
            $actividad = $worksheet->getCellByColumnAndRow(3, $row)->getValue();
            $tipo = strtoupper($worksheet->getCellByColumnAndRow(4, $row)->getValue());
            $escuela = $worksheet->getCellByColumnAndRow(5, $row)->getValue();
            $t1 = $worksheet->getCellByColumnAndRow(6, $row)->getValue() ?? 0;
            $t2 = $worksheet->getCellByColumnAndRow(7, $row)->getValue() ?? 0;
            $t3 = $worksheet->getCellByColumnAndRow(8, $row)->getValue() ?? 0;
            $t4 = $worksheet->getCellByColumnAndRow(9, $row)->getValue() ?? 0;
            $monto = $worksheet->getCellByColumnAndRow(10, $row)->getValue();

            // Validaciones
            if (empty($centro) || empty($codigo) || empty($actividad)) {
                $errores[] = "Fila $row: Campos obligatorios vacíos";
                $stats['errors']++;
                continue;
            }

            if (!isset($entes[$centro])) {
                $errores[] = "Fila $row: Centro/Grupo '$centro' no encontrado en la base de datos";
                $stats['errors']++;
                continue;
            }

            if (!in_array($tipo, TIPOS_VALIDOS)) {
                $errores[] = "Fila $row: Tipo '$tipo' no válido";
                $stats['errors']++;
                continue;
            }

            if (!in_array($escuela, ESCUELAS_VALIDAS)) {
                $errores[] = "Fila $row: Escuela '$escuela' no válida";
                $stats['errors']++;
                continue;
            }

            if (!validarPorcentaje($t1) || !validarPorcentaje($t2) || 
                !validarPorcentaje($t3) || !validarPorcentaje($t4)) {
                $errores[] = "Fila $row: Porcentajes incorrectos (deben estar entre 0 y 100)";
                $stats['errors']++;
                continue;
            }

            if (!is_numeric($monto) || $monto < 0) {
                $errores[] = "Fila $row: Monto inválido";
                $stats['errors']++;
                continue;
            }

            // Verificar si la actividad existe
            $stmt = $pdo->prepare("SELECT codigo_actividad FROM actividades_poi WHERE codigo_actividad = ?");
            $stmt->execute([$codigo]);
            $existe = $stmt->fetch();

            if ($existe) {
                // Actualizar actividad existente
                $stmt = $pdo->prepare("
                    UPDATE actividades_poi 
                    SET nombre_actividad = ?, categoria = ?, 
                        porcentaje_1t = ?, porcentaje_2t = ?, 
                        porcentaje_3t = ?, porcentaje_4t = ?,
                        monto_financiamiento = ?
                    WHERE codigo_actividad = ?
                ");
                $stmt->execute([
                    $actividad, $tipo,
                    $t1, $t2, $t3, $t4,
                    $monto, $codigo
                ]);
                $stats['updated']++;
            } else {
                // Insertar nueva actividad
                $stmt = $pdo->prepare("
                    INSERT INTO actividades_poi (
                        codigo_actividad, id_ente, nombre_actividad, 
                        categoria, porcentaje_1t, porcentaje_2t, 
                        porcentaje_3t, porcentaje_4t, monto_financiamiento
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $codigo, $entes[$centro]['id'], $actividad,
                    $tipo, $t1, $t2, $t3, $t4, $monto
                ]);
                $stats['imported']++;
            }
        }

        $pdo->commit();
        return [
            'success' => true,
            'message' => 'Importación completada',
            'stats' => $stats,
            'errors' => $errores
        ];

    } catch (Exception $e) {
        $pdo->rollBack();
        return [
            'success' => false,
            'error' => 'Error en la importación: ' . $e->getMessage(),
            'errors' => $errores
        ];
    }
}

// Procesar la solicitud
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $resultado = procesarExcel($_FILES['file']);
    echo json_encode($resultado);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'No se recibió ningún archivo'
    ]);
}
