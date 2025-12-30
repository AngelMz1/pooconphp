<?php
require 'vendor/autoload.php';
use App\SupabaseClient;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$supabase = new SupabaseClient($_ENV['SUPABASE_URL'], $_ENV['SUPABASE_KEY']);

echo "Checking Citas (Appointments)...\n";
$citas = $supabase->select('citas', '*', 'limit=5');
foreach ($citas as $c) {
    echo "Cita ID: {$c['id']} - Patient: {$c['paciente_id']} - Medico: {$c['medico_id']} - State: {$c['estado']}\n";
}

echo "\nChecking Consultas (Medical Records)...\n";
$consultas = $supabase->select('consultas', '*', 'limit=5');
foreach ($consultas as $c) {
    echo "Consulta ID: {$c['id_consulta']} - Patient: {$c['id_paciente']} - Medico: {$c['medico_id']} - State: {$c['estado']}\n";
}
