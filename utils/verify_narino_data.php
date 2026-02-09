<?php
// Script para verificar datos de Nariño en PostgreSQL local
require_once __DIR__ . '/../vendor/autoload.php';

use App\PostgreSQLClient;

// Cargar entorno
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

try {
    // Conectar a PostgreSQL Local
    $postgres = new PostgreSQLClient(
        $_ENV['DB_HOST'],
        $_ENV['DB_DATABASE'],
        $_ENV['DB_USERNAME'],
        $_ENV['DB_PASSWORD'],
        (int)$_ENV['DB_PORT']
    );
    
    echo "========================================\n";
    echo "VERIFICACIÓN DE DATOS DE NARIÑO\n";
    echo "========================================\n\n";
    
    // 1. Contar municipios de Nariño
    $narino = $postgres->select(
        'ciudades',
        'COUNT(*) as total',
        'departamento=eq.Nariño'
    );
    echo "1. Municipios de Nariño: " . $narino[0]['total'] . "\n\n";
    
    // 2. Buscar Pasto
    $pasto = $postgres->select(
        'ciudades',
        '*',
        'codigo_dane=eq.52001'
    );
    if (count($pasto) > 0) {
        echo "2. ✅ Pasto encontrado:\n";
        echo "   ID: " . $pasto[0]['id'] . "\n";
        echo "   Nombre: " . $pasto[0]['nombre'] . "\n";
        echo "   DANE: " . $pasto[0]['codigo_dane'] . "\n\n";
        
        $pastoId = $pasto[0]['id'];
        
        // 3. Contar barrios de Pasto
        $barrios = $postgres->select(
            'barrio',
            'COUNT(*) as total',
            "ciudad_id=eq.$pastoId"
        );
        echo "3. Barrios de Pasto: " . $barrios[0]['total'] . "\n\n";
        
        // 4. Listar algunos barrios
        $barriosList = $postgres->select(
            'barrio',
            'barrio, zona',
            "ciudad_id=eq.$pastoId",
            'barrio.asc',
            10
        );
        echo "4. Primeros 10 barrios:\n";
        foreach ($barriosList as $b) {
            echo "   - {$b['barrio']} (Zona: {$b['zona']})\n";
        }
    } else {
        echo "2. ❌ Pasto NO encontrado\n";
    }
    
    echo "\n✅ Verificación completada\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
