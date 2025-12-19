<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\SupabaseClient;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$supabase = new SupabaseClient($_ENV['SUPABASE_URL'], $_ENV['SUPABASE_KEY']);

echo "Checking 'formulas_medicas' table structure...\n";
try {
    $data = $supabase->select('formulas_medicas', '*', '');
    if (empty($data)) {
        echo "Table 'formulas_medicas' is empty or not found.\n";
    } else {
        echo "Columns in 'formulas_medicas':\n";
        print_r(array_keys($data[0]));
    }
} catch (Exception $e) {
    echo "Error checking 'formulas_medicas': " . $e->getMessage() . "\n";
}

echo "\nChecking 'medicamentos_recetados'...\n";
try {
    $data = $supabase->select('medicamentos_recetados', '*', '');
    if (empty($data)) {
        echo "Table 'medicamentos_recetados' empty (or not found).\n";
    } else {
        echo "Found 'medicamentos_recetados'. Columns:\n";
        print_r(array_keys($data[0]));
    }
} catch (Exception $e) {
}
