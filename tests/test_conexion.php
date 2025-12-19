<?php
require_once 'vendor/autoload.php';

use App\SupabaseClient;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "<h2>🔧 Prueba de Conexión a Supabase</h2>";

try {
    $supabase = new SupabaseClient($_ENV['SUPABASE_URL'], $_ENV['SUPABASE_KEY']);
    
    echo "<p style='color: green;'>✅ Cliente Supabase creado correctamente</p>";
    echo "<p><strong>URL:</strong> " . $_ENV['SUPABASE_URL'] . "</p>";
    
    // Probar conexión básica
    echo "<h3>🔍 Probando conexión...</h3>";
    
    // Intentar obtener información de una tabla (aunque no exista)
    try {
        $result = $supabase->select('pacientes', '*', '', 'id_paciente.asc');
        echo "<p style='color: green;'>✅ Conexión exitosa - Tabla 'pacientes' accesible</p>";
        echo "<p>Registros encontrados: " . count($result) . "</p>";
        
        if (!empty($result)) {
            echo "<h4>Primer registro:</h4>";
            echo "<pre>" . json_encode($result[0], JSON_PRETTY_PRINT) . "</pre>";
        }
        
    } catch (Exception $e) {
        if (strpos($e->getMessage(), '404') !== false || strpos($e->getMessage(), 'relation') !== false) {
            echo "<p style='color: orange;'>⚠️ Tabla 'pacientes' no existe aún</p>";
        } else {
            echo "<p style='color: red;'>❌ Error de conexión: " . $e->getMessage() . "</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error al crear cliente: " . $e->getMessage() . "</p>";
}
?>