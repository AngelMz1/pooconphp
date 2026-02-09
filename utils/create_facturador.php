<?php
// Crear usuario facturador en PostgreSQL local
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
    echo "CREAR USUARIO FACTURADOR\n";
    echo "========================================\n\n";
    
    // Generar hash para admin123
    $password = 'admin123';
    $hash = password_hash($password, PASSWORD_BCRYPT);
    
    // Verificar si ya existe
    $existing = $postgres->select('users', '*', 'username=eq.facturador');
    
    if (count($existing) > 0) {
        echo "⚠️  Usuario 'facturador' ya existe (ID: {$existing[0]['id']})\n";
        echo "Actualizando contraseña...\n";
        
        $postgres->update(
            'users',
            ['password_hash' => $hash],
            "id=eq.{$existing[0]['id']}"
        );
        
        echo "✅ Contraseña actualizada\n";
    } else {
        echo "Creando nuevo usuario 'facturador'...\n";
        
        $result = $postgres->insert('users', [
            'username' => 'facturador',
            'password_hash' => $hash,
            'nombre_completo' => 'Facturador de Prueba',
            'rol' => 'cajero', // Rol cajero para facturador
            'active' => true
        ]);
        
        echo "✅ Usuario 'facturador' creado exitosamente\n";
    }
    
    echo "\n========================================\n";
    echo "CREDENCIALES\n";
    echo "========================================\n";
    echo "Usuario: facturador\n";
    echo "Contraseña: admin123\n";
    echo "Rol: cajero\n";
    echo "========================================\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
