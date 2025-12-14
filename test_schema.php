<?php
require_once 'vendor/autoload.php';

use App\SupabaseClient;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

try {
    $supabase = new SupabaseClient($_ENV['SUPABASE_URL'], $_ENV['SUPABASE_KEY']);
    
    echo "<h2>Probando estructura de tabla 'pacientes'</h2>";
    
    // Probar diferentes estructuras comunes
    $testStructures = [
        ['nombre' => 'Juan Pérez'],
        ['nombre' => 'Juan Pérez', 'cedula' => '12345678'],
        ['nombre' => 'Juan Pérez', 'cc' => '12345678'],
        ['nombre' => 'Juan Pérez', 'id' => 1],
        ['nombre' => 'Juan Pérez', 'edad' => 30, 'telefono' => '3001234567']
    ];
    
    foreach($testStructures as $index => $testData) {
        echo "<h4>Prueba " . ($index + 1) . ": " . json_encode($testData) . "</h4>";
        
        try {
            $result = $supabase->insert('pacientes', $testData);
            echo "<p style='color: green;'>✓ Inserción exitosa</p>";
            
            // Si funciona, mostrar la estructura
            $pacientes = $supabase->select('pacientes');
            if(!empty($pacientes)) {
                $record = end($pacientes); // Último registro
                echo "<p><strong>Estructura confirmada:</strong></p>";
                echo "<ul>";
                foreach($record as $key => $value) {
                    echo "<li><strong>$key:</strong> " . htmlspecialchars($value ?? 'NULL') . " (" . gettype($value) . ")</li>";
                }
                echo "</ul>";
                break; // Salir del loop si encontramos la estructura correcta
            }
            
        } catch (Exception $e) {
            echo "<p style='color: red'>✗ Error: " . $e->getMessage() . "</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error general: " . $e->getMessage() . "</p>";
}
?>