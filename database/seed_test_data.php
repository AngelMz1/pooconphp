<?php
require_once __DIR__ . '/../vendor/autoload.php';
use App\DatabaseFactory;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
try { $dotenv->load(); } catch (Exception $e) {}

try {
    $db = DatabaseFactory::create();
    
    echo "--- Seeding Data ---\n";

    // 1. Create User (Medico)
    $username = 'medico1';
    $password = password_hash('medico123', PASSWORD_BCRYPT);
    
    // Check if exists
    $users = $db->select('users', 'id', "username=eq.$username");
    if (empty($users)) {
        $userData = [
            'username' => $username,
            'password_hash' => $password,
            'nombre_completo' => 'Dr. Gregory House',
            'rol' => 'medico',
            'active' => true
        ];
        $res = $db->insert('users', $userData);
        $userId = $res[0]['id'];
        echo "Created User: medico1 (ID: $userId)\n";
    } else {
        $userId = $users[0]['id'];
        echo "User medico1 already exists (ID: $userId)\n";
    }

    // 2. Create Specialty if needed (Assuming 'especialidades' table exists, checking schema...)
    // Actually, looking at previous files, medicos table usually has 'especialidad' string or ID. 
    // Let's check medicos columns via error if needed, but for now assuming string or ID 1.
    // I'll try to insert string 'Cardiologia' if column is text, or ID 1 if int.
    // Safest is to just insert into medicos and see.
    
    // 3. Create Medico
    $medicoCheck = $db->select('medicos', 'id', "user_id=eq.$userId");
    if (empty($medicoCheck)) {
        // Removed documento_id, telefono, email, especialidad as they might not exist or be named differently
        // We stick to the basics used by gestion_citas.php
        $medicoData = [
            'user_id' => $userId,
            'primer_nombre' => 'Gregory',
            'primer_apellido' => 'House',
            // 'documento_id' => 'MED123', // Caused error
            // 'especialidad' => 'Diagnóstico' 
        ];
        
        try {
             $db->insert('medicos', $medicoData);
             echo "Created Medico Profile for Dr. House\n";
        } catch (Exception $e) {
             echo "Insert Medico failed: " . $e->getMessage() . "\n";
        }
    } else {
        echo "Medico profile already exists.\n";
    }

    // 4. Create Patient
    $docId = '12345678';
    $patCheck = $db->select('pacientes', 'id_paciente', "documento_id=eq.$docId");
    if (empty($patCheck)) {
        $patData = [
            'primer_nombre' => 'Juan',
            'primer_apellido' => 'Perez',
            'documento_id' => $docId,
            // 'tipo_documento' => 'CC', // Caused error
            'fecha_nacimiento' => '1990-01-01',
            'telefono' => '3001234567',
            'direccion' => 'Calle Falsa 123',
            'email' => 'juan@example.com'
        ];
        try {
            $db->insert('pacientes', $patData);
            echo "Created Patient: Juan Perez (Doc: 12345678)\n";
        } catch (Exception $e) {
            echo "Insert Patient failed: " . $e->getMessage() . "\n";
        }
    } else {
        echo "Patient Juan Perez already exists.\n";
    }

    echo "Seeding Complete.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
