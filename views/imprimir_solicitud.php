require_once '../vendor/autoload.php';
require_once '../src/SupabaseClient.php';
require_once '../src/Configuracion.php';
require_once '../src/Paciente.php';
require_once '../includes/auth_helper.php';

use App\SupabaseClient;
use App\Configuracion;
use App\Paciente;

$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

session_start();
requireLogin();

$id_historia = $_GET['id_historia'] ?? null;
if (!$id_historia) die("ID válido requerido");

$supabase = new SupabaseClient($_ENV['SUPABASE_URL'], $_ENV['SUPABASE_KEY']);
$configModel = new Configuracion();
$config = $configModel->obtenerConfiguracion();

// Assuming table 'procedimientos_consulta' or similar linking exams/orders
// Let's check `src/Procedimiento.php` or assumption of 'procedimientos' table.
// Usually 'procedimientos'
// 1. Get History first to get id_consulta
$historia = $supabase->select('historias_clinicas', '*', "id_historia=eq.$id_historia");
if (empty($historia)) die("Historia no encontrada");
$historia = $historia[0];

// 2. Get Solicitudes using id_consulta
$id_consulta = $historia['id_consulta'];
if ($id_consulta) {
    // Try to fetch with join if FK exists: solicitudes(*, procedimientos(*))
    // If not, fetch raw and loop. Assuming basic fetch first.
    // 'procedimientos' table matches 'proced_id' in 'solicitudes'
    $ordenes = $supabase->select('solicitudes', '*, procedimientos(nombre_procedimiento, codigo_cups)', "id_consulta=eq.$id_consulta");
} else {
    $ordenes = [];
}

$pacienteModel = new Paciente();
$paciente = $pacienteModel->obtenerPaciente($historia['id_paciente']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Solicitud de Servicios</title>
    <style>
        body { font-family: 'Arial', sans-serif; padding: 40px; }
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .company { font-size: 20px; font-weight: bold; color: <?php echo $config['color_principal']; ?>; }
        .title { text-align: center; font-size: 18px; margin: 20px 0; text-transform: uppercase; }
        .info { margin-bottom: 20px; }
        .list-item { border-bottom: 1px solid #eee; padding: 10px 0; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="no-print"><button onclick="window.print()">Imprimir</button></div>
    
    <div class="header">
        <div class="company"><?php echo htmlspecialchars($config['nombre_institucion']); ?></div>
        <small>Orden de Procedimientos / Exámenes</small>
    </div>

    <div class="info">
        <p><strong>Paciente:</strong> <?php echo htmlspecialchars($paciente['primer_nombre'] . ' ' . $paciente['primer_apellido']); ?></p>
        <p><strong>Documento:</strong> <?php echo htmlspecialchars($paciente['documento_id']); ?></p>
        <p><strong>Fecha:</strong> <?php echo date('d/m/Y', strtotime($historia['fecha_ingreso'])); ?></p>
    </div>

    <div class="content">
        <?php if (!empty($ordenes)): ?>
            <?php foreach ($ordenes as $ord): ?>
                <div class="list-item">
                <div class="list-item">
                    <?php 
                        $nom = $ord['procedimientos']['nombre_procedimiento'] ?? 'Procedimiento #' . $ord['proced_id'];
                        $cod = $ord['procedimientos']['codigo_cups'] ?? '';
                    ?>
                    <strong><?= htmlspecialchars($nom) ?></strong> 
                    <?php if($cod): ?> <small>(CUPS: <?= htmlspecialchars($cod) ?>)</small> <?php endif; ?>
                    <br>
                    <small>Cantidad: <?= htmlspecialchars($ord['cantidad']) ?></small>
                </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No hay procedimientos ordenados.</p>
        <?php endif; ?>
    </div>

    <div style="margin-top: 50px; text-align: center;">
        __________________________<br>Firma Profesional
    </div>
</body>
</html>
