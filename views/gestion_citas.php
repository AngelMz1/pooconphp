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
    $data = [
        'paciente_id' => $_POST['paciente_id'],
        'medico_id' => $_POST['medico_id'],
        'fecha_hora' => $_POST['fecha_hora'], // Formato datetime-local: YYYY-MM-DDTHH:MM
        'motivo_consulta' => $_POST['motivo'],
        'estado' => 'pendiente'
    ];
    
    // TODO: Validar que no haya choque de horario (30 min)
    $supabase->insert('citas', $data);
    header("Location: gestion_citas.php?msg=Cita Agendada");
    exit;
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
$medicos = $supabase->select('users', '*', "rol.eq.medico");
$pacientes = $supabase->select('pacientes', 'id_paciente, primer_nombre, primer_apellido, documento_id'); // Simple lista

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
                                <option value="<?= $m['id'] ?>"><?= $m['nombre_completo'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group" style="flex: 1;">
                        <label>Fecha y Hora</label>
                        <input type="datetime-local" name="fecha_hora" required>
                        <small class="form-help">Intervalos de 30 min</small>
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
