<?php
require_once '../vendor/autoload.php';
require_once '../includes/auth_helper.php';

use App\SupabaseClient;
use App\Configuracion;
use App\Paciente;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

requirePermission('ver_historia'); // Verificar permiso para ver historias (imprimir solicitud)

$id_historia = $_GET['id_historia'] ?? null;
if (!$id_historia) die("ID válido requerido");

$supabase = new SupabaseClient($_ENV['SUPABASE_URL'], $_ENV['SUPABASE_KEY']);

// Obtener configuración
$configModel = new Configuracion($supabase);
$config = $configModel->obtenerConfiguracion();

// Obtener historia
$historia = $supabase->select('historias_clinicas', '*', "id_historia=eq.$id_historia");
if (empty($historia)) die("Historia no encontrada");
$historia = $historia[0];

// Obtener solicitudes
$id_consulta = $historia['id_consulta'] ?? null;
if ($id_consulta) {
    $ordenes = $supabase->select('solicitudes', '*', "id_consulta=eq.$id_consulta");
} else {
    $ordenes = [];
}

// Obtener paciente
$pacienteModel = new Paciente($supabase);
$paciente = $pacienteModel->obtenerPorId($historia['id_paciente']);
if (!$paciente) die("Paciente no encontrado");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Solicitud de Servicios</title>
    <style>
        body { font-family: 'Arial', sans-serif; padding: 40px; }
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .company { font-size: 20px; font-weight: bold; color: <?php echo $config['color_principal'] ?? '#333'; ?>; }
        .title { text-align: center; font-size: 18px; margin: 20px 0; text-transform: uppercase; }
        .info { margin-bottom: 20px; }
        .list-item { border-bottom: 1px solid #eee; padding: 10px 0; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="no-print"><button onclick="window.print()">Imprimir</button></div>
    
    <div class="header">
        <div class="company"><?php echo htmlspecialchars($config['nombre_institucion'] ?? 'Institución de Salud'); ?></div>
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
                    <strong><?= htmlspecialchars($ord['descripcion'] ?? 'Procedimiento #' . ($ord['proced_id'] ?? $ord['id'])) ?></strong>
                    <?php if (!empty($ord['codigo'])): ?>
                        <small>(Código: <?= htmlspecialchars($ord['codigo']) ?>)</small>
                    <?php endif; ?>
                    <br>
                    <small>Cantidad: <?= htmlspecialchars($ord['cantidad'] ?? '1') ?></small>
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
