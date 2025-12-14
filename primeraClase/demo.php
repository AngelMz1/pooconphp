<?php
// Demo del sistema con datos simulados
require_once '../vendor/autoload.php';

use App\SupabaseClient;
use Dotenv\Dotenv;

// Cargar variables de entorno
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Función para calcular edad
function calcularEdad($fecha_nacimiento) {
    if (!$fecha_nacimiento) return 'N/A';
    
    try {
        $fecha_nac = new DateTime($fecha_nacimiento);
        $hoy = new DateTime();
        $edad = $hoy->diff($fecha_nac)->y;
        return $edad . ' años';
    } catch (Exception $e) {
        return 'N/A';
    }
}

// Datos simulados para demostración
$pacientes_demo = [
    '1000000246' => [
        'id_paciente' => 1,
        'documento_id' => '1000000246',
        'primer_nombre' => 'María',
        'segundo_nombre' => 'Elena',
        'primer_apellido' => 'González',
        'segundo_apellido' => 'López',
        'fecha_nacimiento' => '1990-05-15',
        'direccion' => 'Carrera 15 #32-45',
        'telefono' => '3201234567',
        'eps_id' => 1,
        'regimen_id' => 1
    ],
    '12345678' => [
        'id_paciente' => 2,
        'documento_id' => '12345678',
        'primer_nombre' => 'Juan',
        'segundo_nombre' => 'Carlos',
        'primer_apellido' => 'Pérez',
        'segundo_apellido' => 'García',
        'fecha_nacimiento' => '1985-03-20',
        'direccion' => 'Calle 123 #45-67',
        'telefono' => '3001234567',
        'eps_id' => 2,
        'regimen_id' => 1
    ]
];

$historias_demo = [
    1 => [
        [
            'id_historia' => 1,
            'id_paciente' => 1,
            'fecha_ingreso' => '2024-01-15 10:30:00',
            'fecha_egreso' => '2024-01-15',
            'analisis_plan' => 'Consulta por control rutinario. Paciente presenta buen estado general. Se recomienda seguimiento anual.'
        ],
        [
            'id_historia' => 2,
            'id_paciente' => 1,
            'fecha_ingreso' => '2024-01-20 14:15:00',
            'fecha_egreso' => null,
            'analisis_plan' => 'Consulta por síntomas gripales. Tratamiento sintomático con acetaminofén. Control en 3 días.'
        ]
    ],
    2 => [
        [
            'id_historia' => 3,
            'id_paciente' => 2,
            'fecha_ingreso' => '2024-01-10 09:00:00',
            'fecha_egreso' => '2024-01-10',
            'analisis_plan' => 'Chequeo médico preventivo. Exámenes de laboratorio normales. Continuar con hábitos saludables.'
        ]
    ]
];

// --- LÓGICA DE BÚSQUEDA ---
$paciente_encontrado = null;
$historial_clinico = [];
$doc_id_buscado = $_GET['doc_id'] ?? null;
$error_conexion = null;

if ($doc_id_buscado) {
    if (isset($pacientes_demo[$doc_id_buscado])) {
        $paciente_encontrado = $pacientes_demo[$doc_id_buscado];
        $id_paciente = $paciente_encontrado['id_paciente'];
        
        if (isset($historias_demo[$id_paciente])) {
            $historial_clinico = $historias_demo[$id_paciente];
        }
    }
}

// --- LÓGICA DE CREACIÓN ---
$mensaje_creacion = "";
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'crear_historia') {
    $mensaje_creacion = "✅ Historia clínica creada exitosamente (DEMO). En producción se insertaría en la base de datos.";
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Historia Clínica - DEMO</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        
        <div class="encabezado">
            <h2>🏥 Sistema de Historia Clínica - DEMO</h2>
            <p style="background: rgba(255,255,255,0.2); padding: 10px; border-radius: 5px; margin-top: 10px;">
                📋 <strong>Pacientes de prueba:</strong> 1000000246 (María González) | 12345678 (Juan Pérez)
            </p>
            <form action="demo.php" method="GET" class="search-form">
                <input type="text" name="doc_id" placeholder="Ingrese Documento ID del Paciente" required 
                       value="<?php echo htmlspecialchars($doc_id_buscado ?? ''); ?>">
                <button type="submit">🔍 Buscar Historia</button>
            </form>
        </div>
        
        <div class="content-area">

            <?php if ($error_conexion): ?>
                <div class="error">❌ <?php echo $error_conexion; ?></div>
            <?php elseif ($doc_id_buscado && !$paciente_encontrado): ?>
                <div class="error">❌ No se encontró ningún paciente con Documento ID: <strong><?php echo htmlspecialchars($doc_id_buscado); ?></strong></div>
            <?php endif; ?>

            <?php if ($paciente_encontrado): ?>
                <div class="patient-info">
                    <h1>📋 Historia Clínica de <?php echo htmlspecialchars($paciente_encontrado['primer_nombre'] . ' ' . $paciente_encontrado['primer_apellido']); ?></h1>
                </div>
                
                <div class="resultados">
                
                    <h2>👤 Información del Paciente</h2>
                    <div class="doblecolumna">
                        <div class="columna">
                            <p>👤 Nombre Completo: <span><?php echo htmlspecialchars($paciente_encontrado['primer_nombre'] . ' ' . ($paciente_encontrado['segundo_nombre'] ?? '') . ' ' . $paciente_encontrado['primer_apellido'] . ' ' . ($paciente_encontrado['segundo_apellido'] ?? '')); ?></span></p>
                            <p>🎂 Fecha Nacimiento: <span><?php echo htmlspecialchars($paciente_encontrado['fecha_nacimiento'] ?? 'N/A'); ?></span></p>
                            <p>📅 Edad: <span><?php echo calcularEdad($paciente_encontrado['fecha_nacimiento']); ?></span></p>
                            <p>📞 Teléfono: <span><?php echo htmlspecialchars($paciente_encontrado['telefono'] ?? 'N/A'); ?></span></p>
                        </div>
                        <div class="columna">
                            <p>🆔 Documento: <span><?php echo htmlspecialchars($paciente_encontrado['documento_id']); ?></span></p>
                            <p>📍 Dirección: <span><?php echo htmlspecialchars($paciente_encontrado['direccion'] ?? 'N/A'); ?></span></p>
                            <p>🏥 EPS ID: <span><?php echo htmlspecialchars($paciente_encontrado['eps_id'] ?? 'N/A'); ?></span></p>
                            <p>⚕️ Régimen ID: <span><?php echo htmlspecialchars($paciente_encontrado['regimen_id'] ?? 'N/A'); ?></span></p>
                        </div>
                    </div>

                    <h2>📊 Historial de Ingresos Clínicos</h2>
                <?php if (!empty($historial_clinico)): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID Historia</th>
                                <th>Fecha Ingreso</th>
                                <th>Fecha Egreso</th>
                                <th>Estado</th>
                                <th>Análisis/Plan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($historial_clinico as $historia): ?>
                                <?php 
                                    $estado = $historia['fecha_egreso'] ? 'Cerrada' : 'Abierta';
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($historia['id_historia']); ?></td>
                                    <td><?php echo htmlspecialchars($historia['fecha_ingreso']); ?></td>
                                    <td><?php echo htmlspecialchars($historia['fecha_egreso'] ?? 'N/A'); ?></td>
                                    <td><strong style="color: <?php echo $estado === 'Abierta' ? 'green' : 'blue'; ?>"><?php echo $estado; ?></strong></td>
                                    <td><?php echo htmlspecialchars(substr($historia['analisis_plan'] ?? '', 0, 80) . '...'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>Este paciente no tiene historias clínicas registradas.</p>
                <?php endif; ?>

                </div>
            <?php endif; ?>

            <div class="form-section">
                <h2>➕ Crear Nueva Historia Clínica / Consulta</h2>
                <?php if ($mensaje_creacion): ?>
                    <div class="success"><?php echo $mensaje_creacion; ?></div>
                <?php endif; ?>
                
                <form action="demo.php" method="POST">
                    <input type="hidden" name="action" value="crear_historia">
                    
                    <div class="doblecolumna">
                        <div class="form-group">
                            <label for="p_documento">🆔 Documento ID del Paciente (*):</label>
                            <input type="text" id="p_documento" name="p_documento" placeholder="Ej: 1000000246" required>
                        </div>
                        <div class="form-group">
                            <label for="m_id">👨⚕️ ID del Médico (*):</label>
                            <input type="number" id="m_id" name="m_id" placeholder="Ej: 1" required>
                        </div>
                    </div>
                    
                    <h3>📝 Datos de la Consulta y Diagnóstico</h3>
                    
                    <div class="form-group">
                        <label for="motivo">🔍 Motivo de Consulta (*):</label>
                        <textarea id="motivo" name="motivo" placeholder="Describa el motivo principal de la consulta..." required></textarea>
                    </div>

                    <div class="form-group">
                        <label for="enfermedad_actual">🩺 Enfermedad Actual (*):</label>
                        <textarea id="enfermedad_actual" name="enfermedad_actual" placeholder="Describa la enfermedad actual del paciente..." required></textarea>
                    </div>

                    <div class="form-group">
                        <label for="dx_codigo">📋 Código CIE-10 Principal (*):</label>
                        <input type="text" id="dx_codigo" name="dx_codigo" placeholder="Ej: J069 - Infección aguda de las vías respiratorias superiores" required>
                    </div>
                    
                    <button type="submit" class="btn-primary">💾 Guardar Historia y Consulta</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>