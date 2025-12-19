<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\SupabaseClient;
use App\HistoriaClinica;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$supabase = new SupabaseClient($_ENV['SUPABASE_URL'], $_ENV['SUPABASE_KEY']);
$historiaModel = new HistoriaClinica($supabase);

echo "Testing History Closing Logic...\n";

// 1. Create a dummy history (assuming patient 1 exists, usually does)
// We need a valid patient ID. Let's list one.
$pacientes = $supabase->select('pacientes', 'id_paciente', '', 'id_paciente.asc');
if (empty($pacientes)) {
    die("No patients found to test.\n");
}
$id_paciente = $pacientes[0]['id_paciente'];

$datos = [
    'id_paciente' => $id_paciente,
    'fecha_ingreso' => date('Y-m-d H:i:s'),
    'analisis_plan' => 'Test History for Closing'
];

try {
    $res = $historiaModel->crear($datos); // Assuming crear returns array/object
    // In previous code, crear returned result array.
    // Let's inspect Model::crear. It calls supabase->insert.
    // Response from insert usually contains the data.
    
    // Actually, let's fetch the last inserted one if needed.
    // Or assume result[0]['id_historia']
    
    $id_historia = $res[0]['id_historia'];
    echo "Created History ID: $id_historia\n";
    
    // 2. Verify it's open (fecha_egreso is null)
    $h = $historiaModel->obtenerPorId($id_historia);
    if ($h['fecha_egreso']) {
        echo "Error: History should be open initially.\n";
    } else {
        echo "Pass: History is initially open.\n";
    }
    
    // 3. Close it
    echo "Closing history...\n";
    $historiaModel->cerrar($id_historia);
    
    // 4. Verify it's closed
    $h = $historiaModel->obtenerPorId($id_historia);
    if ($h['fecha_egreso']) {
        echo "Pass: History is closed. Fecha Egreso: " . $h['fecha_egreso'] . "\n";
    } else {
        echo "Error: History failed to close.\n";
    }
    
    // Clean up
    $historiaModel->eliminar($id_historia);
    echo "Cleaned up test history.\n";
    
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
