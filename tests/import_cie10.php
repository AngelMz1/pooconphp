<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\DatabaseFactory;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Dotenv\Dotenv;

// Aumentar límite de memoria y tiempo
ini_set('memory_limit', '512M');
set_time_limit(0);

echo "Iniciando importación de CIE-10 a PostgreSQL local...\n";

// Cargar variables de entorno
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
try {
    $dotenv->safeLoad();
} catch (Exception $e) { }

$db = DatabaseFactory::create();

$inputFile = __DIR__ . '/TablaReferencia_CIE10__1.xlsx';

try {
    echo "Leyendo archivo Excel: $inputFile\n";
    $spreadsheet = IOFactory::load($inputFile);
    $worksheet = $spreadsheet->getActiveSheet();
    $highestRow = $worksheet->getHighestRow();
    
    echo "Total de filas a procesar: $highestRow\n";

    $batchSize = 250;
    $batch = [];
    $count = 0;
    
    for ($row = 2; $row <= $highestRow; $row++) {
        $codigo = $worksheet->getCell('B' . $row)->getValue();
        $nombre = $worksheet->getCell('C' . $row)->getValue(); 
        $activoStr = $worksheet->getCell('E' . $row)->getValue();
        $sexo = $worksheet->getCell('R' . $row)->getValue(); 
        $edadMin = $worksheet->getCell('J' . $row)->getValue();
        $edadMax = $worksheet->getCell('K' . $row)->getValue();

        if (empty($codigo)) continue;

        $activo = (strtoupper($activoStr) === 'SI');
        
        $batch[] = [
            'codigo' => trim($codigo),
            'descripcion' => trim($nombre),
            'activo' => $activo,
            'sexo_aplicable' => $sexo ?? 'A',
            'edad_minima' => $edadMin ?? 0,
            'edad_maxima' => $edadMax ?? 120
        ];

        if (count($batch) >= $batchSize) {
            try {
                $db->insert('cie10', $batch);
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
            $db->insert('cie10', $batch);
            $count += count($batch);
        } catch (Exception $e) {
             echo "\nError en lote final: " . $e->getMessage() . "\n";
        }
    }

    echo "\n\nImportación completada. Total registros: $count\n";

} catch (Exception $e) {
    echo "Error fatal: " . $e->getMessage() . "\n";
}
