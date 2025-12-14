<?php
require_once 'vendor/autoload.php';

use App\SupabaseClient;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

try {
    $supabase = new SupabaseClient($_ENV['SUPABASE_URL'], $_ENV['SUPABASE_KEY']);
    
    echo "<h2>🔒 Verificando RLS y permisos</h2>";
    
    // Intentar diferentes métodos de consulta
    echo "<h3>1. Consulta básica a pacientes:</h3>";
    try {
        $pacientes = $supabase->select('pacientes', '*');
        echo "<p style='color: green;'>✅ Consulta exitosa. Registros encontrados: " . count($pacientes) . "</p>";
        
        if (!empty($pacientes)) {
            echo "<h4>Primeros registros:</h4>";
            foreach (array_slice($pacientes, 0, 3) as $p) {
                echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 5px;'>";
                echo "ID: " . ($p['id_paciente'] ?? 'N/A') . " | ";
                echo "Doc: " . ($p['documento_id'] ?? 'N/A') . " | ";
                echo "Nombre: " . ($p['primer_nombre'] ?? 'N/A') . " " . ($p['primer_apellido'] ?? 'N/A');
                echo "</div>";
            }
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error en consulta básica: " . $e->getMessage() . "</p>";
    }
    
    echo "<h3>2. Búsqueda específica por documento 1000000246:</h3>";
    try {
        $paciente_especifico = $supabase->select('pacientes', '*', 'documento_id=eq.1000000246');
        echo "<p style='color: green;'>✅ Búsqueda específica exitosa. Registros: " . count($paciente_especifico) . "</p>";
        
        if (!empty($paciente_especifico)) {
            $p = $paciente_especifico[0];
            echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px;'>";
            echo "<h4>📋 Paciente encontrado:</h4>";
            echo "<strong>ID:</strong> " . ($p['id_paciente'] ?? 'N/A') . "<br>";
            echo "<strong>Documento:</strong> " . ($p['documento_id'] ?? 'N/A') . "<br>";
            echo "<strong>Nombre:</strong> " . ($p['primer_nombre'] ?? 'N/A') . " " . ($p['primer_apellido'] ?? 'N/A') . "<br>";
            echo "<strong>Fecha Nac:</strong> " . ($p['fecha_nacimiento'] ?? 'N/A') . "<br>";
            echo "</div>";
            
            // Buscar historias clínicas
            $id_paciente = $p['id_paciente'];
            echo "<h4>🏥 Buscando historias clínicas para ID: $id_paciente</h4>";
            
            try {
                $historias = $supabase->select('historias_clinicas', '*', "id_paciente=eq.$id_paciente");
                echo "<p style='color: green;'>✅ Historias encontradas: " . count($historias) . "</p>";
                
                foreach ($historias as $h) {
                    echo "<div style='background: #f0f8ff; padding: 10px; margin: 5px; border-left: 4px solid #007bff;'>";
                    echo "<strong>ID Historia:</strong> " . ($h['id_historia'] ?? 'N/A') . "<br>";
                    echo "<strong>Fecha Ingreso:</strong> " . ($h['fecha_ingreso'] ?? 'N/A') . "<br>";
                    echo "<strong>Fecha Egreso:</strong> " . ($h['fecha_egreso'] ?? 'Abierta') . "<br>";
                    echo "<strong>Plan:</strong> " . substr($h['analisis_plan'] ?? 'N/A', 0, 100) . "...<br>";
                    echo "</div>";
                }
            } catch (Exception $e) {
                echo "<p style='color: red;'>❌ Error al buscar historias: " . $e->getMessage() . "</p>";
            }
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error en búsqueda específica: " . $e->getMessage() . "</p>";
    }
    
    echo "<h3>3. Información de la API Key:</h3>";
    echo "<p>Usando API Key: " . substr($_ENV['SUPABASE_KEY'], 0, 20) . "...</p>";
    echo "<p>URL: " . $_ENV['SUPABASE_URL'] . "</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error general: " . $e->getMessage() . "</p>";
}
?>