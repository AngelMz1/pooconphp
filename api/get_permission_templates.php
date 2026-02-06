<?php
/**
 * API: Get Permission Templates
 * Returns pre-defined permission sets for quick user setup
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/auth_helper.php';

requirePermission('gestionar_usuarios');

header('Content-Type: application/json');

// Define permission templates
$templates = [
    'facturador' => [
        'name' => 'Facturador',
        'description' => 'Permisos típicos para personal de facturación',
        'permissions' => [
            'ver_pacientes',
            'gestionar_pacientes',
            'ver_todas_citas',
            'agendar_citas',
            'confirmar_citas',
            'cancelar_citas',
            'reagendar_citas',
            'ver_facturas',
            'generar_factura',
            'registrar_pago'
        ]
    ],
    'medico' => [
        'name' => 'Médico',
        'description' => 'Permisos típicos para médicos',
        'permissions' => [
            'ver_pacientes',
            'ver_todas_citas',
            'atender_consulta',
            'crear_historia',
            'ver_historia',
            'prescribir_medicamentos',
            'solicitar_procedimientos',
            'generar_factura',
            'ver_facturas',
            'registrar_pago'
        ]
    ],
    'admin' => [
        'name' => 'Administrador Completo',
        'description' => 'Todos los permisos del sistema',
        'permissions' => [
            'gestionar_pacientes',
            'ver_pacientes',
            'agendar_citas',
            'confirmar_citas',
            'cancelar_citas',
            'reagendar_citas',
            'ver_todas_citas',
            'atender_consulta',
            'crear_historia',
            'ver_historia',
            'prescribir_medicamentos',
            'solicitar_procedimientos',
            'generar_factura',
            'registrar_pago',
            'ver_facturas',
            'anular_factura',
            'administrar_tarifarios',
            'gestionar_usuarios',
            'gestionar_medicos',
            'configurar_sistema',
            'ver_reportes'
        ]
    ],
    'recepcionista' => [
        'name' => 'Recepcionista',
        'description' => 'Permisos para recepción',
        'permissions' => [
            'ver_pacientes',
            'gestionar_pacientes',
            'agendar_citas',
            'confirmar_citas',
            'cancelar_citas',
            'reagendar_citas'
        ]
    ]
];

echo json_encode([
    'success' => true,
    'templates' => $templates
]);
