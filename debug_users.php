<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/includes/auth_helper.php';

use App\SupabaseClient;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();
$supabase = new SupabaseClient($_ENV['SUPABASE_URL'], $_ENV['SUPABASE_KEY']);

echo "--- DEBUG USERS ---\n";

// 1. Fetch Users
try {
    // Fetch all users to see roles
    $users = $supabase->select('users', '*');
    echo "Total Users: " . count($users) . "\n";
    foreach ($users as $u) {
        $rol = $u['rol'] ?? 'NO_ROLE';
        if (stripos($rol, 'medico') !== false) {
             echo "[MATCH] User: {$u['username']} (ID: {$u['id']}) - Role: '$rol' - Email: {$u['email']}\n";
        } else {
             // echo "[SKIP] User: {$u['username']} - Role: '$rol'\n";
        }
    }
} catch (Throwable $e) {
    echo "Error fetching users: " . $e->getMessage() . "\n";
}

// 2. Fetch Medicos
try {
    $medicos = $supabase->select('medicos', '*');
    echo "\n--- LINKED MEDICOS ---\n";
    foreach ($medicos as $m) {
        $uid = $m['user_id'] ?? 'NULL';
        echo "Medico ID: {$m['id']} - Linked UserID: $uid - Name: {$m['primer_nombre']}\n";
    }
} catch (Throwable $e) {
    echo "Error fetching medicos: " . $e->getMessage() . "\n";
}
