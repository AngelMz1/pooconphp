<?php
require_once '../vendor/autoload.php';
require_once '../includes/auth_helper.php';

use App\SupabaseClient;
use App\Consulta;
use App\Paciente;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

requirePermission('ver_historia'); // Verificar permiso para ver consultas

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: listar_consultas.php');
    exit;
}

$supabase = new SupabaseClient($_ENV['SUPABASE_URL'], $_ENV['SUPABASE_KEY']);
$consultaModel = new Consulta($supabase);
$pacienteModel = new Paciente($supabase);

$error = '';
$consulta = null;
$paciente = null;

try {
    $consulta = $consultaModel->obtenerPorId($id);
    if (!$consulta) {
        throw new Exception("Consulta no encontrada");
    }
    
    $paciente = $pacienteModel->obtenerPorId($consulta['id_paciente']);
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Consulta - Sistema de Gestión Médica</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <div class="dashboard-container">
        <?php include '../includes/sidebar.php'; ?>
        <?php include '../includes/header.php'; ?>
        
        <main class="main-content">
            <div class="container">
                <?php if ($error): ?>
                    <div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div>
                    <div class="text-center">
                        <a href="listar_consultas.php" class="btn btn-primary">← Volver a la lista</a>
                    </div>
                <?php elseif ($consulta): ?>
                    <!-- Header -->
                    <div class="card card-gradient text-center mb-4">
                        <h1>🩺 Detalles de la Consulta</h1>
                        <p style="margin-bottom: 1rem;">
                            Consulta #<?= htmlspecialchars($consulta['id_consulta']) ?>
                        </p>
                        <div style="display: flex; gap: 0.5rem; justify-content: center; flex-wrap: wrap;">
                            <a href="atender_consulta.php?id=<?= $consulta['id_consulta'] ?>" class="btn btn-success">
                                ✏️ Atender Consulta
                            </a>
                            <a href="listar_consultas.php" class="btn" style="background: rgba(255,255,255,0.2); color: white;">
                                ← Volver a la Lista
                            </a>
                        </div>
                    </div>

                    <!-- Información del Paciente -->
                    <?php if ($paciente): ?>
                    <div class="card mb-4">
                        <h2>👤 Información del Paciente</h2>
                        <div class="grid grid-2">
                            <div>
                                <p><strong>Nombre:</strong> <?= htmlspecialchars($paciente['primer_nombre'] . ' ' . $paciente['primer_apellido']) ?></p>
                                <p><strong>Documento:</strong> <?= htmlspecialchars($paciente['documento_id']) ?></p>
                            </div>
                            <div>
                                <p><strong>Teléfono:</strong> <?= htmlspecialchars($paciente['telefono'] ?? 'No registrado') ?></p>
                                <p><strong>Edad:</strong> 
                                    <?php if (!empty($paciente['fecha_nacimiento'])): ?>
                                        <?php
                                        $fechaNac = new DateTime($paciente['fecha_nacimiento']);
                                        $hoy = new DateTime();
                                        $edad = $hoy->diff($fechaNac)->y;
                                        echo $edad . ' años';
                                        ?>
                                    <?php else: ?>
                                        No registrada
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Información de la Consulta -->
                    <div class="card mb-4">
                        <h2>📋 Detalles de la Consulta</h2>
                        <div class="grid grid-2">
                            <div>
                                <p><strong>Fecha:</strong> 
                                    <?php if (!empty($consulta['created_at'])): ?>
                                        <?php
                                        $fecha = new DateTime($consulta['created_at']);
                                        echo $fecha->format('d/m/Y H:i');
                                        ?>
                                    <?php else: ?>
                                        No registrada
                                    <?php endif; ?>
                                </p>
                                <p><strong>Estado:</strong> 
                                    <span class="badge <?= $consulta['estado'] == 'completada' ? 'badge-success' : 'badge-primary' ?>">
                                        <?= ucfirst($consulta['estado'] ?? 'pendiente') ?>
                                    </span>
                                </p>
                            </div>
                            <div>
                                <p><strong>Tipo:</strong> <?= htmlspecialchars($consulta['tipo_consulta'] ?? 'General') ?></p>
                                <p><strong>Médico:</strong> <?= htmlspecialchars($consulta['medico_nombre'] ?? 'No asignado') ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Motivo de Consulta -->
                    <div class="card mb-4">
                        <h3>🗣️ Motivo de Consulta</h3>
                        <p><?= nl2br(htmlspecialchars($consulta['motivo_consulta'] ?? 'No especificado')) ?></p>
                    </div>

                    <!-- Enfermedad Actual -->
                    <?php if (!empty($consulta['enfermedad_actual'])): ?>
                    <div class="card mb-4">
                        <h3>🏥 Enfermedad Actual</h3>
                        <p><?= nl2br(htmlspecialchars($consulta['enfermedad_actual'])) ?></p>
                    </div>
                    <?php endif; ?>

                    <!-- Observaciones -->
                    <?php if (!empty($consulta['observaciones'])): ?>
                    <div class="card mb-4">
                        <h3>📝 Observaciones</h3>
                        <p><?= nl2br(htmlspecialchars($consulta['observaciones'])) ?></p>
                    </div>
                    <?php endif; ?>

                    <!-- Acciones -->
                    <div class="card">
                        <h3>⚡ Acciones</h3>
                        <div class="flex gap-2 flex-wrap">
                            <a href="atender_consulta.php?id=<?= $consulta['id_consulta'] ?>" class="btn btn-success">
                                🩺 Atender Consulta
                            </a>
                            <a href="listar_consultas.php" class="btn btn-secondary">
                                ← Volver a la lista
                            </a>
                            <a href="../index.php" class="btn btn-outline">
                                🏠 Inicio
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>