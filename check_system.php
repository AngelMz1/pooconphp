<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/vendor/autoload.php';
use App\SupabaseClient;
use Dotenv\Dotenv;

echo "<h1>🏥 System Health Check</h1>";

// 1. Files Check
$files = [
    'views/gestion_citas.php',
    'views/gestion_medicos.php',
    'views/gestionar_usuarios.php',
    'views/gestionar_pacientes.php',
    'api/api_availability.php',
    'includes/auth_helper.php',
    'src/SupabaseClient.php'
];

echo "<h2>📂 File Integrity</h2><ul>";
foreach ($files as $f) {
    if (file_exists(__DIR__ . '/' . $f)) {
        echo "<li>✅ Found: $f</li>";
    } else {
        echo "<li>❌ <b>MISSING:</b> $f</li>";
    }
}
echo "</ul>";

// 2. Database Connection
echo "<h2>🗄️ Database Connection</h2>";
try {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();
    $supabase = new SupabaseClient($_ENV['SUPABASE_URL'], $_ENV['SUPABASE_KEY']);
    
    // Test Select
    $users = $supabase->select('users', 'id', null, null, 1);
    echo "<p>✅ Connection Successful. Users table accessible.</p>";
} catch (Throwable $e) {
    echo "<p>❌ <b>Connection Failed:</b> " . $e->getMessage() . "</p>";
    // Don't continue if DB fails
    exit;
}

// 3. Table Checks
$tables = ['medicos', 'pacientes', 'citas', 'especialidades', 'users'];
echo "<h2>📊 Table Check (First Row Probe)</h2><ul>";
foreach ($tables as $t) {
    try {
        $res = $supabase->select($t, '*', null, null, 1);
        echo "<li>✅ Table <b>$t</b>: Accessible (Count: " . (is_array($res) ? 'OK' : 'Error') . ")</li>";
    } catch (Throwable $e) {
        echo "<li>❌ Table <b>$t</b>: Error (" . $e->getMessage() . ")</li>";
    }
}
echo "</ul>";

echo "<p>Detailed checks complete.</p>";
