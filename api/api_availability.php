<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/auth_helper.php';

// Validar sesión básica (cualquier rol autenticado puede consultar disponibilidad)
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

use App\DatabaseFactory;
use App\SupabaseClient;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
try {
    $dotenv->safeLoad();
} catch (Exception $e) { }

$supabase = DatabaseFactory::create();

if (!isset($_GET['medico_id']) || !isset($_GET['fecha'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing parameters']);
    exit;
}

$medico_id = $_GET['medico_id'];
$fecha = $_GET['fecha']; // YYYY-MM-DD

// Consultar citas del día para ese médico
// Filtro: fecha_hora >= fecha 00:00 y <= fecha 23:59
// Y estado != cancelada
// Supabase REST no tiene "starts_with" fácil para datetime, usaremos rango
$start = $fecha . 'T00:00:00';
$end = $fecha . 'T23:59:59';

try {
    // Nota: El filtro de user_id en medicos ya se resolvió en el frontend, aquí llega el ID correcto que guardamos en citas.
    // OJO: En la tabla 'citas', 'medico_id' almacena el user_id del médico o el id de tabla medicos? 
    // Revisando `gestion_citas.php` anterior: "Obtenemos 'user_id' de medicos para la FK correcta en citas".
    // Así que $medico_id aquí debe ser el user_id.
    
    $citas = $supabase->select('citas', 'fecha_hora', 
        "medico_id=eq.$medico_id&fecha_hora=gte.$start&fecha_hora=lte.$end&estado=neq.cancelada"
    );

    $occupied = [];
    if (!empty($citas)) {
        foreach ($citas as $c) {
            // Extraer HH:MM
            $dt = new DateTime($c['fecha_hora']);
            $occupied[] = $dt->format('H:i');
        }
    }

    header('Content-Type: application/json');
    echo json_encode(['occupied' => $occupied]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
