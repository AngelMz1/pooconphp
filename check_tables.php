<?php
require_once 'vendor/autoload.php';

use GuzzleHttp\Client;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "<h2>Verificando tablas disponibles en Supabase</h2>";

$client = new Client([
    'base_uri' => $_ENV['SUPABASE_URL'],
    'timeout' => 10,
    'headers' => [
        'apikey' => $_ENV['SUPABASE_KEY'],
        'Authorization' => "Bearer " . $_ENV['SUPABASE_KEY']
    ]
]);

// Intentar acceder al endpoint raíz para ver qué está disponible
try {
    $response = $client->get('/rest/v1/');
    echo "<h3>Respuesta del endpoint raíz:</h3>";
    echo "<pre>" . htmlspecialchars($response->getBody()) . "</pre>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Error en endpoint raíz: " . $e->getMessage() . "</p>";
}

// Probar algunas tablas comunes
$commonTables = ['pacientes', 'users', 'profiles', 'public'];

foreach($commonTables as $table) {
    echo "<h4>Probando tabla: $table</h4>";
    try {
        $response = $client->get("/rest/v1/$table?select=*&limit=1");
        $data = json_decode($response->getBody(), true);
        echo "<p style='color: green;'>✓ Tabla '$table' existe</p>";
        if(!empty($data)) {
            echo "<p>Estructura del primer registro:</p>";
            echo "<pre>" . json_encode($data[0], JSON_PRETTY_PRINT) . "</pre>";
        } else {
            echo "<p>Tabla vacía</p>";
        }
    } catch (Exception $e) {
        if(strpos($e->getMessage(), '404') !== false) {
            echo "<p style='color: orange;'>✗ Tabla '$table' no existe</p>";
        } else {
            echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
        }
    }
}
?>