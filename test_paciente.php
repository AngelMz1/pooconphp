<?php
require_once 'vendor/autoload.php';

use App\SupabaseClient;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

try {
    $supabase = new SupabaseClient($_ENV['SUPABASE_URL'], $_ENV['SUPABASE_KEY']);
    
    echo "<h2>🔍 Verificando paciente con cédula 1000000246</h2>";
    
    // Buscar el paciente
    $pacientes = $supabase->select('pacientes', '*', 'documento_id=eq.1000000246');
    
    if (!empty($pacientes)) {
        $paciente = $pacientes[0];
        echo "<h3>✅ Paciente encontrado:</h3>";
        echo "<ul>";
        echo "<li><strong>ID:</strong> " . $paciente['id_paciente'] . "</li>";
        echo "<li><strong>Nombre:</strong> " . $paciente['primer_nombre'] . " " . $paciente['primer_apellido'] . "</li>";
        echo "<li><strong>Documento:</strong> " . $paciente['documento_id'] . "</li>";
        echo "</ul>";
        
        // Buscar historias clínicas
        $id_paciente = $paciente['id_paciente'];
        echo "<h3>📋 Buscando historias clínicas para ID paciente: $id_paciente</h3>";
        
        $historias = $supabase->select('historias_clinicas', '*', "id_paciente=eq.$id_paciente");
        
        if (!empty($historias)) {
            echo "<h4>✅ Historias clínicas encontradas (" . count($historias) . "):</h4>";
            foreach ($historias as $historia) {
                echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0;'>";
                echo "<strong>ID Historia:</strong> " . $historia['id_historia'] . "<br>";
                echo "<strong>Fecha Ingreso:</strong> " . $historia['fecha_ingreso'] . "<br>";
                echo "<strong>Fecha Egreso:</strong> " . ($historia['fecha_egreso'] ?? 'N/A') . "<br>";
                echo "<strong>Análisis:</strong> " . substr($historia['analisis_plan'] ?? 'N/A', 0, 100) . "...<br>";
                echo "</div>";
            }
        } else {
            echo "<p style='color: orange;'>⚠️ No se encontraron historias clínicas para este paciente</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ No se encontró el paciente con cédula 1000000246</p>";
        
        // Mostrar algunos pacientes existentes
        echo "<h3>📋 Pacientes existentes en la base de datos:</h3>";
        $todos_pacientes = $supabase->select('pacientes', 'id_paciente,documento_id,primer_nombre,primer_apellido', '', 'id_paciente.asc');
        
        if (!empty($todos_pacientes)) {
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>ID</th><th>Documento</th><th>Nombre</th></tr>";
            foreach (array_slice($todos_pacientes, 0, 10) as $p) {
                echo "<tr>";
                echo "<td>" . $p['id_paciente'] . "</td>";
                echo "<td>" . $p['documento_id'] . "</td>";
                echo "<td>" . $p['primer_nombre'] . " " . $p['primer_apellido'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>