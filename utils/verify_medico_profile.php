<?php
// Verificar la relación entre users y medicos
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
    echo "VERIFICACIÓN DE PERFIL MÉDICO\n";
    echo "========================================\n\n";
    
    // 1. Usuario médico en tabla users
    $userMedico = $postgres->select('users', '*', 'username=eq.medico');
    
    if (count($userMedico) > 0) {
        echo "1. Usuario 'medico' en tabla users:\n";
        echo "   ✅ ID: {$userMedico[0]['id']}\n";
        echo "   ✅ Nombre: {$userMedico[0]['nombre_completo']}\n";
        echo "   ✅ Rol: {$userMedico[0]['rol']}\n";
        echo "   ✅ Activo: " . ($userMedico[0]['active'] ? 'Sí' : 'No') . "\n\n";
        
        $userId = $userMedico[0]['id'];
        
        // 2. Perfil en tabla medicos
        $perfilMedico = $postgres->select('medicos', '*', "user_id=eq.$userId");
        
        if (count($perfilMedico) > 0) {
            echo "2. ✅ Perfil médico encontrado en tabla medicos:\n";
            echo "   ID: {$perfilMedico[0]['id']}\n";
            echo "   Nombre: {$perfilMedico[0]['primer_nombre']} {$perfilMedico[0]['primer_apellido']}\n";
            echo "   Documento: {$perfilMedico[0]['num_documento']}\n";
            echo "   Registro: {$perfilMedico[0]['num_registro']}\n";
            echo "   Especialidad ID: {$perfilMedico[0]['especialidad_id']}\n";
            echo "   User ID: {$perfilMedico[0]['user_id']}\n\n";
            
            // 3. Verificar especialidad
            if ($perfilMedico[0]['especialidad_id']) {
                $especialidad = $postgres->select('especialidades', '*', "id=eq.{$perfilMedico[0]['especialidad_id']}");
                if (count($especialidad) > 0) {
                    echo "3. ✅ Especialidad:\n";
                    echo "   {$especialidad[0]['nombre']}\n\n";
                }
            }
        } else {
            echo "2. ❌ NO hay perfil médico en tabla medicos\n";
            echo "   El usuario existe pero falta el perfil profesional\n\n";
        }
        
        // 4. Verificar consultas asignadas
        $consultas = $postgres->select('consultas', 'COUNT(*) as total', "medico_id=eq.$userId");
        echo "4. Consultas asignadas: {$consultas[0]['total']}\n";
        
    } else {
        echo "❌ Usuario 'medico' NO encontrado en tabla users\n";
    }
    
    echo "\n========================================\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
