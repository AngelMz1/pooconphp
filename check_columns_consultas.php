<?php
require 'vendor/autoload.php';
use App\SupabaseClient;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$supabase = new SupabaseClient($_ENV['SUPABASE_URL'], $_ENV['SUPABASE_KEY']);

echo "Checking consultas columns...\n";
$data = $supabase->select('consultas', '*', 'limit=1');
if (!empty($data)) {
    print_r(array_keys($data[0]));
} else {
    echo "No rows in consultas to check key.\n";
}
