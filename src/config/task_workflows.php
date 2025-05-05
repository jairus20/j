<?php
return [
    'CAPACITACION_EJECUCION' => [
        'name' => 'Ejecución de Capacitación/Taller/Foro/Conversatorio',
        'initial_state' => 'INICIO',
        'states' => [
            'INICIO' => [
                'description' => 'Inicio del proceso',
                'next' => 'EVAL_CGYC',
                'required_docs' => ['Solicitud', 'Formato 14', 'Datos del expositor']
            ],
            'EVAL_CGYC' => [
                'description' => 'Evaluación de Solicitud (CGyC)',
                'next' => 'EVAL_UIFIA',
                'responsible' => 'CGyC'
            ],
            'EVAL_UIFIA' => [
                'description' => 'Evaluación UI-FIA',
                'next' => null,
                'conditions' => [
                    'OBSERVADO' => [
                        'next' => 'INICIO',
                        'message' => 'Inscripción no procede',
                        'retry_to' => 'INICIO'
                    ],
                    'APROBADO' => [
                        'next' => 'EVAL_DDA',
                        'message' => 'Procede evaluación DDA'
                    ]
                ],
                'responsible' => 'UI-FIA'
            ],
            'EVAL_DDA' => [
                'description' => 'Evaluación DDA',
                'next' => 'EVAL_DIPLA',
                'required_docs' => ['Formato 14 firmado'],
                'responsible' => 'DDA'
            ],
            'EVAL_DIPLA' => [
                'description' => 'Evaluación DIPLA (Fondos POI)',
                'next' => 'EVAL_DDA_RESPUESTA',
                'conditions' => [
                    'APROBADO' => [
                        'next' => 'EVAL_DDA_RESPUESTA',
                        'message' => 'Devuelto proveído conforme'
                    ],
                    'RECHAZADO' => [
                        'next' => 'EVAL_DDA_RESPUESTA',
                        'message' => 'Devuelto proveído inconforme'
                    ]
                ],
                'responsible' => 'DIPLA'
            ],
            'EVAL_DDA_RESPUESTA' => [
                'description' => 'Evaluación Final DDA',
                'next' => null,
                'conditions' => [
                    'CONFORME' => [
                        'next' => 'APROBADO',
                        'message' => 'Tarea aprobada'
                    ],
                    'INCONFORME' => [
                        'next' => 'EVAL_UIFIA',
                        'message' => 'Devuelto a UI-FIA para revisión'
                    ]
                ],
                'responsible' => 'DDA',
                'is_final' => true
            ],
            'APROBADO' => [
                'description' => 'Proceso Aprobado',
                'is_final' => true
            ],
            'RECHAZADO' => [
                'description' => 'Proceso Rechazado',
                'is_final' => true
            ]
        ],
        'events' => [
            'EVENTO_1' => 'Reintento desde UI-FIA',
            'EVENTO_2' => 'Evaluación en UI-FIA',
            'EVENTO_3' => 'Evaluación en DDA',
            'EVENTO_4' => 'Evaluación en DIPLA',
            'EVENTO_5' => 'Respuesta Final DDA'
        ]
    ],
    'CAPACITACION' => [
        'name' => 'Ejecución de Capacitación/Taller/Foro/Conversatorio',
        'initial_state' => 'INICIO',
        'states' => [
            'INICIO' => [
                'description' => 'Inicio del proceso',
                'next' => 'EVAL_CGYC',
                'required_docs' => ['Solicitud', 'Formato 14', 'Datos del expositor']
            ],
            'EVAL_CGYC' => [
                'description' => 'Evaluación de Solicitud (CGyC)',
                'next' => 'EVAL_UIFIA',
                'responsible' => 'CGyC'
            ],
            'EVAL_UIFIA' => [
                'description' => 'Evaluación UI-FIA',
                'next' => null,
                'conditions' => [
                    'OBSERVADO' => [
                        'next' => 'INICIO',
                        'message' => 'Inscripción no procede',
                        'retry_to' => 'INICIO'
                    ],
                    'APROBADO' => [
                        'next' => 'EVAL_DDA',
                        'message' => 'Procede evaluación DDA'
                    ]
                ],
                'responsible' => 'UI-FIA'
            ],
            'EVAL_DDA' => [
                'description' => 'Evaluación DDA',
                'next' => 'EVAL_DIPLA',
                'required_docs' => ['Formato 14 firmado'],
                'responsible' => 'DDA'
            ],
            'EVAL_DIPLA' => [
                'description' => 'Evaluación DIPLA (Fondos POI)',
                'next' => 'EVAL_DDA_RESPUESTA',
                'conditions' => [
                    'APROBADO' => [
                        'next' => 'EVAL_DDA_RESPUESTA',
                        'message' => 'Devuelto proveído conforme'
                    ],
                    'RECHAZADO' => [
                        'next' => 'EVAL_DDA_RESPUESTA',
                        'message' => 'Devuelto proveído inconforme'
                    ]
                ],
                'responsible' => 'DIPLA'
            ],
            'EVAL_DDA_RESPUESTA' => [
                'description' => 'Evaluación Final DDA',
                'next' => null,
                'conditions' => [
                    'CONFORME' => [
                        'next' => 'APROBADO',
                        'message' => 'Tarea aprobada'
                    ],
                    'INCONFORME' => [
                        'next' => 'EVAL_UIFIA',
                        'message' => 'Devuelto a UI-FIA para revisión'
                    ]
                ],
                'responsible' => 'DDA',
                'is_final' => true
            ],
            'APROBADO' => [
                'description' => 'Proceso Aprobado',
                'is_final' => true
            ],
            'RECHAZADO' => [
                'description' => 'Proceso Rechazado',
                'is_final' => true
            ]
        ],
        'events' => [
            'EVENTO_1' => 'Reintento desde UI-FIA',
            'EVENTO_2' => 'Evaluación en UI-FIA',
            'EVENTO_3' => 'Evaluación en DDA',
            'EVENTO_4' => 'Evaluación en DIPLA',
            'EVENTO_5' => 'Respuesta Final DDA'
        ]
    ]
];
