<?php
// Script completo para configurar usuarios médico y facturador
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
    echo "CONFIGURACIÓN DE USUARIOS Y PERMISOS\n";
    echo "========================================\n\n";
    
    // 1. USUARIO MÉDICO
    echo "1. CONFIGURANDO USUARIO MÉDICO\n";
    echo "-----------------------------------\n";
    
    $userMedico = $postgres->select('users', '*', 'username=eq.medico');
    
    if (count($userMedico) > 0) {
        $medicoUserId = $userMedico[0]['id'];
        echo "✅ Usuario 'medico' encontrado (ID: $medicoUserId)\n";
        
        // Verificar si existe perfil en tabla medicos
        $perfilMedico = $postgres->select('medicos', '*', "user_id=eq.$medicoUserId");
        
        if (count($perfilMedico) == 0) {
            echo "Creando perfil médico...\n";
            $postgres->insert('medicos', [
                'user_id' => $medicoUserId,
                'especialidad' => 'Medicina General',
                'registro_medico' => 'RM-12345',
                'telefono' => '3001234567',
                'email' => 'medico@example.com'
            ]);
            echo "✅ Perfil médico creado\n";
        } else {
            echo "✅ Perfil médico ya existe (ID: {$perfilMedico[0]['id']})\n";
        }
    } else {
        echo "❌ Usuario 'medico' no encontrado\n";
    }
    
    echo "\n";
    
    // 2. USUARIO FACTURADOR
    echo "2. CONFIGURANDO USUARIO FACTURADOR\n";
    echo "-----------------------------------\n";
    
    $userFacturador = $postgres->select('users', '*', 'username=eq.facturador');
    
    if (count($userFacturador) > 0) {
        echo "✅ Usuario 'facturador' encontrado (ID: {$userFacturador[0]['id']})\n";
        echo "   Rol: {$userFacturador[0]['rol']}\n";
    } else {
        echo "⚠️  Usuario 'facturador' no encontrado, ya debería estar creado\n";
    }
    
    echo "\n";
    
    // 3. VERIFICAR PERMISOS
    echo "3. VERIFICACIÓN DE PERMISOS\n";
    echo "-----------------------------------\n";
    
    // Verificar tabla de permisos
    $permisos = $postgres->select('permisos', '*', '', 'codigo.asc', 10);
    
    if (count($permisos) > 0) {
        echo "Permisos disponibles en el sistema:\n";
        foreach ($permisos as $permiso) {
            echo "  - {$permiso['codigo']}: {$permiso['nombre']}\n";
        }
    } else {
        echo "⚠️  No hay permisos configurados en la tabla 'permisos'\n";
        echo "   El sistema puede usar permisos basados en ROL directamente\n";
    }
    
    echo "\n";
    
    // 4. RESUMEN FINAL
    echo "========================================\n";
    echo "RESUMEN DE CONFIGURACIÓN\n";
    echo "========================================\n\n";
    
    $allUsers = $postgres->select('users', '*', '', 'username.asc');
    
    foreach ($allUsers as $user) {
        echo "Usuario: {$user['username']}\n";
        echo "  - Contraseña: admin123\n";
        echo "  - Rol: {$user['rol']}\n";
        echo "  - Estado: " . ($user['active'] ? 'Activo' : 'Inactivo') . "\n";
        
        if ($user['rol'] === 'medico') {
            $perfil = $postgres->select('medicos', '*', "user_id=eq.{$user['id']}");
            if (count($perfil) > 0) {
                echo "  - Perfil médico: ✅ Completo\n";
                echo "  - Especialidad: {$perfil[0]['especialidad']}\n";
            } else {
                echo "  - Perfil médico: ❌ Falta\n";
            }
        }
        
        echo "\n";
    }
    
    echo "========================================\n";
    echo "✅ Configuración completada\n";
    echo "========================================\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
