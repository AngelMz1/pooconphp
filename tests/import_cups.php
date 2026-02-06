<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\DatabaseFactory;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Dotenv\Dotenv;

// Aumentar límite de memoria y tiempo
ini_set('memory_limit', '512M');
set_time_limit(0);

echo "Iniciando importación de CUPS a PostgreSQL local...\n";

// Cargar variables de entorno
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
try {
    $dotenv->safeLoad();
} catch (Exception $e) { }

$db = DatabaseFactory::create();

$inputFile = __DIR__ . '/TablaReferencia_CUPS__1.xlsx';

try {
    echo "Leyendo archivo Excel: $inputFile\n";
    $spreadsheet = IOFactory::load($inputFile);
    $worksheet = $spreadsheet->getActiveSheet();
    $highestRow = $worksheet->getHighestRow();
    
    echo "Total de filas a procesar: $highestRow\n";

    // Inspeccionar headers (fila 1)
    echo "Columnas detectadas:\n";
    for ($col = 'A'; $col <= 'F'; $col++) {
        $header = $worksheet->getCell($col . '1')->getValue();
        echo "  $col: $header\n";
    }

   // Asumiendo estructura: A=Codigo, B=Nombre/Descripcion, C=Seccion (opcional)
    // Ajustar según headers reales
    
    $batchSize = 250;
    $batch = [];
    $count = 0;
    
    for ($row = 2; $row <= $highestRow; $row++) {
        $codigo = $worksheet->getCell('B' . $row)->getValue(); // Ajustar columna
        $nombre = $worksheet->getCell('C' . $row)->getValue(); 
        $activoStr = $worksheet->getCell('E' . $row)->getValue();

        if (empty($codigo)) continue;

        $activo = (strtoupper($activoStr) === 'SI');
        
        $batch[] = [
            'codigo' => trim($codigo),
            'nombre' => trim($nombre),
            'descripcion' => null, // Si hay columna de desc adicional, agregarla
            'seccion' => null,
            'activo' => $activo
        ];

        if (count($batch) >= $batchSize) {
            try {
                $db->insert('cups', $batch);
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
            $db->insert('cups', $batch);
            $count += count($batch);
        } catch (Exception $e) {
             echo "\nError en lote final: " . $e->getMessage() . "\n";
        }
    }

    echo "\n\nImportación completada. Total registros: $count\n";

} catch (Exception $e) {
    echo "Error fatal: " . $e->getMessage() . "\n";
}
