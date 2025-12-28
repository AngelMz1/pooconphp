<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\SupabaseClient;
use Dotenv\Dotenv;

// Cargar variables de entorno
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$supabase = new SupabaseClient($_ENV['SUPABASE_URL'], $_ENV['SUPABASE_KEY']);

echo "Sembrando usuarios...\n";

$users = [
    [
        'username' => 'admin',
        'password_hash' => password_hash('admin', PASSWORD_DEFAULT),
        'nombre_completo' => 'Administrador del Sistema',
        'rol' => 'admin'
    ],
    [
        'username' => 'medico1',
        'password_hash' => password_hash('medico123', PASSWORD_DEFAULT),
        'nombre_completo' => 'Dr. Juan Pérez',
        'rol' => 'medico'
    ],
    [
        'username' => 'cajero1',
        'password_hash' => password_hash('cajero123', PASSWORD_DEFAULT),
        'nombre_completo' => 'Cajero Principal',
        'rol' => 'cajero'
    ]
];

foreach ($users as $user) {
    try {
        // Verificar si existe (simulado, idealmente hacer un select primero o usar upsert si la libreria lo soporta)
        // Como SupabaseClient::insert parece básico, intentaremos insertar y capturar error de duplicado (username unique)
        
        $result = $supabase->insert('users', $user);
        
        if (isset($result['error'])) {
            echo "Error al crear {$user['username']}: " . print_r($result, true) . "\n";
        } else {
            echo "Usuario creado: {$user['username']} ({$user['rol']})\n";
        }
    } catch (Exception $e) {
        // Probablemente error de duplicado
        echo "Info: El usuario {$user['username']} ya podría existir o hubo un error: " . $e->getMessage() . "\n";
    }
}

echo "Proceso finalizado. Recuerda ejecutar primero el script SQL 'create_auth_tables.sql' en Supabase.\n";
