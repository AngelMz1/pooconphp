<?php
require 'vendor/autoload.php';
use App\SupabaseClient;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

$supabase = new SupabaseClient($_ENV['SUPABASE_URL'], $_ENV['SUPABASE_KEY']);

echo "Checking formulas_medicas columns...\n";
$data = $supabase->select('formulas_medicas', '*', 'limit=1');
if (!empty($data)) {
    print_r(array_keys($data[0]));
} else {
    // try insert and fail to see columns? 
    // Or just look at database_schema.md which might be wrong again.
    // Let's rely on my previous error inspection which said "Could not find 'recomendaciones'".
    echo "No rows. Columns likely: id_formula, id_historia, tipo_formula, vigencia_dias, medicamento_id, dosis, cantidad, frecuencia, via_administracion, duracion, observaciones.\n";
}
