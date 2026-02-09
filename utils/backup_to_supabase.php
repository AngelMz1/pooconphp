<?php
/**
 * Script para Backup de PostgreSQL Local a Supabase
 * Ejecutar periódicamente para mantener sincronizado Supabase como backup
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\SupabaseClient;
use App\PostgreSQLClient;

// Cargar variables de entorno
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

echo "==========================================\n";
echo "Backup PostgreSQL Local → Supabase\n";
echo "==========================================\n\n";

try {
    // Conectar a PostgreSQL Local
    echo "1. Conectando a PostgreSQL Local...\n";
    $postgres = new PostgreSQLClient(
        $_ENV['DB_HOST'],
        $_ENV['DB_DATABASE'],
        $_ENV['DB_USERNAME'],
        $_ENV['DB_PASSWORD'],
        (int)$_ENV['DB_PORT']
    );
    echo "   ✅ Conectado a PostgreSQL Local\n\n";
    
    // Conectar a Supabase
    echo "2. Conectando a Supabase...\n";
    $supabase = new SupabaseClient($_ENV['SUPABASE_URL'], $_ENV['SUPABASE_KEY']);
    echo "   ✅ Conectado a Supabase\n\n";
    
    // Solo sincronizar tablas de datos (no configuración ni catálogos)
    $tables = [
        'users',
        'pacientes',
        'citas',
        'consultas',
        'historias_clinicas',
        'signos_vitales',
        'formulas_medicas',
        'procedimientos',
        'facturas',
        'factura_items',
        'pagos',
        'medicos',
        'sync_logs'
    ];
    
    $totalBacked = 0;
    
    foreach ($tables as $table) {
        echo "3. Respaldando tabla: $table\n";
        
        try {
            // Obtener datos modificados recientemente (últimas 24 horas)
            $data = $postgres->select(
                $table,
                '*',
                'updated_at=gte.' . date('Y-m-d H:i:s', strtotime('-24 hours'))
            );
            
            $count = count($data);
            
            if ($count === 0) {
                echo "   ℹ️  Sin cambios recientes\n\n";
                continue;
            }
            
            echo "   📦 $count registros modificados\n";
            
            // Upsert cada registro en Supabase
            $backed = 0;
            foreach ($data as $row) {
                try {
                    // Intentar insertar, si falla (ya existe), actualizar
                    try {
                        $supabase->insert($table, $row);
                    } catch (Exception $insertError) {
                        // Si es error de duplicado, hacer update
                        if (strpos($insertError->getMessage(), 'duplicate') !== false ||
                            strpos($insertError->getMessage(), 'unique') !== false) {
                            
                            // Identificar PK según tabla
                            $pkColumn = 'id';
                            if ($table === 'pacientes') $pkColumn = 'id_paciente';
                            elseif ($table === 'consultas') $pkColumn = 'id_consulta';
                            elseif ($table === 'historias_clinicas') $pkColumn = 'id_historia';
                            elseif ($table === 'facturas') $pkColumn = 'id_factura';
                            elseif ($table === 'citas') $pkColumn = 'id';
                            elseif ($table === 'formulas_medicas') $pkColumn = 'id_formula';
                            
                            if (isset($row[$pkColumn])) {
                                $id = $row[$pkColumn];
                                unset($row[$pkColumn]); // No actualizar PK
                                $supabase->update($table, $row, "$pkColumn=eq.$id");
                            }
                        } else {
                            throw $insertError;
                        }
                    }
                    $backed++;
                } catch (Exception $e) {
                    echo "   ⚠️  Error respaldando registro: " . $e->getMessage() . "\n";
                }
            }
            
            echo "   ✅ $backed registros respaldados\n\n";
            $totalBacked += $backed;
            
        } catch (Exception $e) {
            echo "   ❌ Error: " . $e->getMessage() . "\n\n";
        }
    }
    
    echo "==========================================\n";
    echo "✅ Backup Completado\n";
    echo "==========================================\n";
    echo "Total de registros respaldados: $totalBacked\n\n";
    
} catch (Exception $e) {
    echo "\n❌ Error fatal: " . $e->getMessage() . "\n";
    exit(1);
}
