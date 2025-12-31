<?php
header('Content-Type: application/json');
require_once '../vendor/autoload.php';

use App\SupabaseClient;
use App\ReferenceData;
use Dotenv\Dotenv;

// Initialize dependencies
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$supabase = new SupabaseClient($_ENV['SUPABASE_URL'], $_ENV['SUPABASE_KEY']);
$refData = new ReferenceData($supabase);

$ciudad_id = $_GET['ciudad_id'] ?? null;

if (!$ciudad_id) {
    echo json_encode(['error' => 'Missing ciudad_id']);
    exit;
}

try {
    // Fetch barrios using the existing ReferenceData method
    // 'barrio' table, filtered by ciudad_id
    $barrios = $refData->getBarrios($ciudad_id);
    
    // Return empty array instead of null or error if none found
    if (!$barrios) {
        $barrios = [];
    }
    
    echo json_encode($barrios);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
