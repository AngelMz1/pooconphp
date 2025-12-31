<?php
require 'vendor/autoload.php';
use App\SupabaseClient;
use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();
$supabase = new SupabaseClient($_ENV['SUPABASE_URL'], $_ENV['SUPABASE_KEY']);
echo 'Checking table: tarifarios' . PHP_EOL;
try {
    $data = $supabase->select('tarifarios', '*', 'limit=1');
    print_r($data);
} catch (Exception $e) { echo 'Error tarifarios: ' . $e->getMessage() . PHP_EOL; }

echo 'Checking table: configuracion' . PHP_EOL;
try {
    $data = $supabase->select('configuracion', '*', 'limit=1');
    print_r($data);
} catch (Exception $e) { echo 'Error configuracion: ' . $e->getMessage() . PHP_EOL; }

