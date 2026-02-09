<?php
/**
 * Script para Sincronizar Datos de Supabase a PostgreSQL Local
 * Ejecutar después de configurar la base de datos local
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\SupabaseClient;
use App\PostgreSQLClient;

// Cargar variables de entorno
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

echo "==========================================\n";
echo "Sincronización Supabase → PostgreSQL Local\n";
echo "==========================================\n\n";

try {
    // Conectar a Supabase
    echo "1. Conectando a Supabase...\n";
    $supabase = new SupabaseClient($_ENV['SUPABASE_URL'], $_ENV['SUPABASE_KEY']);
    echo "   ✅ Conectado a Supabase\n\n";
    
    // Conectar a PostgreSQL Local
    echo "2. Conectando a PostgreSQL Local...\n";
    $postgres = new PostgreSQLClient(
        $_ENV['DB_HOST'],
        $_ENV['DB_DATABASE'],
        $_ENV['DB_USERNAME'],
        $_ENV['DB_PASSWORD'],
        (int)$_ENV['DB_PORT']
    );
    echo "   ✅ Conectado a PostgreSQL Local\n\n";
    
    // Tablas a sincronizar (en orden de dependencias)
    $tables = [
        // Tablas base sin dependencias
        'configuracion',
        'cie10',
        'cups',
        'medicamentos',
        'ciudades',
        
        // Tablas con FK a ciudades
        'barrio',
        
        // Tablas de usuarios
        'users',
        'medicos',
        
        // Tablas de pacientes
        'pacientes',
        'historias_clinicas',
        
        // Tablas de citas y consultas
        'citas',
        'consultas',
        'signos_vitales',
        
        // Tablas de fórmulas
        'formulas_medicas',
        'procedimientos',
        
        // Tablas de facturación
        'facturas',
        'factura_items',
        'pagos',
        
        // Tablas de sincronización
        'sync_logs'
    ];
    
    $totalSynced = 0;
    
    foreach ($tables as $table) {
        echo "3. Sincronizando tabla: $table\n";
        
        try {
            // Obtener datos de Supabase
            $data = $supabase->select($table);
            $count = count($data);
            
            if ($count === 0) {
                echo "   ℹ️  Sin datos en Supabase\n\n";
                continue;
            }
            
            echo "   📦 $count registros encontrados\n";
            
            // Iniciar transacción
            $postgres->beginTransaction();
            
            // Insertar cada registro
            $inserted = 0;
            foreach ($data as $row) {
                try {
                    $postgres->insert($table, $row);
                    $inserted++;
                } catch (Exception $e) {
                    // Si ya existe (conflict), ignorar
                    if (strpos($e->getMessage(), 'duplicate') === false &&
                        strpos($e->getMessage(), 'unique') === false) {
                        echo "   ⚠️  Error insertando: " . $e->getMessage() . "\n";
                    }
                }
            }
            
            // Commit transacción
            $postgres->commit();
            
            echo "   ✅ $inserted registros insertados\n\n";
            $totalSynced += $inserted;
            
        } catch (Exception $e) {
            // Rollback en caso de error
            try {
                $postgres->rollback();
            } catch (Exception $rollbackError) {
                // Ignorar errores de rollback
            }
            
            echo "   ❌ Error: " . $e->getMessage() . "\n\n";
        }
    }
    
    echo "==========================================\n";
    echo "✅ Sincronización Completada\n";
    echo "==========================================\n";
    echo "Total de registros sincronizados: $totalSynced\n\n";
    
} catch (Exception $e) {
    echo "\n❌ Error fatal: " . $e->getMessage() . "\n";
    exit(1);
}
