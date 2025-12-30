<?php
require 'vendor/autoload.php';
use App\SupabaseClient;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

$supabase = new SupabaseClient($_ENV['SUPABASE_URL'], $_ENV['SUPABASE_KEY']);

echo "Checking configuration table...\n";
$data = $supabase->select('configuracion', '*', '');
print_r($data);
