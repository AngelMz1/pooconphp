<?php
require_once '../vendor/autoload.php';

use App\SupabaseClient;
use Dotenv\Dotenv;

// Configuración básica
header('Content-Type: application/json');

try {
    // Cargar entorno
    $dotenv = Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();

    // Validar petición
    if (!isset($_GET['q']) || strlen($_GET['q']) < 2) {
        echo json_encode([]);
        exit;
    }

    $termino = $_GET['q'];
    $supabase = new SupabaseClient($_ENV['SUPABASE_URL'], $_ENV['SUPABASE_KEY']);

    // Buscar en la tabla cie10
    // Usamos el filtro ilike para búsqueda insensible a mayúsculas
    // Buscamos por código O descripción
    $filter = "or=(codigo.ilike.*$termino*,descripcion.ilike.*$termino*)";
    
    // Limitamos a 20 resultados para rapidez
    $resultados = $supabase->select('cie10', '*', $filter, 'codigo.asc', 20);

    echo json_encode($resultados);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
