<?php
require 'vendor/autoload.php';
use App\SupabaseClient;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

$supabase = new SupabaseClient($_ENV['SUPABASE_URL'], $_ENV['SUPABASE_KEY']);

echo "Checking procedimientos columns...\n";
$data = $supabase->select('procedimientos', '*', 'limit=1');
if (!empty($data)) {
    print_r(array_keys($data[0]));
} else {
    // If empty, assume id_procedimiento, id_historia, codigo_cups, nombre_procedimiento, cantidad, justificacion based on code usage.
    // Trying to be safe.
    echo "No rows found in procedimientos.\n";
}
