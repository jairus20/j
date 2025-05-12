<?php
return [
    'CAPACITACION_SERVICIOS' => [
        'name' => 'Requerimiento de Servicios por Terceros',
        'steps' => [
            'CGyC' => [
                'descripcion' => 'Revisión inicial de la solicitud y documentos requeridos.',
                'documentos' => ['TDR', 'Plan de trabajo', 'Formato de objetivos', 'Propuesta económica', 'RUC/CCI/Firma', 'CV'],
                'estados_posibles' => ['ENVIADO_UIFIA', 'OBSERVADO']
            ],
            'UIFIA' => [
                'descripcion' => 'Revisión formal y técnica de la solicitud.',
                'documentos' => ['Solicitud de aprobación', 'Requerimiento en ERP'],
                'estados_posibles' => ['ENVIADO_DDA', 'OBSERVADO', 'NO_PROCEDE']
            ],
            'DDA' => [
                'descripcion' => 'Evaluación de la solicitud y requisitos.',
                'documentos' => ['Formato 14'],
                'estados_posibles' => ['ENVIADO_ABASTECIMIENTOS', 'OBSERVADO']
            ],
            'ABASTECIMIENTOS' => [
                'descripcion' => 'Verificación final con fondos POI.',
                'documentos' => ['Formato 14'],
                'estados_posibles' => ['CERRADO', 'OBSERVADO']
            ]
        ],
        'initial_state' => 'CGyC'
    ],
    'CAPACITACION_CERTIFICACION' => [
        'name' => 'Certificación de Capacitación/Taller/Foro/Conversatorio',
        'steps' => [
            'CGyC' => [
                'descripcion' => 'Revisión inicial de la solicitud de certificación.',
                'documentos' => ['Oficio DDA', 'Informe de realización', 'Lista de asistentes', 'Encuesta de satisfacción'],
                'estados_posibles' => ['ENVIADO_UIFIA', 'OBSERVADO']
            ],
            'UIFIA' => [
                'descripcion' => 'Verificación formal de la solicitud.',
                'documentos' => ['Solicitud de certificación'],
                'estados_posibles' => ['ENVIADO_DDA', 'OBSERVADO']
            ],
            'DDA' => [
                'descripcion' => 'Evaluación final y emisión del certificado.',
                'documentos' => ['Certificado emitido'],
                'estados_posibles' => ['CERRADO', 'OBSERVADO']
            ]
        ],
        'initial_state' => 'CGyC'
    ],
    'CAPACITACION_EJECUCION' => [
        'name' => 'Capacitación/Taller/Foro/Conversatorio (Ejecución)',
        'steps' => [
            'CGyC' => [
                'descripcion' => 'Recepción y revisión de la solicitud de ejecución.',
                'documentos' => ['Formato 14', 'Datos del expositor'],
                'estados_posibles' => ['ENVIADO_UIFIA', 'OBSERVADO']
            ],
            'UIFIA' => [
                'descripcion' => 'Revisión formal y técnica de la solicitud.',
                'documentos' => ['Solicitud de ejecución'],
                'estados_posibles' => ['ENVIADO_DDA', 'OBSERVADO', 'NO_PROCEDE']
            ],
            'DDA' => [
                'descripcion' => 'Evaluación de la solicitud y remisión a DIPLA.',
                'documentos' => ['Formato 14'],
                'estados_posibles' => ['ENVIADO_DIPLA', 'OBSERVADO']
            ],
            'DIPLA' => [
                'descripcion' => 'Revisión de fondos POI asignados.',
                'documentos' => ['Formato 14'],
                'estados_posibles' => ['PROVEIDO_CONFORME', 'PROVEIDO_INCONFORME']
            ],
            'DDA_FINAL' => [
                'descripcion' => 'Análisis del proveído y emisión de aprobación final.',
                'documentos' => ['Aprobación final'],
                'estados_posibles' => ['CERRADO', 'OBSERVADO']
            ]
        ],
        'initial_state' => 'CGyC'
    ]
];
?>
