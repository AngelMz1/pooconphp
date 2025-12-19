<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\SupabaseClient;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$supabase = new SupabaseClient($_ENV['SUPABASE_URL'], $_ENV['SUPABASE_KEY']);

echo "Checking 'medicamentos' table structure...\n";
try {
    $data = $supabase->select('medicamentos', '*', '');
    if (empty($data)) {
        echo "Table 'medicamentos' is empty or not found.\n";
    } else {
        echo "Columns in 'medicamentos':\n";
        print_r(array_keys($data[0]));
        echo "Sample data:\n";
        print_r($data[0]);
    }
} catch (Exception $e) {
    echo "Error checking 'medicamentos': " . $e->getMessage() . "\n";
}

echo "\nChecking 'catalogo_medicamentos'...\n";
try {
    $data = $supabase->select('catalogo_medicamentos', '*', '');
    if (empty($data)) {
        echo "Table 'catalogo_medicamentos' is empty (or doesn't exist).\n";
    } else {
        echo "Found 'catalogo_medicamentos'. Columns:\n";
        print_r(array_keys($data[0]));
    }
} catch (Exception $e) {
    // echo "Error checking 'catalogo_medicamentos': " . $e->getMessage() . "\n";
}
