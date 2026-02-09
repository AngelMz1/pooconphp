<?php
// Script para regenerar hashes de contraseñas en PostgreSQL local
// Ejecutar: php update_passwords_local.php

require_once __DIR__ . '/../vendor/autoload.php';

use App\PostgreSQLClient;

// Cargar entorno
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

try {
    // Conectar a PostgreSQL Local
    $postgres = new PostgreSQLClient(
        $_ENV['DB_HOST'],
        $_ENV['DB_DATABASE'],
        $_ENV['DB_USERNAME'],
        $_ENV['DB_PASSWORD'],
        (int)$_ENV['DB_PORT']
    );
    
    echo "Actualizando contraseñas en PostgreSQL local...\n\n";
    
    // Generar nuevo hash para admin123
    $password = 'admin123';
    $hash = password_hash($password, PASSWORD_BCRYPT);
    
    echo "Nueva contraseña: $password\n";
    echo "Hash generado: $hash\n\n";
    
    // Actualizar usuarios
    $users = ['admin', 'medico', 'cajero'];
    
    foreach ($users as $user) {
        echo "Actualizando usuario: $user\n";
        $result = $postgres->update(
            'users',
            ['password_hash' => $hash],
            "username=eq.$user"
        );
        echo "  ✅ Actualizado\n";
    }
    
    echo "\n✅ Todas las contraseñas actualizadas a: $password\n";
    echo "Ahora puedes hacer login con cualquier usuario usando: admin123\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
