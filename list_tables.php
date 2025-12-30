<?php
require 'vendor/autoload.php';
use App\SupabaseClient;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

$supabase = new SupabaseClient($_ENV['SUPABASE_URL'], $_ENV['SUPABASE_KEY']);

echo "List of Tables:\n";
// There is no easy "SHOW TABLES" via PostgREST unless we query information_schema.
// But we might not have permissions.
// Alternatively, assume standard naming or try to guess.
// Let's try to select from 'ordenes', 'solicitudes', 'examenes'.
$tables = ['procedimientos_historia', 'ordenes_medicas', 'solicitudes', 'examenes', 'procedimientos_ordenados'];

foreach ($tables as $t) {
    try {
        $data = $supabase->select($t, '*', 'limit=1');
        echo "Table '$t' EXISTS.\n";
        if (!empty($data)) print_r(array_keys($data[0]));
    } catch (Exception $e) {
        // likely 404
        echo "Table '$t' does not exist (or 404).\n";
    }
}
