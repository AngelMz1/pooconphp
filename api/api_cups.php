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

    // Sanitizar término para evitar inyección en PostgREST (aunque usamos prepare statement interno, el filtro es raw string)
    // Caracteres especiales de PostgREST: . : ( ) ,
    $termino = str_replace(['.', ':', '(', ')', ','], '', $_GET['q']);
    
    // Validar longitud post-sanitización
    if (strlen($termino) < 2) {
        echo json_encode([]);
        exit;
    }

    $supabase = new SupabaseClient($_ENV['SUPABASE_URL'], $_ENV['SUPABASE_KEY']);

    // Buscar en la tabla 'tarifarios' que contiene los servicios/procedimientos
    // Filtro ilike para búsqueda insensible a mayúsculas
    // Buscamos por código O descripción (nombre_servicio)
    $filter = "or=(codigo.ilike.*$termino*,nombre_servicio.ilike.*$termino*)";
    
    // Limitamos a 20 resultados
    $resultados = $supabase->select('tarifarios', 'id, codigo, nombre_servicio', $filter, 'nombre_servicio.asc', 20);

    // Mapeamos para mantener consistencia con frontend (codigo, descripcion)
    $output = array_map(function($item) {
        return [
            'id' => $item['id'],
            'codigo' => $item['codigo'],
            'descripcion' => $item['nombre_servicio']
        ];
    }, $resultados);

    echo json_encode($output);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
