<?php
require_once 'vendor/autoload.php';

use App\SupabaseClient;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

try {
    $supabase = new SupabaseClient($_ENV['SUPABASE_URL'], $_ENV['SUPABASE_KEY']);
    
    echo "<h2>📊 Verificación completa de datos</h2>";
    
    // 1. Contar pacientes
    $pacientes = $supabase->select('pacientes', '*');
    echo "<h3>👥 Total de pacientes: " . count($pacientes) . "</h3>";
    
    if (!empty($pacientes)) {
        echo "<h4>Primeros 5 pacientes:</h4>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Documento</th><th>Nombre Completo</th><th>Teléfono</th></tr>";
        
        foreach (array_slice($pacientes, 0, 5) as $p) {
            echo "<tr>";
            echo "<td>" . $p['id_paciente'] . "</td>";
            echo "<td><strong>" . $p['documento_id'] . "</strong></td>";
            echo "<td>" . $p['primer_nombre'] . " " . ($p['segundo_nombre'] ?? '') . " " . $p['primer_apellido'] . " " . ($p['segundo_apellido'] ?? '') . "</td>";
            echo "<td>" . ($p['telefono'] ?? 'N/A') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Buscar específicamente por 1000000246
        echo "<h4>🔍 Búsqueda específica por '1000000246':</h4>";
        $busqueda_exacta = $supabase->select('pacientes', '*', 'documento_id=eq.1000000246');
        echo "Resultados búsqueda exacta: " . count($busqueda_exacta) . "<br>";
        
        // Buscar con LIKE por si hay espacios o caracteres extra
        echo "<h4>🔍 Búsqueda con LIKE:</h4>";
        $busqueda_like = $supabase->select('pacientes', '*', 'documento_id=like.*1000000246*');
        echo "Resultados búsqueda LIKE: " . count($busqueda_like) . "<br>";
        
        // Mostrar todos los documentos que contengan '1000000246'
        foreach ($pacientes as $p) {
            if (strpos($p['documento_id'], '1000000246') !== false) {
                echo "<div style='background: #ffffcc; padding: 10px; margin: 5px;'>";
                echo "✅ Encontrado: ID=" . $p['id_paciente'] . ", Doc='" . $p['documento_id'] . "', Nombre=" . $p['primer_nombre'] . " " . $p['primer_apellido'];
                echo "</div>";
            }
        }
    }
    
    // 2. Verificar historias clínicas
    echo "<h3>📋 Historias clínicas:</h3>";
    $historias = $supabase->select('historias_clinicas', '*');
    echo "Total de historias clínicas: " . count($historias) . "<br>";
    
    if (!empty($historias)) {
        echo "<h4>Primeras 3 historias:</h4>";
        foreach (array_slice($historias, 0, 3) as $h) {
            echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 5px;'>";
            echo "<strong>ID Historia:</strong> " . $h['id_historia'] . "<br>";
            echo "<strong>ID Paciente:</strong> " . $h['id_paciente'] . "<br>";
            echo "<strong>Fecha:</strong> " . $h['fecha_ingreso'] . "<br>";
            echo "</div>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>