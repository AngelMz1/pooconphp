<?php
require_once '../vendor/autoload.php';

use App\SupabaseClient;
use App\HistoriaClinica;
use App\Paciente;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

echo "<h2>🧪 Prueba de Historias Clínicas</h2>";

try {
    $supabase = new SupabaseClient($_ENV['SUPABASE_URL'], $_ENV['SUPABASE_KEY']);
    $historiaClinica = new HistoriaClinica($supabase);
    $paciente = new Paciente($supabase);
    
    echo "<p style='color: green;'>✅ Clases creadas correctamente</p>";
    
    // Probar obtener pacientes
    echo "<h3>👥 Probando obtener pacientes...</h3>";
    $pacientes = $paciente->obtenerTodos();
    echo "<p>Pacientes encontrados: " . count($pacientes) . "</p>";
    
    if (!empty($pacientes)) {
        echo "<h4>Primer paciente:</h4>";
        echo "<pre>" . json_encode($pacientes[0], JSON_PRETTY_PRINT) . "</pre>";
        
        // Probar crear historia clínica
        echo "<h3>📋 Probando crear historia clínica...</h3>";
        $datosHistoria = [
            'id_paciente' => $pacientes[0]['id_paciente'],
            'motivo_consulta' => 'Consulta de prueba desde PHP',
            'analisis_plan' => 'Plan de prueba para verificar funcionamiento',
            'diagnostico' => 'Diagnóstico de prueba',
            'tratamiento' => 'Tratamiento de prueba',
            'observaciones' => 'Observaciones de prueba - ' . date('Y-m-d H:i:s')
        ];
        
        $resultado = $historiaClinica->crear($datosHistoria);
        echo "<p style='color: green;'>✅ Historia clínica creada exitosamente</p>";
        echo "<pre>" . json_encode($resultado, JSON_PRETTY_PRINT) . "</pre>";
        
        // Probar obtener historias del paciente
        echo "<h3>📖 Probando obtener historias del paciente...</h3>";
        $historias = $historiaClinica->obtenerPorPaciente($pacientes[0]['id_paciente']);
        echo "<p>Historias encontradas: " . count($historias) . "</p>";
        
        if (!empty($historias)) {
            echo "<h4>Última historia:</h4>";
            echo "<pre>" . json_encode($historias[0], JSON_PRETTY_PRINT) . "</pre>";
        }
        
    } else {
        echo "<p style='color: orange;'>⚠️ No hay pacientes en la base de datos</p>";
        echo "<p>Ejecuta el script crear_tablas.sql en Supabase para crear datos de prueba</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>

<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    pre { background: #f4f4f4; padding: 10px; border-radius: 4px; overflow-x: auto; }
</style>