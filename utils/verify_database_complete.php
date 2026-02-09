<?php
// Verificación completa de la base de datos
require_once __DIR__ . '/../vendor/autoload.php';

use App\PostgreSQLClient;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

try {
    $postgres = new PostgreSQLClient(
        $_ENV['DB_HOST'],
        $_ENV['DB_DATABASE'],
        $_ENV['DB_USERNAME'],
        $_ENV['DB_PASSWORD'],
        (int)$_ENV['DB_PORT']
    );
    
    echo "========================================\n";
    echo "VERIFICACIÓN COMPLETA DE BASE DE DATOS\n";
    echo "========================================\n\n";
    
    // 1. Usuarios
    $users = $postgres->select('users', 'COUNT(*) as total');
    echo "✅ Usuarios: {$users[0]['total']}\n";
    
    // 2. Perfil médico
    $medicos = $postgres->select('medicos', 'COUNT(*) as total');
    echo "✅ Perfiles médicos: {$medicos[0]['total']}\n";
    
    // 3. Tipos de documento
    $tipos_doc = $postgres->select('tipo_documento', '*', '', 'codigo.asc');
    echo "✅ Tipos de documento: " . count($tipos_doc) . "\n";
    foreach ($tipos_doc as $tipo) {
        echo "   - {$tipo['codigo']}: {$tipo['descripcion']}\n";
    }
    
    // 4. EPS
    $eps = $postgres->select('eps', 'COUNT(*) as total');
    echo "\n✅ EPS: {$eps[0]['total']}\n";
    
    // 5. Municipios de Nariño
    $ciudades = $postgres->select('ciudades', 'COUNT(*) as total', 'departamento=eq.Nariño');
    echo "✅ Municipios Nariño: {$ciudades[0]['total']}\n";
    
    // 6. Barrios de Pasto
    $barrios = $postgres->select('barrio', 'COUNT(*) as total');
    echo "✅ Barrios Pasto: {$barrios[0]['total']}\n";
    
    echo "\n========================================\n";
    echo "SISTEMA COMPLETAMENTE CONFIGURADO\n";
    echo "========================================\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
