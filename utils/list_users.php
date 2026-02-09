<?php
// Verificar usuarios existentes en PostgreSQL local
require_once __DIR__ . '/../vendor/autoload.php';

use App\PostgreSQLClient;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

try {
    $postgres = new PostgreSQLClient(
        $_ENV['DB_HOST'],
        $_ENV['DB_DATABASE'],
        $_ENV['DB_USERNAME'],
        $_ENV['DB_PASSWORD'],
        (int)$_ENV['DB_PORT']
    );
    
    echo "========================================\n";
    echo "USUARIOS EN POSTGRESQL LOCAL\n";
    echo "========================================\n\n";
    
    $users = $postgres->select('users', '*', '', 'username.asc');
    
    echo "Total usuarios: " . count($users) . "\n\n";
    
    foreach ($users as $user) {
        echo "Usuario: {$user['username']}\n";
        echo "  - ID: {$user['id']}\n";
        echo "  - Nombre: {$user['nombre_completo']}\n";
        echo "  - Rol: {$user['rol']}\n";
        echo "  - Activo: " . ($user['active'] ? 'Sí' : 'No') . "\n";
        echo "  - Hash: " . substr($user['password_hash'], 0, 20) . "...\n\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
