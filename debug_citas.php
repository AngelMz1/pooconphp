<?php
require 'vendor/autoload.php';
use App\SupabaseClient;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

session_start();
// Mock session if not set via CLI
if (!isset($_SESSION['user_id'])) {
    // Assuming we want to debug for user ID 1 (admin/medico often same for dev)
    // Or we will just check all linkages.
    echo "No session in CLI. Checking general data linkages...\n";
}

$supabase = new SupabaseClient($_ENV['SUPABASE_URL'], $_ENV['SUPABASE_KEY']);

echo "1. Scalipping Users check (Table might be protected or named differently)...\n";
// $users = $supabase->select('usuarios', '*');
// print_r($users);

echo "\n2. Checking Medicos linked to Users...\n";
$medicos = $supabase->select('medicos', '*');
foreach ($medicos as $m) {
    echo "Medico ID: {$m['id']} - Name: {$m['primer_nombre']} {$m['primer_apellido']} - UserID: {$m['user_id']}\n";
}

echo "\n3. Checking Pending Consultas...\n";
$consultas = $supabase->select('consultas', '*', "estado=eq.pendiente");
foreach ($consultas as $c) {
    echo "Consulta ID: {$c['id_consulta']} - Patient: {$c['id_paciente']} - MedicoID: {$c['medico_id']} - Status: {$c['estado']}\n";
}

echo "\nAnalysis:\n";
foreach ($medicos as $m) {
    $count = 0;
    foreach ($consultas as $c) {
        if ($c['medico_id'] == $m['id']) $count++;
    }
    echo "Medico {$m['primer_nombre']} (ID {$m['id']}) has $count pending consultations.\n";
}
