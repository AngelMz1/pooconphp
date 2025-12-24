<?php
require_once '../vendor/autoload.php';

use App\SupabaseClient;
use App\HistoriaClinica;
use App\Paciente;
use App\SignosVitales;
use App\ExamenFisico;
use App\RevisionSistemas;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$supabase = new SupabaseClient($_ENV['SUPABASE_URL'], $_ENV['SUPABASE_KEY']);
$historiaModel = new HistoriaClinica($supabase);
$pacienteModel = new Paciente($supabase);
$signosModel = new SignosVitales($supabase);
$examenModel = new ExamenFisico($supabase);
$revisionModel = new RevisionSistemas($supabase);

$error = '';
$historia = null;
$paciente = null;

if (!isset($_GET['id'])) {
    header('Location: listar_historias.php');
    exit;
}

$id = $_GET['id'];

// Procesar cierre de historia
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cerrar') {
    try {
        $historiaModel->cerrar($id);
        header("Location: ver_historia.php?id=$id&msg=closed");
        exit;
    } catch (Exception $e) {
        $error = "Error al cerrar historia: " . $e->getMessage();
    }
}

try {
    $historia = $historiaModel->obtenerPorId($id);
    if (!$historia) {
        throw new Exception("Historia clínica no encontrada");
    }
    
    // Obtener información del paciente
    $paciente = $pacienteModel->obtenerPorId($historia['id_paciente']);
    
    // Obtener datos del examen físico completo (si existen)
    $signos = $signosModel->obtenerPorHistoria($id);
    $examen = $examenModel->obtenerPorHistoria($id);
    $revision = $revisionModel->obtenerPorHistoria($id);
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historia Clínica #<?= $id ?> - Sistema de Gestión Médica</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <div class="container">
        <?php if ($error): ?>
            <div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div>
            <div class="text-center">
                <a href="listar_historias.php" class="btn btn-primary">← Volver a historias</a>
            </div>
        <?php elseif ($historia): ?>
            <!-- Header -->
            <div class="card card-gradient text-center mb-4">
                <h1>📋 Historia Clínica #<?= htmlspecialchars($historia['id_historia']) ?></h1>
                <?php if ($paciente): ?>
                    <p style="margin-bottom: 0;">
                        Paciente: <?= htmlspecialchars($paciente['primer_nombre']) ?> 
                        <?= htmlspecialchars($paciente['primer_apellido']) ?> 
                        (<?= htmlspecialchars($paciente['documento_id']) ?>)
                    </p>
                <?php endif; ?>
            </div>

            <!-- Información del Paciente -->
            <?php if ($paciente): ?>
                <div class="card mb-4">
                    <h3>👤 Información del Paciente</h3>
                    <div class="grid grid-2">
                        <div>
                            <p><strong>Nombre Completo:</strong></p>
                            <p style="color: var(--gray-700);">
                                <?= htmlspecialchars($paciente['primer_nombre']) ?> 
                                <?= htmlspecialchars($paciente['segundo_nombre'] ?? '') ?>
                                <?= htmlspecialchars($paciente['primer_apellido']) ?> 
                                <?= htmlspecialchars($paciente['segundo_apellido'] ?? '') ?>
                            </p>
                        </div>
                        <div>
                            <p><strong>Documento ID:</strong></p>
                            <p style="color: var(--gray-700);"><?= htmlspecialchars($paciente['documento_id']) ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Fechas y Estado -->
            <div class="grid grid-2 mb-4">
                <div class="card">
                    <h3>📅 Fecha de Ingreso</h3>
                    <p style="font-size: 1.25rem; color: var(--primary); margin: 0;">
                        <?php
                        $fechaIngreso = new DateTime($historia['fecha_ingreso']);
                        echo $fechaIngreso->format('d/m/Y H:i');
                        ?>
                    </p>
                </div>

                <div class="card">
                    <h3>🏥 Estado</h3>
                    <?php if ($historia['fecha_egreso']): ?>
                        <span class="badge badge-success">✓ Cerrada</span>
                    <?php else: ?>
                        <span class="badge badge-primary">● Activa</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Signos Vitales -->
            <?php if (!empty($signos)): ?>
                <div class="card mb-4">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <h3>💓 Signos Vitales</h3>
                        <span class="badge badge-success">Registrados</span>
                    </div>
                    <div class="grid grid-3" style="text-align: center;">
                        <div>
                            <strong style="color: var(--gray-600);">Tensión Arterial</strong>
                            <p style="font-size: 1.2rem;"><?= htmlspecialchars($signos['ta']) ?></p>
                        </div>
                        <div>
                            <strong style="color: var(--gray-600);">Frecuencia Cardíaca</strong>
                            <p style="font-size: 1.2rem;"><?= htmlspecialchars($signos['pulso']) ?> lpm</p>
                        </div>
                        <div>
                            <strong style="color: var(--gray-600);">Frecuencia Respiratoria</strong>
                            <p style="font-size: 1.2rem;"><?= htmlspecialchars($signos['f_res']) ?> rpm</p>
                        </div>
                        <div>
                            <strong style="color: var(--gray-600);">Temperatura</strong>
                            <p style="font-size: 1.2rem;"><?= htmlspecialchars($signos['temperatura']) ?> °C</p>
                        </div>
                        <div>
                            <strong style="color: var(--gray-600);">Peso</strong>
                            <p style="font-size: 1.2rem;"><?= htmlspecialchars($signos['peso']) ?> kg</p>
                        </div>
                        <div>
                            <strong style="color: var(--gray-600);">Talla</strong>
                            <p style="font-size: 1.2rem;"><?= htmlspecialchars($signos['talla']) ?> cm</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Examen Físico -->
            <?php if (!empty($examen)): ?>
                <div class="card mb-4">
                    <h3>👨‍⚕️ Hallazgos Físicos</h3>
                    <div class="grid grid-2">
                        <?php 
                        $camposExamen = ['cabeza', 'ojos', 'oidos', 'nariz', 'boca', 'garganta', 'cuello', 'torax', 'corazon', 'pulmon', 'abdomen', 'extremidades_sup', 'piel', 'sistema_nervioso'];
                        foreach ($camposExamen as $campo): 
                            if (!empty($examen[$campo])):
                        ?>
                            <div style="margin-bottom: 0.5rem;">
                                <strong><?= ucfirst(str_replace('_', ' ', $campo)) ?>:</strong>
                                <span><?= htmlspecialchars($examen[$campo]) ?></span>
                            </div>
                        <?php 
                            endif;
                        endforeach; 
                        ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Análisis, Diagnóstico y Tratamiento -->
            <div class="card mb-4">
                <h3>📋 Detalles Clínicos</h3>
                
                <?php if (!empty($historia['analisis_plan'])): ?>
                    <div class="mb-3">
                        <h4>Análisis y Plan</h4>
                        <p><?= nl2br(htmlspecialchars($historia['analisis_plan'])) ?></p>
                    </div>
                <?php endif; ?>

                <?php if (!empty($historia['diagnostico'])): ?>
                    <div class="mb-3">
                        <h4>Diagnóstico</h4>
                        <div style="background: var(--info-bg); padding: 10px; border-radius: 4px;">
                            <?= nl2br(htmlspecialchars($historia['diagnostico'])) ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($historia['tratamiento'])): ?>
                    <div>
                        <h4>Tratamiento</h4>
                        <div style="background: var(--success-bg); padding: 10px; border-radius: 4px;">
                            <?= nl2br(htmlspecialchars($historia['tratamiento'])) ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($historia['observaciones'])): ?>
                    <div style="margin-top: 1rem; border-top: 1px solid var(--gray-200); padding-top: 1rem;">
                        <strong>Observaciones:</strong>
                        <p><?= nl2br(htmlspecialchars($historia['observaciones'])) ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Acciones -->
            <div class="card">
                <h3>⚡ Acciones</h3>
                
                <?php if ($historia['fecha_egreso']): ?>
                    <div class="alert alert-warning mb-4">
                        🔒 <strong>Historia Cerrada:</strong> Esta historia clínica se encuentra cerrada y en modo solo lectura. No se pueden agregar más registros.
                    </div>
                <?php endif; ?>

                <div class="flex gap-2 flex-wrap items-center">
                    <!-- Botón de impresión siempre visible -->
                    <a href="imprimir_historia.php?id=<?= $historia['id_historia'] ?>" class="btn btn-success" target="_blank">
                        🖨️ Imprimir Historia
                    </a>
                    
                    <?php if (!$historia['fecha_egreso']): ?>
                        <a href="registrar_examen.php?id=<?= $historia['id_historia'] ?>" class="btn btn-primary">
                            👨‍⚕️ Registrar Examen Físico
                        </a>
                        <a href="registrar_ordenes.php?id=<?= $historia['id_historia'] ?>" class="btn btn-success">
                            💊 Recetar / Órdenes
                        </a>
                        
                        <!-- Formulario para cerrar historia -->
                        <form method="POST" onsubmit="return confirm('¿Está seguro de cerrar esta historia clínica? No podrá realizar más cambios.')" style="display: inline;">
                            <input type="hidden" name="action" value="cerrar">
                            <button type="submit" class="btn btn-danger">
                                🔒 Cerrar Historia
                            </button>
                        </form>
                    <?php else: ?>
                        <button class="btn btn-secondary" disabled style="opacity: 0.6; cursor: not-allowed;">
                            👨‍⚕️ Registrar Examen Físico (Deshabilitado)
                        </button>
                        <button class="btn btn-secondary" disabled style="opacity: 0.6; cursor: not-allowed;">
                            💊 Recetar / Órdenes (Deshabilitado)
                        </button>
                    <?php endif; ?>

                    <a href="listar_historias.php" class="btn btn-secondary">
                        📋 Volver a Historias
                    </a>
                    <a href="../index.php" class="btn btn-outline">
                        🏠 Inicio
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
