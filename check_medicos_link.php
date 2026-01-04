<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/includes/auth_helper.php';

use App\SupabaseClient;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();
$supabase = new SupabaseClient($_ENV['SUPABASE_URL'], $_ENV['SUPABASE_KEY']);

echo "<h1>Diagnóstico de Médicos</h1>";

// 1. Obtener Usuarios con rol 'medico'
try {
    $medicoUsers = $supabase->select('users', '*', "rol=eq.medico");
    echo "<h2>Usuarios con rol 'medico' (" . count($medicoUsers) . ")</h2>";
    echo "<table border='1'><tr><th>ID</th><th>Nombre</th><th>Email</th></tr>";
    $userIds = [];
    foreach ($medicoUsers as $u) {
        $userIds[] = $u['id'];
        echo "<tr><td>{$u['id']}</td><td>{$u['nombre_completo']}</td><td>{$u['email']}</td></tr>";
    }
    echo "</table>";
} catch (Throwable $e) {
    echo "Error fetching users: " . $e->getMessage();
}

// 2. Obtener Entradas en tabla 'medicos'
try {
    $medicosProfile = $supabase->select('medicos', '*');
    echo "<h2>Perfiles en tabla 'medicos' (" . count($medicosProfile) . ")</h2>";
    echo "<table border='1'><tr><th>ID</th><th>User ID</th><th>Nombre</th><th>Especialidad ID</th></tr>";
    foreach ($medicosProfile as $m) {
        $uid = $m['user_id'] ?? 'NULL';
        $style = in_array($uid, $userIds) ? "background-color: lightgreen;" : "background-color: lightcoral;";
        echo "<tr style='$style'><td>{$m['id']}</td><td>$uid</td><td>{$m['primer_nombre']} {$m['primer_apellido']}</td><td>{$m['especialidad_id']}</td></tr>";
    }
    echo "</table>";
} catch (Throwable $e) {
    echo "Error fetching medicos: " . $e->getMessage();
}

echo "<h3>Leyenda</h3>";
echo "<ul><li><span style='background-color: lightgreen;'>Verde</span>: Vinculado correctamente (User ID coincide con un usuario médico).</li>";
echo "<li><span style='background-color: lightcoral;'>Rojo</span>: User ID no encontrado en la lista de usuarios o vacío.</li></ul>";
echo "<p>Si ves usuarios en la primera tabla que NO tienen una fila verde en la segunda, necesitas crearles el perfil de médico.</p>";
