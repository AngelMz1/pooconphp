<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

// Load env
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
try {
    $dotenv->load();
} catch (Exception $e) {
    echo "No .env file found. Proceeding with defaults if possible.\n";
}

$host = $_ENV['DB_HOST'] ?? '127.0.0.1';
$port = $_ENV['DB_PORT'] ?? '5432';
$dbname = $_ENV['DB_DATABASE'] ?? 'pooconphp_local';
$user = $_ENV['DB_USERNAME'] ?? 'postgres';
$pass = $_ENV['DB_PASSWORD'] ?? '';

echo "Connecting to $dbname at $host:$port as $user...\n";

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    
    echo "Connected successfully.\n";

    // List of SQL files in logical dependency order
    $files = [
        'crear_tablas.sql',            // 1. Pacientes, Historias (Base)
        'restore_missing_schemas.sql', // 2. Users, Medicos, Especialidades, Consultas (Restores missing)
        'create_auth_tables.sql',      // 3. User extra config / Citas (Might overlap but IF NOT EXISTS handles it)
        'create_missing_tables.sql',   // 4. Tarifarios, Configuracion
        'setup_reference_data.sql',    // 5. Reference Data (Sex, DocTypes, CIE10/CUPS schema)
        'create_facturacion_tables.sql', // 5. Facturas (Ref Consultas)
        'update_schema.sql',           // 6. Alters
        'update_schema_permissions.sql', // 7. Alters 
        'fix_column_types.sql',        // 8. Fixes
    ];

    foreach ($files as $file) {
        $path = __DIR__ . '/' . $file;
        if (file_exists($path)) {
            echo "Executing $file...\n";
            $sql = file_get_contents($path);
            try {
                $pdo->exec($sql);
                echo "✓ $file executed.\n";
            } catch (PDOException $e) {
                echo "X Error executing $file: " . $e->getMessage() . "\n";
                // Don't stop? Or stop? 
                // Some errors might be "table exists".
            }
        } else {
            echo "! File $file not found.\n";
        }
    }

    echo "\nDatabase setup completed.\n";

} catch (PDOException $e) {
    echo "FATAL: Could not connect to database. " . $e->getMessage() . "\n";
    echo "Please ensure the database '$dbname' exists and credentials are correct in .env\n";
    exit(1);
}
