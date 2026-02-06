<?php
require_once __DIR__ . '/../vendor/autoload.php';
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
try { $dotenv->load(); } catch (Exception $e) {}

$host = $_ENV['DB_HOST'] ?? '127.0.0.1';
$db = $_ENV['DB_DATABASE'] ?? 'pooconphp_local';
$user = $_ENV['DB_USERNAME'] ?? 'postgres';
$pass = $_ENV['DB_PASSWORD'] ?? '';

try {
    $pdo = new PDO("pgsql:host=$host;dbname=$db", $user, $pass);
    echo "Conexión Exitosa.\n";
    
    $tables = ['users', 'pacientes', 'historias_clinicas', 'citas', 'consultas', 'medicos', 'permissions', 'tarifarios'];
    
    foreach ($tables as $t) {
        $stmt = $pdo->query("SELECT to_regclass('public.$t')");
        $exists = $stmt->fetchColumn();
        if ($exists) {
            echo "[OK] Tabla '$t' existe.\n";
        } else {
            echo "[X] ERROR: Tabla '$t' NO existe.\n";
        }
    }
} catch (PDOException $e) {
    echo "Error de conexión: " . $e->getMessage();
}
