<?php
require_once '../vendor/autoload.php';

use App\SupabaseClient;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

echo "<h2>📊 Verificación de Tablas</h2>";

try {
    $supabase = new SupabaseClient($_ENV['SUPABASE_URL'], $_ENV['SUPABASE_KEY']);
    
    echo "<h3>🔍 Verificando tabla 'pacientes'...</h3>";
    try {
        $pacientes = $supabase->select('pacientes', '*', '', 'id_paciente.asc');
        echo "<p style='color: green;'>✅ Tabla 'pacientes' existe y es accesible</p>";
        echo "<p><strong>Registros encontrados:</strong> " . count($pacientes) . "</p>";
        
        if (!empty($pacientes)) {
            echo "<h4>Primeros 3 pacientes:</h4>";
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>ID</th><th>Documento</th><th>Nombre</th><th>Apellido</th><th>Teléfono</th></tr>";
            foreach (array_slice($pacientes, 0, 3) as $p) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($p['id_paciente']) . "</td>";
                echo "<td>" . htmlspecialchars($p['documento_id']) . "</td>";
                echo "<td>" . htmlspecialchars($p['primer_nombre']) . "</td>";
                echo "<td>" . htmlspecialchars($p['primer_apellido']) . "</td>";
                echo "<td>" . htmlspecialchars($p['telefono'] ?? 'N/A') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
    } catch (Exception $e) {
        if (strpos($e->getMessage(), '404') !== false || strpos($e->getMessage(), 'relation') !== false) {
            echo "<p style='color: red;'>❌ Tabla 'pacientes' no existe</p>";
            echo "<p>Ejecuta el script crear_tablas.sql en Supabase</p>";
        } else {
            echo "<p style='color: red;'>❌ Error al acceder a 'pacientes': " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<h3>🔍 Verificando tabla 'historias_clinicas'...</h3>";
    try {
        $historias = $supabase->select('historias_clinicas', '*', '', 'id_historia.desc');
        echo "<p style='color: green;'>✅ Tabla 'historias_clinicas' existe y es accesible</p>";
        echo "<p><strong>Registros encontrados:</strong> " . count($historias) . "</p>";
        
        if (!empty($historias)) {
            echo "<h4>Últimas 3 historias:</h4>";
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>ID Historia</th><th>ID Paciente</th><th>Fecha Ingreso</th><th>Motivo</th></tr>";
            foreach (array_slice($historias, 0, 3) as $h) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($h['id_historia']) . "</td>";
                echo "<td>" . htmlspecialchars($h['id_paciente']) . "</td>";
                echo "<td>" . htmlspecialchars(substr($h['fecha_ingreso'], 0, 19)) . "</td>";
                echo "<td>" . htmlspecialchars(substr($h['motivo_consulta'] ?? '', 0, 50)) . "...</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
    } catch (Exception $e) {
        if (strpos($e->getMessage(), '404') !== false || strpos($e->getMessage(), 'relation') !== false) {
            echo "<p style='color: red;'>❌ Tabla 'historias_clinicas' no existe</p>";
            echo "<p>Ejecuta el script crear_tablas.sql en Supabase</p>";
        } else {
            echo "<p style='color: red;'>❌ Error al acceder a 'historias_clinicas': " . $e->getMessage() . "</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error de conexión: " . $e->getMessage() . "</p>";
}
?>

<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    table { margin: 10px 0; }
    th, td { padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
</style>

<p><a href="../index.php" style="text-decoration: none; background: #007cba; color: white; padding: 10px 15px; border-radius: 3px;">🏠 Volver al Inicio</a></p>