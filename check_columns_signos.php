<?php
require 'vendor/autoload.php';
use App\SupabaseClient;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

$supabase = new SupabaseClient($_ENV['SUPABASE_URL'], $_ENV['SUPABASE_KEY']);

echo "Checking signos_vitales columns...\n";
$data = $supabase->select('signos_vitales', '*', 'limit=1');
if (!empty($data)) {
    print_r(array_keys($data[0]));
} else {
    echo "No rows found in signos_vitales.\n";
}
