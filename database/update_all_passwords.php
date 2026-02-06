<?php
/**
 * Actualizar contraseñas usando password_hash de PHP
 */

require_once '../vendor/autoload.php';

use App\DatabaseFactory;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
try {
    $dotenv->safeLoad();
} catch (Exception $e) {
    die("Error loading .env: " . $e->getMessage());
}

$db = DatabaseFactory::create();

echo "<pre>\n";
echo "=== ACTUALIZAR CONTRASEÑAS CON NUEVO HASH ===\n\n";

try {
    // Generar nuevo hash para admin123
    $newHash = password_hash('admin123', PASSWORD_DEFAULT);
    echo "Nuevo hash generado: " . substr($newHash, 0, 30) . "...\n\n";
    
    // Actualizar todos los usuarios
    $users = ['admin', 'facturador', 'medico'];
    
    foreach ($users as $username) {
        $result = $db->query("UPDATE users SET password_hash = '$newHash' WHERE username = '$username'");
        echo "✓ Password de '$username' actualizado\n";
    }
    
    echo "\n--- Verificación ---\n\n";
    
    // Verificar cada usuario
    $stmt = $db->query("SELECT username, password_hash FROM users WHERE username IN ('admin', 'facturador', 'medico') ORDER BY username");
    
    foreach ($stmt as $row) {
        $matches = password_verify('admin123', $row['password_hash']);
        $status = $matches ? '✅ OK' : '❌ FALLO';
        echo "{$row['username']}: $status\n";
    }
    
    echo "\n=== COMPLETADO ===\n";
    echo "\nTodos los usuarios ahora tienen password: admin123\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}

echo "</pre>";
?>
