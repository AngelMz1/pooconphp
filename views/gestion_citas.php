<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/auth_helper.php';

// Solo cajeros y admins
requireRole(['cajero', 'admin']);

use App\SupabaseClient;
use Dotenv\Dotenv;

// Debugging 500
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();
$supabase = new SupabaseClient($_ENV['SUPABASE_URL'], $_ENV['SUPABASE_KEY']);

// --- Auto-Cancelación de Citas Vencidas ---
try {
    // Usar formato ISO 8601 para la API (con T)
    $nowStr = date('Y-m-d\TH:i:s');
    $expired = $supabase->select('citas', 'id', "fecha_hora=lt.$nowStr&estado=in.(pendiente,por_confirmar)");

    if (!empty($expired)) {
        foreach ($expired as $ex) {
            $supabase->update('citas', [
                'estado' => 'cancelada',
                'motivo_cancelacion' => 'Vencimiento automático por sistema'
            ], "id.eq." . $ex['id']);
        }
    }
} catch (Throwable $e) {
    // Silenciar error en auto-cancelación
}

// --- Lógica para Crear Cita ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'crear') {
    try { // Inicio del bloque try para capturar TODAS las excepciones de validación

    // Buscar ID de paciente por Documento
    $doc_paciente = $_POST['documento_paciente'];
    $pacienteData = $supabase->select('pacientes', 'id_paciente', "documento_id=eq.$doc_paciente");
    
    if (empty($pacienteData)) {
        header("Location: gestion_citas.php?error=Paciente no encontrado con el documento: " . urlencode($doc_paciente));
        exit;
    }
    $paciente_id = $pacienteData[0]['id_paciente'];

    $medico_id = $_POST['medico_id'] ?? null;
    if (empty($medico_id)) {
        throw new Exception("Debe seleccionar un médico válido.");
    }
    
    // Combinar Fecha y Hora
    $fecha_post = $_POST['fecha_cita'];
    $hora_post = $_POST['hora_cita'];
    $fecha_hora = $fecha_post . ' ' . $hora_post; // YYYY-MM-DD HH:MM para Insert
    $fecha_hora_iso = $fecha_post . 'T' . $hora_post . ':00'; // Formato ISO para Consultas GET

    $motivo = $_POST['motivo'];

        $dateObj = new DateTime($fecha_hora);
        $hour = (int)$dateObj->format('H');
        $min = (int)$dateObj->format('i');
        $dayOfWeek = (int)$dateObj->format('w'); // 0 (Sun) - 6 (Sat)
        
        // 1. Validar Horario (Lunes a Sábado, 7am - 6pm)
        if ($dayOfWeek === 0) {
            throw new Exception("No se atienden citas los Domingos.");
        }
        // Validacion redundante si usamos Select, pero necesaria por seguridad backend
        if ($hour < 7 || $hour >= 18) { 
             throw new Exception("El horario de atención es de 7:00 AM a 6:00 PM.");
        }

        // 2. Validar Intervalos de 20 minutos
        if ($min % 20 !== 0) {
            throw new Exception("Las citas deben ser en intervalos de 20 minutos.");
        }

        // 3. Validar Disponibilidad del Médico (Mismo día y hora)
        // state != cancelada
        // IMPORTANTE: Usar formato ISO y asegurarnos que medico_id no sea nulo
        $existing = $supabase->select('citas', 'id', "medico_id=eq.$medico_id&fecha_hora=eq.$fecha_hora_iso&estado=neq.cancelada");
        if (!empty($existing)) {
            throw new Exception("El médico ya tiene una cita agendada en ese horario.");
        }

        // 4. Validar Citas del Paciente (Max 1 activa por semana)
        $week = $dateObj->format('W');
        
        $monday = clone $dateObj;
        $monday->modify(('Sunday' === $dateObj->format('l')) ? 'Monday last week' : 'Monday this week');
        $saturday = clone $monday;
        $saturday->modify('+6 days'); 

        $startWeek = $monday->format('Y-m-d 00:00:00');
        $endWeek = $saturday->format('Y-m-d 23:59:59');

        $patientCitas = $supabase->select('citas', 'fecha_hora', "paciente_id=eq.$paciente_id&estado=eq.pendiente&fecha_hora=gte.$startWeek&fecha_hora=lte.$endWeek");
        
        if (count($patientCitas) >= 1) {
            throw new Exception("El paciente ya tiene una cita activa esta semana. No se permiten dos citas activas en la misma semana.");
        }

        // Insertar
        $data = [
            'paciente_id' => $paciente_id,
            'medico_id' => $medico_id,
            'fecha_hora' => $fecha_hora,
            'motivo_consulta' => $motivo,
            'estado' => 'por_confirmar' // Nuevo estado inicial
        ];
        
        $supabase->insert('citas', $data);
        header("Location: gestion_citas.php?msg=Cita Agendada (Requiere Confirmación)");
        exit;

    } catch (Exception $e) {
        header("Location: gestion_citas.php?error=" . urlencode($e->getMessage()));
        exit;
    }
}

// --- Acción: Confirmar Cita ---
// Verificación de permiso redundante pero segura
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'confirmar') {
    if (!hasPermission('confirmar_citas')) {
        header("Location: gestion_citas.php?error=No tiene permiso para confirmar citas");
        exit;
    }
    $id = $_POST['cita_id'];
    try {
        $result = $supabase->update('citas', ['estado' => 'pendiente'], "id=eq.$id");
        header("Location: gestion_citas.php?msg=" . urlencode("Cita Confirmada (Visible para el médico)"));
        exit;
    } catch (Exception $e) {
        header("Location: gestion_citas.php?error=" . urlencode("Error al confirmar: " . $e->getMessage()));
        exit;
    }
}

// --- Lógica para Cancelar Cita ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancelar') {
    if (!hasPermission('cancelar_citas')) {
        header("Location: gestion_citas.php?error=No tiene permiso para cancelar citas");
        exit;
    }
    $id = $_POST['cita_id'];
    $motivo = $_POST['motivo_cancelacion'];
    
    // Actualizar
    $supabase->update('citas', [
        'estado' => 'cancelada',
        'motivo_cancelacion' => $motivo
    ], "id=eq.$id");
    
    header("Location: gestion_citas.php?msg=Cita Cancelada");
    exit;
}

// Obtener listas para el formulario
try {
    // Obtenemos 'user_id' de medicos para la FK correcta en citas
    // Cambiamos a '*' para asegurar que no falla por nombres de columna incorrectos
    $medicos = $supabase->select('medicos', '*'); 
    $pacientes = $supabase->select('pacientes', 'id_paciente, primer_nombre, primer_apellido, documento_id');
    $citas = $supabase->select('citas', '*, pacientes(primer_nombre, primer_apellido, documento_id), users(nombre_completo)', null, 'fecha_hora.asc', 50);
} catch (Throwable $e) {
    die("<h1>Error Crítico</h1><pre>" . $e->getMessage() . "\n" . $e->getTraceAsString() . "</pre>");
}

// Generar Slots de Hora (07:00 - 17:40)
$timeSlots = [];
$start = strtotime('07:00');
$end = strtotime('17:40');
while ($start <= $end) {
    $timeSlots[] = date('H:i', $start);
    $start = strtotime('+20 minutes', $start);
}
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
                        <label>Documento Paciente</label>
                        <input type="text" name="documento_paciente" list="pacientes_list" required placeholder="Escriba documento..." autocomplete="off">
                        <datalist id="pacientes_list">
                            <?php foreach($pacientes as $p): ?>
                                <option value="<?= $p['documento_id'] ?>">
                                    <?= $p['primer_nombre'] ?> <?= $p['primer_apellido'] ?>
                                </option>
                            <?php endforeach; ?>
                        </datalist>
                    </div>

                    <div class="form-group" style="flex: 1;">
                        <label>Médico <span style="color: red;">*</span></label>
                        <select name="medico_id" required>
                            <option value="">-- Seleccione Médico --</option>
                            <?php foreach($medicos as $m): 
                                // Solo mostrar médicos con user_id vinculado
                                if (empty($m['user_id'])) continue;
                            ?>
                                <option value="<?= $m['user_id'] ?>">
                                    Dr. <?= $m['primer_nombre'] ?> <?= $m['primer_apellido'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group" style="flex: 1;">
                        <label>Fecha</label>
                        <input type="date" name="fecha_cita" required min="<?= date('Y-m-d') ?>">
                    </div>

                    <div class="form-group" style="flex: 1;">
                        <label>Hora</label>
                        <select name="hora_cita" required>
                            <option value="">Seleccione Hora...</option>
                            <?php foreach($timeSlots as $slot): ?>
                                <option value="<?= $slot ?>"><?= $slot ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-help">Horario Habil.</small>
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
            
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const dateInput = document.querySelector('input[name="fecha_cita"]');
                    const timeSelect = document.querySelector('select[name="hora_cita"]');
                    const medicoSelect = document.querySelector('select[name="medico_id"]'); // Necesitamos saber el medico
                    
                    let occupiedSlots = []; // Cache simple

                    async function fetchAvailability() {
                        const date = dateInput.value;
                        const medico = medicoSelect.value;
                        
                        if (!date || !medico) {
                            occupiedSlots = [];
                            updateTimeOptions();
                            return;
                        }

                        // Mostrar estado de carga si se desea (opcional)
                        timeSelect.disabled = true;

                        try {
                            const res = await fetch(`../api/api_availability.php?medico_id=${medico}&fecha=${date}`);
                            const data = await res.json();
                            occupiedSlots = data.occupied || [];
                        } catch (e) {
                            console.error("Error fetching availability", e);
                            occupiedSlots = [];
                        } finally {
                            timeSelect.disabled = false;
                            updateTimeOptions();
                        }
                    }

                    function updateTimeOptions() {
                        const selectedDate = new Date(dateInput.value + 'T00:00:00');
                        const now = new Date();
                        const isToday = selectedDate.toDateString() === now.toDateString();
                        
                        const options = timeSelect.options;
                        for (let i = 0; i < options.length; i++) {
                            const opt = options[i];
                            if (!opt.value) continue;
                            
                            let isDisabled = false;
                            let labelSuffix = '';

                            // 1. Filter past times if today
                            if (isToday) {
                                const [h, m] = opt.value.split(':').map(Number);
                                const slotDate = new Date(now);
                                slotDate.setHours(h, m, 0, 0);
                                if (slotDate < now) {
                                    isDisabled = true;
                                    labelSuffix = ' (Pasada)';
                                }
                            }

                            // 2. Filter occupied times (from API)
                            if (occupiedSlots.includes(opt.value)) {
                                isDisabled = true;
                                labelSuffix = ' (Ocupada)';
                            }
                            
                            // Apply state
                            opt.disabled = isDisabled;
                            // Reset text content to original slot value then append suffix
                            opt.textContent = opt.value + labelSuffix;
                            
                            if (isDisabled) {
                                opt.style.color = '#ccc';
                                // Si estaba seleccionada, deseleccionar
                                if (timeSelect.value === opt.value) timeSelect.value = "";
                            } else {
                                opt.style.color = '';
                            }
                        }
                    }
                    
                    dateInput.addEventListener('change', fetchAvailability);
                    medicoSelect.addEventListener('change', fetchAvailability);
                    
                    // Run once on load if values exist
                    if(dateInput.value && medicoSelect.value) fetchAvailability();
                    else if (dateInput.value) updateTimeOptions();
                });
            </script>

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
                                            <?php 
                                            $badgeClass = 'badge-primary';
                                            if ($c['estado'] === 'cancelada') $badgeClass = 'badge-danger';
                                            elseif ($c['estado'] === 'atendida') $badgeClass = 'badge-success';
                                            elseif ($c['estado'] === 'por_confirmar') $badgeClass = 'badge-warning';
                                            ?>
                                            <span class="badge <?= $badgeClass ?>">
                                                <?= ucfirst(str_replace('_', ' ', $c['estado'])) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php 
                                            // Lógica de Permisos
                                            $canConfirm = hasPermission('confirmar_citas');
                                            $canCancel = hasPermission('cancelar_citas');
                                            
                                            // Lógica de Tiempo
                                            $now = new DateTime();
                                            $isPast = $fecha < $now;
                                            
                                            echo "<!-- Debug: Estado={$c['estado']}, CanConfirm=$canConfirm, IsPast=$isPast -->";
                                            
                                            if ($isPast && ($c['estado'] === 'por_confirmar' || $c['estado'] === 'pendiente')) {
                                                echo '<span class="badge badge-secondary">Vencida</span>';
                                            } else {
                                                // Botón Confirmar
                                                if($c['estado'] === 'por_confirmar' && $canConfirm): ?>
                                                    <form method="POST" style="display:inline; margin-right: 5px;">
                                                        <input type="hidden" name="action" value="confirmar">
                                                        <input type="hidden" name="cita_id" value="<?= $c['id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-success" title="Confirmar" onclick="return confirm('¿Confirmar esta cita?')">
                                                            ✅ Confirmar
                                                        </button>
                                                    </form>
                                                <?php endif;

                                                // Botón Cancelar
                                                if(($c['estado'] === 'pendiente' || $c['estado'] === 'por_confirmar') && $canCancel): ?>
                                                    <form method="POST" onsubmit="return confirm('¿Cancelar esta cita?');" style="display:inline;">
                                                        <input type="hidden" name="action" value="cancelar">
                                                        <input type="hidden" name="cita_id" value="<?= $c['id'] ?>">
                                                        <input type="text" name="motivo_cancelacion" placeholder="Motivo..." required style="width: 120px; padding: 0.25rem; margin-bottom: 2px;">
                                                        <button type="submit" class="btn btn-sm btn-danger">❌ Cancelar</button>
                                                    </form>
                                                <?php endif;
                                                
                                                // Si no hay botones, mostrar estado
                                                if (!($c['estado'] === 'por_confirmar' && $canConfirm) && !(($c['estado'] === 'pendiente' || $c['estado'] === 'por_confirmar') && $canCancel)) {
                                                    echo '<span class="badge badge-info">Sin acciones</span>';
                                                }
                                            }
                                            ?>
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
