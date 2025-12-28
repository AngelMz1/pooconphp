<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\SupabaseClient;
use Dotenv\Dotenv;

echo "<h1>Diagnóstico de Usuarios</h1>";

try {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();

    $supabase = new SupabaseClient($_ENV['SUPABASE_URL'], $_ENV['SUPABASE_KEY']);

    // 1. Intentar leer usuarios
    echo "<h2>1. Consultando tabla 'users'</h2>";
    $users = $supabase->select('users', '*');

    if (empty($users)) {
        echo "<p style='color:red'>❌ No se encontraron usuarios. O la tabla está vacía, o RLS está bloqueando la lectura.</p>";
        echo "<p>Intenta ejecutar en SQL Supabase: <code>ALTER TABLE users DISABLE ROW LEVEL SECURITY;</code></p>";
    } else {
        echo "<p style='color:green'>✅ Se encontraron " . count($users) . " usuarios:</p>";
        echo "<ul>";
        foreach ($users as $u) {
            echo "<li><strong>" . htmlspecialchars($u['username']) . "</strong> - Rol: " . htmlspecialchars($u['rol']) . " (Hash: " . substr($u['password_hash'], 0, 10) . "...)</li>";
        }
        echo "</ul>";
    }

} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error Fatal: " . $e->getMessage() . "</p>";
}
echo "<p><a href='../views/login.php'>Ir al Login</a></p>";
