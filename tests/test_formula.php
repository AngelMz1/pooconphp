<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\SupabaseClient;
use App\HistoriaClinica;
use App\FormulaMedica;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$supabase = new SupabaseClient($_ENV['SUPABASE_URL'], $_ENV['SUPABASE_KEY']);
$historiaModel = new HistoriaClinica($supabase);
$formulaModel = new FormulaMedica($supabase);

echo "Testing Formula Creation...\n";

// 1. Get History (create if none)
$historias = $historiaModel->obtenerTodas(1);
if (empty($historias)) {
    // Create dummy info if no history
    $pacientes = $supabase->select('pacientes', 'id_paciente', '', 'id_paciente.asc');
    if (empty($pacientes)) die("No patients.");
    $id_paciente = $pacientes[0]['id_paciente'];
    $dataH = ['id_paciente' => $id_paciente, 'fecha_ingreso' => date('Y-m-d H:i:s')];
    $res = $historiaModel->crear($dataH); // Assuming returns [0] => row or similar
    // Check return value structure of crear...
    // Previous analysis: insert returns array of rows.
    $id_historia = $res[0]['id_historia'];
} else {
    $id_historia = $historias[0]['id_historia'];
}
echo "Using History ID: $id_historia\n";

// 2. Get Medication ID
$meds = $supabase->select('medicamentos', 'id', '');
if (empty($meds)) die("No meds in catalog.");
$id_med = $meds[0]['id'];
echo "Using Med ID: $id_med\n";

// 3. Add Medication to Formula
$datos = [
    'id_historia' => $id_historia,
    'medicamento_id' => $id_med,
    'cantidad_total' => 10,
    'dosis' => '500mg', // Start of string
    'frecuencia' => 'C/8h',
    'via_administracion' => 'Oral',
    'duracion' => '5 dias',
    'observaciones' => 'Tomar con agua'
];

try {
    echo "Adding medication...\n";
    $res = $formulaModel->agregarMedicamento($datos);
    print_r($res);
    
    echo "Fetching items for history...\n";
    $items = $formulaModel->obtenerPorHistoria($id_historia);
    print_r($items);
    
    // Check if added item exists
    $found = false;
    foreach ($items as $item) {
        if ($item['medicamento_id'] == $id_med && strpos($item['dosis'], 'C/8h') !== false) {
            $found = true;
            break;
        }
    }
    
    if ($found) {
        echo "PASS: Medication added successfully.\n";
    } else {
        echo "FAIL: Medication not found in list.\n";
    }
    
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
