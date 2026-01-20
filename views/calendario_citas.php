<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/auth_helper.php';

// Solo medicos y admins
requireRole(['medico', 'admin']);

use App\SupabaseClient;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();
$supabase = new SupabaseClient($_ENV['SUPABASE_URL'], $_ENV['SUPABASE_KEY']);

$medico_id = $_SESSION['user_id'];
$rol = $_SESSION['user_role'];

// Si es admin, quizás quiera ver las citas de todos o seleccionar. Por defecto, si es admin, mostramos todo.
$filter = ($rol === 'medico') ? "medico_id.eq.$medico_id" : null;

// Marcar como atendida
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'atender') {
    $cita_id = $_POST['cita_id'];
    $supabase->update('citas', ['estado' => 'atendida'], "id.eq.$cita_id");
    header("Location: calendario_citas.php?msg=Cita marcada como atendida");
    exit;
}

// Obtener citas (pendientes y atendidas del futuro/hoy)
// Filtrar por estado pendiente para "Por atender"
$citas_pendientes = $supabase->select('citas', '*, pacientes(*)', ($filter ? "$filter&" : "") . "estado=eq.pendiente", 'fecha_hora.asc');

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Mi Calendario Médico</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body class="dashboard-container">
    <?php include '../includes/sidebar.php'; ?>
    <?php include '../includes/header.php'; ?>
    
    <main class="main-content">
        <div class="container">
            <h1>📅 Mi Agenda</h1>

            <div class="grid-2">
                <!-- Columna: Próximos Pacientes -->
                <div>
                    <h3>Pacientes Pendientes</h3>
                    <?php if(empty($citas_pendientes)): ?>
                        <div class="card">
                            <p class="text-center text-muted">No tienes pacientes pendientes.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach($citas_pendientes as $cita): 
                            $fecha = new DateTime($cita['fecha_hora']);
                            $hoy = new DateTime();
                            $esHoy = $fecha->format('Y-m-d') === $hoy->format('Y-m-d');
                            $paciente = $cita['pacientes'];
                        ?>
                        <div class="card mb-2" style="border-left: 5px solid <?= $esHoy ? 'var(--primary)' : 'var(--gray-400)' ?>">
                            <div class="flex justify-between items-center">
                                <div>
                                    <div style="font-size: 1.2rem; font-weight: bold; color: var(--primary);">
                                        <?= $fecha->format('h:i A') ?>
                                        <?php if($esHoy): ?>
                                            <span class="badge badge-success">HOY</span>
                                        <?php else: ?>
                                            <span class="badge"><?= $fecha->format('d/m/Y') ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <h4 class="mb-1 mt-1"><?= $paciente['primer_nombre'] ?> <?= $paciente['primer_apellido'] ?></h4>
                                    <p class="mb-0 text-sm"><?= $cita['motivo_consulta'] ?></p>
                                </div>
                                <div class="text-right">
                                    <a href="atender_consulta.php?cita_id=<?= $cita['id'] ?>" class="btn btn-sm btn-primary">Atender</a>
                                    <a href="ver_paciente.php?id=<?= $paciente['id_paciente'] ?>" class="btn btn-sm btn-secondary mt-1">Ver Historia</a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Columna: Resumen / Calendario Visual (Placeholder) -->
                <div>
                    <div class="card">
                        <h3>Resumen del Día</h3>
                        <div class="stat-card">
                            <div class="stat-value"><?= count($citas_pendientes) ?></div>
                            <div class="stat-label">Citas Pendientes</div>
                        </div>
                        <div class="mt-4">
                            <p class="text-sm text-center">
                                Las citas se asignan en intervalos de 30 minutos.<br>
                                Si necesitas cancelar o reprogramar, contacta a caja.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
