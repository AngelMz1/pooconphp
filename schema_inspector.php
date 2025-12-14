<?php
require_once 'vendor/autoload.php';

use App\SupabaseClient;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

try {
    $supabase = new SupabaseClient($_ENV['SUPABASE_URL'], $_ENV['SUPABASE_KEY']);
    
    echo "<h2>Esquema de Base de Datos - Supabase</h2>";
    
    // Intentar obtener datos de la tabla pacientes para inferir estructura
    echo "<h3>Analizando tabla 'pacientes':</h3>";
    $pacientes = $supabase->select('pacientes');
    
    if(!empty($pacientes)) {
        echo "<p><strong>Registros encontrados:</strong> " . count($pacientes) . "</p>";
        echo "<h4>Estructura inferida del primer registro:</h4>";
        $firstRecord = $pacientes[0];
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Campo</th><th>Valor de ejemplo</th><th>Tipo inferido</th></tr>";
        foreach($firstRecord as $key => $value) {
            $type = gettype($value);
            echo "<tr>";
            echo "<td><strong>$key</strong></td>";
            echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
            echo "<td>$type</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>La tabla 'pacientes' está vacía. Intentando crear un registro de prueba...</p>";
        
        // Intentar insertar un registro de prueba
        try {
            $testData = [
                'nombre' => 'Juan Pérez',
                'documento' => '12345678',
                'edad' => 30
            ];
            $result = $supabase->insert('pacientes', $testData);
            echo "<p style='color: green;'>✓ Registro de prueba insertado exitosamente</p>";
            
            // Volver a consultar
            $pacientes = $supabase->select('pacientes');
            if(!empty($pacientes)) {
                $firstRecord = $pacientes[0];
                echo "<h4>Estructura de la tabla:</h4>";
                echo "<table border='1' style='border-collapse: collapse;'>";
                echo "<tr><th>Campo</th><th>Valor</th><th>Tipo</th></tr>";
                foreach($firstRecord as $key => $value) {
                    echo "<tr><td><strong>$key</strong></td><td>" . htmlspecialchars($value ?? 'NULL') . "</td><td>" . gettype($value) . "</td></tr>";
                }
                echo "</table>";
            }
        } catch (Exception $insertError) {
            echo "<p style='color: orange;'>No se pudo insertar registro de prueba: " . $insertError->getMessage() . "</p>";
        }
    }

    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>