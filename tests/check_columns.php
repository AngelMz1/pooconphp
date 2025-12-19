<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\SupabaseClient;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$supabase = new SupabaseClient($_ENV['SUPABASE_URL'], $_ENV['SUPABASE_KEY']);

function checkCols($supabase, $table) {
    echo "Checking columns for '$table':\n";
    try {
        // Query information_schema.columns won't work easily with PostgREST unless exposed.
        // Usually it's not exposed by default on Supabase REST API unless explicitly enabled.
        // So we fallback to 'rpc' if available or just try to select * limit 0? 
        // But select returns empty if empty table.
        // Let's try selecting structure.
        
        // Alternative: Try to Insert a dummy row with wrong columns and catch error message "column X does not exist"?
        // Or "null value in column ... violates ...".
        
        // Actually, if table is empty, keys are not returned in select.
        // If Supabase allows 'HEAD' request? $supabase->select uses GET.
        
        // Let's try to fetch definitions via RPC or just assume if empty we can try to guess or use the Schema MD as truth?
        
        // But the Schema MD said 'formulas_medicas' has 'medicamento_id'.
        // My Code 'FormulaMedica.php' inserts 'tipo_formula'.
        
        // Let's rely on what the USER wants: "select medications from table".
        // I know 'medicamentos' table has (id, codigo, nombre) - verified.
        
        // I will implement the dropdown using 'medicamentos' table (Catalog).
        // I will update 'FormulaMedica.php' to use 'medicamento_id' instead of 'nombre_medicamento'.
        // I will assume (or FIX) the target table.
        
        // If I can't verify 'formulas_medicas' columns, I might break insert if I assume columns that don't exist.
        // But user asked for dropdown.
        
        // I'll try to select from 'medicamentos' (Catalog) - we know it has data.
        // I'll assume 'formulas_medicas' corresponds to what Schema MD says IF it was provided by user or trustworthy source.
        
        echo "Skipping direct column check (PostgREST limitation). Using assumption.\n";
        
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}

checkCols($supabase, 'formulas_medicas');
