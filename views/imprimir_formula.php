require_once '../vendor/autoload.php';
require_once '../src/SupabaseClient.php';
require_once '../src/Configuracion.php';
require_once '../src/Medico.php';
require_once '../src/Paciente.php';
require_once '../includes/auth_helper.php';

use App\SupabaseClient;
use App\Configuracion;
use App\Medico;
use App\Paciente;
use App\ReferenceData;

$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

session_start();
requireLogin();

// We assume we receive 'id_historia' or 'id_consulta'
$id_historia = $_GET['id_historia'] ?? null;
if (!$id_historia) die("ID de historia no válido");

$supabase = new SupabaseClient($_ENV['SUPABASE_URL'], $_ENV['SUPABASE_KEY']);
$configModel = new Configuracion();
$config = $configModel->obtenerConfiguracion();

// Fetch History & Context (Assuming 'historias_clinicas' has links to patient and doctor or// MOCK fetch for Medication Name if possible, otherwise use ID or generic.
// In a real join we would get the name.
// For now, let's fetch the items from 'formulas_medicas'.
$formulas = $supabase->select('formulas_medicas', '*', "id_historia=eq.$id_historia");

// If we have access to table 'medicamentos' (catalog), we could map IDs.
// Let's try to fetch all medications to map names (inefficient but works for small catalog)
// Or just display 'Medicamento ID: X' if we can't join.
// The previous logic in FormulaMedica just inserts 'medicamento_id'.
// Let's instantiate ReferenceData or similar if it exists to get names, or assume straightforward display.
// I'll check if ReferenceData exists and has getMedicamentos.
require_once __DIR__ . '/../src/ReferenceData.php';
$refData = new ReferenceData($supabase);
$lista_meds = $refData->getMedicamentos();
$med_map = [];
foreach ($lista_meds as $m) {
    // Assuming structure of medicamentos table has 'id' and 'nombre' or similar
    $med_map[$m['id']] = $m['nombre'] ?? $m['descripcion'] ?? 'Med #' . $m['id'];
}

/*
$historia = $supabase->select('historias_clinicas', '*', "id_historia=eq.$id_historia");
*/
// Note: id_historia column might be 'id_historia' or 'id'. 
// Checking `src/HistoriaClinica.php` or `FormulaMedica.php`. 
// FormulaMedica uses 'id_historia' in insert, so that's likely the FK.
// HistoriaClinica PK is likely 'id_historia' based on 'historias_clinicas' table having it.


$historia = $supabase->select('historias_clinicas', '*', "id_historia=eq.$id_historia");
if (empty($historia)) die("Historia no encontrada");
$historia = $historia[0];

$pacienteModel = new Paciente();
$paciente = $pacienteModel->obtenerPaciente($historia['id_paciente']);

// Medico fetch - assuming medico_id is in history or we can get it from session if current user
// Or Historia might have medico_id. Let's check schema.
// Schema says `historias_clinicas` has `id_paciente`. 
// Does it have `medico_id`? `atender_consulta.php` saves it.
// Let's assume we can get it or display basic info.
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Fórmula Médica</title>
    <style>
        body { font-family: 'Arial', sans-serif; padding: 40px; }
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; }
        .company { font-size: 20px; font-weight: bold; color: <?php echo $config['color_principal']; ?>; }
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
        <div class="company"><?php echo htmlspecialchars($config['nombre_institucion']); ?></div>
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
