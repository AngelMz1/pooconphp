<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/auth_helper.php';

// Solo cajeros y admins
requireRole(['cajero', 'admin']);

use App\SupabaseClient;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();
$supabase = new SupabaseClient($_ENV['SUPABASE_URL'], $_ENV['SUPABASE_KEY']);

// --- Lógica para Crear Cita ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'crear') {
    $paciente_id = $_POST['paciente_id'];
    $medico_id = $_POST['medico_id'];
    $fecha_hora = $_POST['fecha_hora']; // YYYY-MM-DDTHH:MM
    $motivo = $_POST['motivo'];

    try {
        $dateObj = new DateTime($fecha_hora);
        $hour = (int)$dateObj->format('H');
        $min = (int)$dateObj->format('i');
        $dayOfWeek = (int)$dateObj->format('w'); // 0 (Sun) - 6 (Sat)
        $dateStr = $dateObj->format('Y-m-d');
        $timeStr = $dateObj->format('H:i');

        // 1. Validar Horario (Lunes a Sábado, 7am - 6pm)
        // Domingo es 0.
        if ($dayOfWeek === 0) {
            throw new Exception("No se atienden citas los Domingos.");
        }
        if ($hour < 7 || $hour >= 18) { // 18:00 is closing time, so last appointment start? Or can accept 18:00? Usually close at 18:00 means last slot ends at 18:00.
             // If slots are 20 min, 17:40 is last slot. 18:00 is invalid to START.
             throw new Exception("El horario de atención es de 7:00 AM a 6:00 PM.");
        }

        // 2. Validar Intervalos de 20 minutos
        if ($min % 20 !== 0) {
            throw new Exception("Las citas deben ser en intervalos de 20 minutos (ej. 7:00, 7:20, 7:40).");
        }

        // 3. Validar Disponibilidad del Médico (Mismo día y hora)
        // state != cancelada
        $existing = $supabase->select('citas', 'id', "medico_id=eq.$medico_id&fecha_hora=eq.{$fecha_hora}&estado=neq.cancelada");
        if (!empty($existing)) {
            throw new Exception("El médico ya tiene una cita agendada en ese horario.");
        }

        // 4. Validar Citas del Paciente (Max 1 activa por semana)
        // "El paciente no puede tener dos citas activas en la misma semana"
        // Get week number
        $week = $dateObj->format('W');
        $year = $dateObj->format('Y');
        
        // Fetch pending citations for this patient. This is heavy but Supabase filtering by computed date is hard without stored proc.
        // We will fetch 'pendiente' appointments for this patient and check dates in PHP.
        // Optimization: Filter by date range of the week? 
        // Start/End of week for the requested date
        $monday = clone $dateObj;
        $monday->modify(('Sunday' === $dateObj->format('l')) ? 'Monday last week' : 'Monday this week');
        $saturday = clone $monday;
        $saturday->modify('+6 days'); // Next Sunday actually. Let's cover the week range.

        $startWeek = $monday->format('Y-m-d 00:00:00');
        $endWeek = $saturday->format('Y-m-d 23:59:59');

        $patientCitas = $supabase->select('citas', 'fecha_hora', "paciente_id=eq.$paciente_id&estado=eq.pendiente&fecha_hora=gte.$startWeek&fecha_hora=lte.$endWeek");
        
        // If there is ANY active appointment in this week, reject. "No puede tener dos" -> Already has 1 + New 1 = 2 => Reject.
        if (count($patientCitas) >= 1) {
            throw new Exception("El paciente ya tiene una cita activa esta semana. No se permiten dos citas activas en la misma semana.");
        }

        // Insertar
        $data = [
            'paciente_id' => $paciente_id,
            'medico_id' => $medico_id,
            'fecha_hora' => $fecha_hora,
            'motivo_consulta' => $motivo,
            'estado' => 'pendiente'
        ];
        
        $supabase->insert('citas', $data);
        header("Location: gestion_citas.php?msg=Cita Agendada correctamente");
        exit;

    } catch (Exception $e) {
        header("Location: gestion_citas.php?error=" . urlencode($e->getMessage()));
        exit;
    }
}

// --- Lógica para Cancelar Cita ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancelar') {
    $id = $_POST['cita_id'];
    $motivo = $_POST['motivo_cancelacion'];
    
    // Actualizar
    $supabase->update('citas', [
        'estado' => 'cancelada',
        'motivo_cancelacion' => $motivo
    ], "id.eq.$id");
    
    header("Location: gestion_citas.php?msg=Cita Cancelada");
    exit;
}

// Obtener listas para el formulario
$medicos = $supabase->select('medicos', 'id, primer_nombre, primer_apellido, especialidad_id');
$pacientes = $supabase->select('pacientes', 'id_paciente, primer_nombre, primer_apellido, documento_id');

// Obtener citas futuras
// Supabase query filter: fecha_hora >= now() ideally. 
// Simplificación: Traer últimas 100 y filtrar en PHP o usar filtro de fecha si la librería lo permite
// "fecha_hora.gte." . date('Y-m-d')
$citas = $supabase->select('citas', '*, pacientes(primer_nombre, primer_apellido, documento_id), users(nombre_completo)', null, 'fecha_hora.asc', 50);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Gestión de Citas</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body class="dashboard-container">
    <?php include '../includes/sidebar.php'; ?>
    <?php include '../includes/header.php'; ?>
    
    <main class="main-content">
        <div class="container">
            <h1>Gestión de Citas</h1>
            
            <?php if(isset($_GET['msg'])): ?>
                <div class="alert alert-success"><?= htmlspecialchars($_GET['msg']) ?></div>
            <?php endif; ?>
            <?php if(isset($_GET['error'])): ?>
                <div class="alert alert-error"><?= htmlspecialchars($_GET['error']) ?></div>
            <?php endif; ?>

            <!-- Formulario Nueva Cita -->
            <div class="card mb-4">
                <h2>Nueva Cita</h2>
                <form method="POST" class="form-row">
                    <input type="hidden" name="action" value="crear">
                    
                    <div class="form-group" style="flex: 1;">
                        <label>Paciente</label>
                        <select name="paciente_id" required>
                            <option value="">Seleccione Paciente...</option>
                            <?php foreach($pacientes as $p): ?>
                                <option value="<?= $p['id_paciente'] ?>">
                                    <?= $p['documento_id'] ?> - <?= $p['primer_nombre'] ?> <?= $p['primer_apellido'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group" style="flex: 1;">
                        <label>Médico</label>
                        <select name="medico_id" required>
                            <option value="">Seleccione Médico...</option>
                            <?php foreach($medicos as $m): ?>
                                <option value="<?= $m['id'] ?>">
                                    Dr. <?= $m['primer_nombre'] ?> <?= $m['primer_apellido'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group" style="flex: 1;">
                        <label>Fecha y Hora</label>
                        <input type="datetime-local" name="fecha_hora" required>
                        <small class="form-help">Lunes a Sábado, 7am-6pm. Intervalos de 20 min (ej. :00, :20, :40)</small>
                    </div>

                    <div class="form-group" style="flex: 2;">
                        <label>Motivo</label>
                        <input type="text" name="motivo" placeholder="Ej. Control general">
                    </div>

                    <div class="form-group" style="display: flex; align-items: flex-end;">
                        <button type="submit" class="btn btn-primary">Agendar</button>
                    </div>
                </form>
            </div>

            <!-- Listado de Citas -->
            <div class="card">
                <h2>Próximas Citas</h2>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Fecha/Hora</th>
                                <th>Paciente</th>
                                <th>Médico</th>
                                <th>Motivo</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($citas)): ?>
                                <tr><td colspan="6" class="text-center">No hay citas registradas</td></tr>
                            <?php else: ?>
                                <?php foreach($citas as $c): 
                                    $p = $c['pacientes'] ?? ['primer_nombre' => '?', 'primer_apellido' => '?'];
                                    $m = $c['users'] ?? ['nombre_completo' => '?'];
                                    $fecha = new DateTime($c['fecha_hora']);
                                ?>
                                    <tr>
                                        <td><?= $fecha->format('d/m/Y h:i A') ?></td>
                                        <td><?= $p['primer_nombre'] ?> <?= $p['primer_apellido'] ?></td>
                                        <td><?= $m['nombre_completo'] ?></td>
                                        <td><?= $c['motivo_consulta'] ?></td>
                                        <td>
                                            <span class="badge <?= $c['estado'] === 'cancelada' ? 'badge-danger' : ($c['estado'] === 'atendida' ? 'badge-success' : 'badge-primary') ?>">
                                                <?= ucfirst($c['estado']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if($c['estado'] === 'pendiente'): ?>
                                                <form method="POST" onsubmit="return confirm('¿Cancelar esta cita?');" style="display:inline;">
                                                    <input type="hidden" name="action" value="cancelar">
                                                    <input type="hidden" name="cita_id" value="<?= $c['id'] ?>">
                                                    <input type="text" name="motivo_cancelacion" placeholder="Motivo..." required class="mb-1" style="width: 150px; padding: 0.25rem;">
                                                    <button type="submit" class="btn btn-sm btn-danger">Cancelar</button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
