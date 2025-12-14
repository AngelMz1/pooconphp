<?php
require_once 'vendor/autoload.php';

use App\SupabaseClient;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

try {
    $supabase = new SupabaseClient($_ENV['SUPABASE_URL'], $_ENV['SUPABASE_KEY']);
    
    echo "<h2>Insertando datos de prueba...</h2>";
    
    // Insertar un paciente de prueba
    $paciente_data = [
        'documento_id' => '12345678',
        'primer_nombre' => 'Juan',
        'segundo_nombre' => 'Carlos',
        'primer_apellido' => 'Pérez',
        'segundo_apellido' => 'García',
        'fecha_nacimiento' => '1985-03-15',
        'edad' => 39,
        'sexo_id' => 1,
        'direccion' => 'Calle 123 #45-67',
        'telefono' => '3001234567',
        'regimen_id' => 1,
        'estado_civil_id' => 1,
        'ocupacion' => 'Ingeniero',
        'estrato' => 3,
        'barrio_id' => 1,
        'eps_id' => 1,
        'ciudad_id' => 1,
        'gs_rh_id' => 1,
        'lugar_nacimiento' => 1,
        'etnia_id' => 1,
        'escolaridad_id' => 1
    ];
    
    $result = $supabase->insert('pacientes', $paciente_data);
    echo "<p style='color: green;'>✓ Paciente insertado exitosamente</p>";
    
    // Insertar una historia clínica
    $historia_data = [
        'id_paciente' => 1, // Asumiendo que el paciente tiene ID 1
        'fecha_ingreso' => date('Y-m-d H:i:s'),
        'analisis_plan' => 'Paciente presenta síntomas de gripe común. Se recomienda reposo y medicación.'
    ];
    
    $result = $supabase->insert('historias_clinicas', $historia_data);
    echo "<p style='color: green;'>✓ Historia clínica insertada exitosamente</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>