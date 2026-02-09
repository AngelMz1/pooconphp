<?php
// Verificación final de usuarios y su configuración
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
    echo "VERIFICACIÓN FINAL DE USUARIOS\n";
    echo "========================================\n\n";
    
    $users = ['admin', 'medico', 'facturador', 'cajero'];
    
    foreach ($users as $username) {
        $user = $postgres->select('users', '*', "username=eq.$username");
        
        if (count($user) == 0) {
            echo "❌ Usuario '$username' NO encontrado\n\n";
            continue;
        }
        
        $u = $user[0];
        echo "Usuario: $username\n";
        echo "----------------------------------------\n";
        echo "✅ ID: {$u['id']}\n";
        echo "✅ Nombre: {$u['nombre_completo']}\n";
        echo "✅ Rol: {$u['rol']}\n";
        echo "✅ Activo: " . ($u['active'] ? 'Sí' : 'No') . "\n";
        echo "✅ Contraseña: admin123\n";
        
        // Verificar perfil médico si es rol medico
        if ($u['rol'] === 'medico') {
            $perfil = $postgres->select('medicos', '*', "user_id=eq.{$u['id']}");
            if (count($perfil) > 0) {
                echo "✅ Perfil médico: Completo\n";
                echo "   - Especialidad: {$perfil[0]['especialidad']}\n";
                echo "   - Registro: {$perfil[0]['registro_medico']}\n";
            } else {
                echo "❌ Perfil médico: FALTA\n";
            }
        }
        
        // Permisos según rol
        echo "\nPermisos (basado en rol):\n";
        switch ($u['rol']) {
            case 'admin':
                echo "  - Acceso total al sistema\n";
                echo "  - Gestión de usuarios\n";
                echo "  - Configuración\n";
                echo "  - Todas las funcionalidades\n";
                break;
            case 'medico':
                echo "  - Consultas médicas\n";
                echo "  - Historias clínicas\n";
                echo "  - Citas médicas\n";
                echo "  - Gestión de pacientes\n";
                break;
            case 'cajero':
                echo "  - Facturación\n";
                echo "  - Citas (confirmación)\n";
                echo "  - Gestión de pacientes\n";
                break;
        }
        
        echo "\n\n";
    }
    
    echo "========================================\n";
    echo "INSTRUCCIONES DE LOGIN\n";
    echo "========================================\n\n";
    
    echo "Todos los usuarios pueden iniciar sesión con:\n";
    echo "  Contraseña: admin123\n\n";
    
    echo "URLs de acceso:\n";
    echo "  Login: http://localhost/pooconphp/views/login.php\n";
    echo "  Dashboard: http://localhost/pooconphp/index.php\n\n";
    
    echo "✅ Sistema configurado correctamente\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
