<?php
require 'vendor/autoload.php';
use App\SupabaseClient;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

$supabase = new SupabaseClient($_ENV['SUPABASE_URL'], $_ENV['SUPABASE_KEY']);

echo "Checking historias_clinicas columns...\n";
// Try to select one row to see keys
$data = $supabase->select('historias_clinicas', '*', 'limit=1');
if (!empty($data)) {
    print_r(array_keys($data[0]));
} else {
    echo "No rows found, cannot check columns easily via select *.\n";
    // If no rows, we might need to rely on the error message from a failed insert or just try to insert dummy to see error?
    // Or just look at the previous error: "Could not find the 'recomendaciones' column..."
    // I can try to insert a dummy row with id_consulta and see if it fails.
}
