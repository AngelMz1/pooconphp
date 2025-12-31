<?php
require_once '../vendor/autoload.php';
require_once '../includes/auth_helper.php';

use App\SupabaseClient;
use App\Configuracion;
use App\Medico;
use App\Paciente;
use App\ReferenceData;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

requireLogin();

$id_historia = $_GET['id_historia'] ?? null;
if (!$id_historia) die("ID de historia no válido");

$supabase = new SupabaseClient($_ENV['SUPABASE_URL'], $_ENV['SUPABASE_KEY']);

// Obtener configuración
$configModel = new Configuracion($supabase);
$config = $configModel->obtenerConfiguracion();

// Obtener fórmulas
$formulas = $supabase->select('formulas_medicas', '*', "id_historia=eq.$id_historia");

// Obtener medicamentos
$refData = new ReferenceData($supabase);
$lista_meds = $refData->getMedicamentos();
$med_map = [];
foreach ($lista_meds as $m) {
    $med_map[$m['id']] = $m['nombre'] ?? $m['descripcion'] ?? 'Med #' . $m['id'];
}

// Obtener historia
$historia = $supabase->select('historias_clinicas', '*', "id_historia=eq.$id_historia");
if (empty($historia)) die("Historia no encontrada");
$historia = $historia[0];

// Obtener paciente
$pacienteModel = new Paciente($supabase);
$paciente = $pacienteModel->obtenerPorId($historia['id_paciente']);
if (!$paciente) die("Paciente no encontrado");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Fórmula Médica</title>
    <style>
        body { font-family: 'Arial', sans-serif; padding: 40px; }
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; }
        .company { font-size: 20px; font-weight: bold; color: <?php echo $config['color_principal'] ?? '#333'; ?>; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .box { border: 1px solid #ccc; padding: 10px; border-radius: 5px; }
        .rx-section { margin-top: 20px; }
        .rx-item { border-bottom: 1px dotted #ccc; padding: 10px 0; }
        .footer { margin-top: 50px; text-align: center; border-top: 1px solid #ccc; padding-top: 10px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()">Imprimir</button>
    </div>

    <div class="header">
        <div class="company"><?php echo htmlspecialchars($config['nombre_institucion'] ?? 'Institución de Salud'); ?></div>
        <small>Fórmula Médica</small>
    </div>

    <div class="info-grid">
        <div class="box">
            <strong>Paciente:</strong> <?php echo htmlspecialchars($paciente['primer_nombre'] . ' ' . $paciente['primer_apellido']); ?><br>
            <strong>ID:</strong> <?php echo htmlspecialchars($paciente['documento_id']); ?>
        </div>
        <div class="box">
            <strong>Fecha:</strong> <?php echo date('d/m/Y', strtotime($historia['fecha_ingreso'])); ?><br>
            <strong>Consulta #</strong> <?php echo $id_historia; ?>
        </div>
    </div>

    <div class="rx-section">
        <h3>Rx / Prescripción</h3>
        <?php if (!empty($formulas)): ?>
            <?php foreach ($formulas as $f): ?>
            <div class="rx-item">
                <!-- Use mapped name or fallback -->
                <strong><?php echo htmlspecialchars($med_map[$f['medicamento_id']] ?? 'Medicamento ' . $f['medicamento_id']); ?></strong><br>
                <?php echo htmlspecialchars($f['dosis'] ?? ''); ?>
                <!-- 'dosis' in DB contains concatenated string of details per FormulaMedica class logic -->
                <br>
                <small>Cantidad: <?php echo htmlspecialchars($f['cantidad'] ?? 0); ?></small>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No hay medicamentos registrados en esta fórmula.</p>
        <?php endif; ?>
    </div>

    <div class="footer">
        __________________________<br>
        Firma del Médico<br>
        <small>Registro Médico</small>
    </div>
</body>
</html>
