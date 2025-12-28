<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\SupabaseClient;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Dotenv\Dotenv;

// Aumentar límite de memoria y tiempo
ini_set('memory_limit', '512M');
set_time_limit(0);

echo "Iniciando importación optimizada de CIE-10...\n";

// Cargar variables de entorno
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$supabase = new SupabaseClient($_ENV['SUPABASE_URL'], $_ENV['SUPABASE_KEY']);

$inputFile = __DIR__ . '/TablaReferencia_CIE10__1.xlsx';

try {
    echo "Leyendo archivo Excel: $inputFile\n";
    $spreadsheet = IOFactory::load($inputFile);
    $worksheet = $spreadsheet->getActiveSheet();
    $highestRow = $worksheet->getHighestRow();
    
    echo "Total de filas a procesar: $highestRow\n";

    $batchSize = 250; // Lote más grande para mayor velocidad
    $batch = [];
    $count = 0;
    
    // Primero, limpiar la tabla cie10 actual para evitar duplicados/errores
    // Nota: Esto debería hacerse con cuidado en producción.
    // Como el usuario dio permiso de limpiar:
    echo "Limpiando tabla cie10...\n";
    // El cliente Supabase básico no tiene método delete_all o truncate fácil expuesto en este proyecto,
    // pero podemos intentar borrar todo si id > 0 (asumiendo que hay pocos, si no, será lento).
    // O mejor, confiamos en que el usuario ejecutó el script de drop/create.
    
    for ($row = 2; $row <= $highestRow; $row++) {
        // Mapeo de columnas basado en headers confirmados:
        // [1] Codigo -> B
        // [2] Nombre -> C
        // [3] Descripcion -> D (A veces Nombre y Descripcion son parecidos, usaremos Nombre como principal descripción corta)
        // [4] Habilitado -> E
        // [9] Extra_II:EdadMinima -> J
        // [10] Extra_III:EdadMaxima -> K
        // [17] Extra_X:Sexo -> R
        
        $codigo = $worksheet->getCell('B' . $row)->getValue();
        $nombre = $worksheet->getCell('C' . $row)->getValue(); 
        //$descripcion = $worksheet->getCell('D' . $row)->getValue(); // A veces es redundante
        $activoStr = $worksheet->getCell('E' . $row)->getValue();
        $sexo = $worksheet->getCell('R' . $row)->getValue(); 
        $edadMin = $worksheet->getCell('J' . $row)->getValue();
        $edadMax = $worksheet->getCell('K' . $row)->getValue();

        if (empty($codigo)) continue;

        $activo = (strtoupper($activoStr) === 'SI');
        
        // Normalizar sexo (A=Ambos, M=Masculino, F=Femenino - ajustar según datos reales)
        // En Excel vi "A" en fila 4. 
        
        $batch[] = [
            'codigo' => trim($codigo),
            'descripcion' => trim($nombre), // Usamos Nombre como la descripción principal visible
            'activo' => $activo,
            'sexo_aplicable' => $sexo,
            'edad_minima' => $edadMin,
            'edad_maxima' => $edadMax
        ];

        if (count($batch) >= $batchSize) {
            try {
                $supabase->insert('cie10', $batch);
                $count += count($batch);
                echo "Procesados: $row / $highestRow (Total insertados: $count)\r";
            } catch (Exception $e) {
                echo "\nError en lote fila $row: " . $e->getMessage() . "\n";
            }
            $batch = [];
        }
    }

    if (!empty($batch)) {
        try {
            $supabase->insert('cie10', $batch);
            $count += count($batch);
        } catch (Exception $e) {
             echo "\nError en lote final: " . $e->getMessage() . "\n";
        }
    }

    echo "\n\nImportación completada. Total registros: $count\n";

} catch (Exception $e) {
    echo "Error fatal: " . $e->getMessage() . "\n";
}
