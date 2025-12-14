<?php
require_once 'vendor/autoload.php';

use App\SupabaseClient;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

try {
    $supabase = new SupabaseClient($_ENV['SUPABASE_URL'], $_ENV['SUPABASE_KEY']);
    
    echo "<h2>➕ Insertando paciente de prueba</h2>";
    
    // Insertar paciente con cédula 1000000246
    $paciente_data = [
        'documento_id' => '1000000246',
        'primer_nombre' => 'María',
        'segundo_nombre' => 'Elena', 
        'primer_apellido' => 'González',
        'segundo_apellido' => 'López',
        'fecha_nacimiento' => '1990-05-15',
        'sexo_id' => 1,
        'direccion' => 'Carrera 15 #32-45',
        'telefono' => '3201234567',
        'regimen_id' => 1,
        'estado_civil_id' => 1,
        'ocupacion' => 'Profesora',
        'estrato' => 3,
        'barrio_id' => 1,
        'eps_id' => 1,
        'ciudad_id' => 1,
        'gs_rh_id' => 1,
        'lugar_nacimiento' => 1,
        'etnia_id' => 1,
        'escolaridad_id' => 1
        // Nota: La edad se calcula automáticamente desde fecha_nacimiento
    ];
    
    $result_paciente = $supabase->insert('pacientes', $paciente_data);
    echo "<p style='color: green;'>✅ Paciente insertado exitosamente</p>";
    
    // Obtener el ID del paciente recién insertado
    $pacientes_insertados = $supabase->select('pacientes', '*', 'documento_id=eq.1000000246');
    
    if (!empty($pacientes_insertados)) {
        $id_paciente = $pacientes_insertados[0]['id_paciente'];
        echo "<p>ID del paciente: $id_paciente</p>";
        
        // Insertar historia clínica
        $historia_data = [
            'id_paciente' => $id_paciente,
            'fecha_ingreso' => date('Y-m-d H:i:s'),
            'analisis_plan' => 'Paciente ingresa por control rutinario. Presenta buen estado general. Se recomienda seguimiento.'
        ];
        
        $result_historia = $supabase->insert('historias_clinicas', $historia_data);
        echo "<p style='color: green;'>✅ Historia clínica insertada exitosamente</p>";
        
        // Insertar segunda historia clínica
        $historia_data2 = [
            'id_paciente' => $id_paciente,
            'fecha_ingreso' => date('Y-m-d H:i:s', strtotime('-1 month')),
            'fecha_egreso' => date('Y-m-d'),
            'analisis_plan' => 'Consulta por síntomas gripales. Tratamiento sintomático. Evolución satisfactoria.'
        ];
        
        $result_historia2 = $supabase->insert('historias_clinicas', $historia_data2);
        echo "<p style='color: green;'>✅ Segunda historia clínica insertada exitosamente</p>";
        
        echo "<h3>🎉 Datos insertados correctamente</h3>";
        echo "<p>Ahora puedes buscar el paciente con cédula: <strong>1000000246</strong></p>";
        
    } else {
        echo "<p style='color: red;'>❌ Error: No se pudo obtener el ID del paciente insertado</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>Esto puede ser normal si el paciente ya existe o si hay restricciones en la base de datos.</p>";
}
?>