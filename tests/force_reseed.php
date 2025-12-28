<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\SupabaseClient;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();
$supabase = new SupabaseClient($_ENV['SUPABASE_URL'], $_ENV['SUPABASE_KEY']);

echo "<h1>🗑️ Reseteo Total de Usuarios</h1>";

// 1. Definir usuarios y contraseñas limpias
$users = [
    [
        'username' => 'admin',
        'password_hash' => password_hash('admin', PASSWORD_DEFAULT),
        'nombre_completo' => 'Administrador del Sistema',
        'rol' => 'admin',
        'active' => true
    ],
    [
        'username' => 'medico1',
        'password_hash' => password_hash('medico123', PASSWORD_DEFAULT),
        'nombre_completo' => 'Dr. Juan Pérez',
        'rol' => 'medico',
        'active' => true
    ],
    [
        'username' => 'cajero1',
        'password_hash' => password_hash('cajero123', PASSWORD_DEFAULT),
        'nombre_completo' => 'Cajero Principal',
        'rol' => 'cajero',
        'active' => true
    ]
];

foreach ($users as $user) {
    try {
        $username = $user['username'];
        echo "<hr>Procesando <strong>$username</strong>...<br>";

        // A. Verificar si existe
        $existing = $supabase->select('users', 'id', "username=eq.$username");

        if (!empty($existing) && isset($existing[0])) {
            // Existe -> ACTUALIZAR
            echo "ℹ️ El usuario existe. Actualizando contraseña...<br>";
            $result = $supabase->update('users', [
                'password_hash' => $user['password_hash'],
                'nombre_completo' => $user['nombre_completo'],
                'rol' => $user['rol'],
                'active' => true
            ], "username=eq.$username");

            if (isset($result['error'])) {
                 echo "❌ Error actualizando: " . print_r($result, true) . "<br>";
            } else {
                 echo "✅ Actualizado correctamente.<br>";
            }

        } else {
            // No existe -> INSERTAR
            echo "ℹ️ Usuario nuevo. Insertando...<br>";
            $result = $supabase->insert('users', $user);
            
            if (isset($result['error'])) {
                 echo "❌ Error insertando: " . print_r($result, true) . "<br>";
            } else {
                 echo "✅ Creado correctamente.<br>";
            }
        }

    } catch (Exception $e) {
        echo "❌ Excepción con {$user['username']}: " . $e->getMessage() . "<br>";
    }
}

echo "<hr><h3>Proceso Completado. Intenta loguearte ahora.</h3>";
echo "<a href='../views/login.php' style='display:inline-block; padding:10px 20px; background:blue; color:white; text-decoration:none;'>Ir al Login</a>";
