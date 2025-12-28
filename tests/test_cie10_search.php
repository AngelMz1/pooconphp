<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\SupabaseClient;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$supabase = new SupabaseClient($_ENV['SUPABASE_URL'], $_ENV['SUPABASE_KEY']);

echo "Prueba 1: Buscar código 'A00'\n";
$term = 'A00';
// Simular la lógica de api_cie10.php
$filter = "or=(codigo.ilike.*$term*,descripcion.ilike.*$term*)";
$results = $supabase->select('cie10', '*', $filter, 'codigo.asc', 5);

if (count($results) > 0) {
    echo "EXITO: Se encontraron " . count($results) . " resultados.\n";
    print_r($results[0]);
} else {
    echo "FALLO: No se encontraron resultados.\n";
}

echo "\nPrueba 2: Buscar descripción 'GRIPE'\n";
$term = 'GRIPE';
$filter = "or=(codigo.ilike.*$term*,descripcion.ilike.*$term*)";
$results = $supabase->select('cie10', '*', $filter, 'codigo.asc', 5);
if (count($results) > 0) {
    echo "EXITO: Se encontraron " . count($results) . " resultados.\n";
    print_r($results[0]);
} else {
    echo "FALLO: No se encontraron resultados para 'GRIPE'.\n";
}
