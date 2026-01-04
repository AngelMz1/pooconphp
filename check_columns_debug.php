<?php
require_once __DIR__ . '/vendor/autoload.php';
use App\SupabaseClient;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();
$supabase = new SupabaseClient($_ENV['SUPABASE_URL'], $_ENV['SUPABASE_KEY']);

echo "--- MEDICOS Columns ---\n";
try {
    $m = $supabase->select('medicos', '*', null, null, 1);
    if (!empty($m)) {
        print_r(array_keys($m[0]));
    } else {
        echo "Table medicos is empty or inaccessible.\n";
    }
} catch (Throwable $e) { echo $e->getMessage(); }

echo "\n--- USERS Columns ---\n";
try {
    $u = $supabase->select('users', '*', null, null, 1);
    if (!empty($u)) {
        print_r(array_keys($u[0]));
    } else {
        echo "Table users is empty or inaccessible.\n";
    }
} catch (Throwable $e) { echo $e->getMessage(); }
