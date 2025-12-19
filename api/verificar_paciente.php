<?php
require_once '../vendor/autoload.php';

use App\SupabaseClient;
use Dotenv\Dotenv;

// Cargar variables de entorno
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

header('Content-Type: application/json');

$doc_id = $_GET['doc_id'] ?? null;

if (!$doc_id) {
    echo json_encode(['error' => 'Documento ID requerido']);
    exit;
}

try {
    $supabase = new SupabaseClient($_ENV['SUPABASE_URL'], $_ENV['SUPABASE_KEY']);
    
    // Buscar paciente por documento
    $pacientes_data = $supabase->select('pacientes', '*', "documento_id=eq.$doc_id");
    
    if (!empty($pacientes_data)) {
        echo json_encode([
            'existe' => true,
            'paciente' => $pacientes_data[0]
        ]);
    } else {
        echo json_encode([
            'existe' => false
        ]);
    }
} catch (Exception $e) {
    echo json_encode(['error' => 'Error de conexión: ' . $e->getMessage()]);
}
?>