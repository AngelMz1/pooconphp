<?php
// Crear perfil médico completo
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
    echo "CREAR PERFIL MÉDICO PROFESIONAL\n";
    echo "========================================\n\n";
    
    // Obtener el user_id del médico
    $userMedico = $postgres->select('users', 'id', 'username=eq.medico');
    
    if (count($userMedico) == 0) {
        echo "❌ Usuario 'medico' no encontrado\n";
        exit(1);
    }
    
    $userId = $userMedico[0]['id'];
    echo "Usuario médico encontrado (ID: $userId)\n\n";
    
    // Verificar si ya existe el perfil
    $existingProfile = $postgres->select('medicos', '*', "user_id=eq.$userId");
    
    if (count($existingProfile) > 0) {
        echo "⚠️  El perfil médico ya existe (ID: {$existingProfile[0]['id']})\n";
        exit(0);
    }
    
    // Verificar/crear especialidad por defecto
    $especialidad = $postgres->select('especialidades', '*', 'nombre=eq.Medicina General');
    
    if (count($especialidad) == 0) {
        echo "Creando especialidad 'Medicina General'...\n";
        $postgres->insert('especialidades', [
            'nombre' => 'Medicina General',
            'descripcion' => 'Especialidad médica general'
        ]);
        $especialidad = $postgres->select('especialidades', '*', 'nombre=eq.Medicina General');
    }
    
    $especialidadId = $especialidad[0]['id'];
    echo "Especialidad 'Medicina General' (ID: $especialidadId)\n\n";
    
    // Crear perfil médico
    echo "Creando perfil médico profesional...\n";
    
    $result = $postgres->insert('medicos', [
        'primer_nombre' => 'Juan',
        'segundo_nombre' => 'Carlos',
        'primer_apellido' => 'Médico',
        'segundo_apellido' => 'Prueba',
        'num_documento' => '1234567890',
        'num_registro' => 'RM-12345',
        'fecha_nacimiento' => '1985-01-15',
        'genero' => 'Masculino',
        'telefono' => '3001234567',
        'email' => 'medico@example.com',
        'direccion' => 'Calle Ejemplo #123',
        'especialidad_id' => $especialidadId,
        'user_id' => $userId
    ]);
    
    echo "✅ Perfil médico creado exitosamente\n\n";
    
    // Verificar el perfil creado
    $perfil = $postgres->select('medicos', '*', "user_id=eq.$userId");
    
    if (count($perfil) > 0) {
        echo "========================================\n";
        echo "PERFIL CREADO\n";
        echo "========================================\n";
        echo "ID: {$perfil[0]['id']}\n";
        echo "Nombre: {$perfil[0]['primer_nombre']} {$perfil[0]['segundo_nombre']} {$perfil[0]['primer_apellido']} {$perfil[0]['segundo_apellido']}\n";
        echo "Documento: {$perfil[0]['num_documento']}\n";
        echo "Registro Médico: {$perfil[0]['num_registro']}\n";
        echo "Email: {$perfil[0]['email']}\n";
        echo "Especialidad ID: {$perfil[0]['especialidad_id']}\n";
        echo "User ID: {$perfil[0]['user_id']}\n";
        echo "========================================\n\n";
        
        echo "✅ El usuario 'medico' ahora puede iniciar sesión con perfil completo\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
