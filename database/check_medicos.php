<?php
require_once __DIR__ . '/../vendor/autoload.php';
use App\DatabaseFactory;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
try { $dotenv->load(); } catch (Exception $e) {}

try {
    $db = DatabaseFactory::create();
    
    echo "--- Checking Medicos Table ---\n";
    $medicos = $db->select('medicos', '*');
    echo "Total Medicos: " . count($medicos) . "\n";
    foreach ($medicos as $m) {
        echo "ID: {$m['id']} | Name: {$m['primer_nombre']} {$m['primer_apellido']} | UserID: " . ($m['user_id'] ?? 'NULL') . "\n";
    }

    echo "\n--- Checking Users (Role: medico) ---\n";
    $users = $db->select('users', '*', "rol=eq.medico");
    echo "Total Medico Users: " . count($users) . "\n";
    foreach ($users as $u) {
        echo "ID: {$u['id']} | User: {$u['username']} | Name: {$u['nombre_completo']}\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
