<?php
/**
 * Resetear contraseñas de facturador y medico a admin123
 */

require_once '../vendor/autoload.php';

use App\DatabaseFactory;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
try {
    $dotenv->safeLoad();
} catch (Exception $e) {
    echo "Error loading .env: " . $e->getMessage() . "\n";
}

$db = DatabaseFactory::create();

echo "<pre>";
echo "=== RESETEAR CONTRASEÑAS ===\n\n";

try {
    // Hash correcto para "admin123"
    $correctHash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
    
    // Actualizar facturador (usando query directo)
    $db->query("UPDATE users SET password_hash = '$correctHash' WHERE username = 'facturador'");
    echo "✓ Password de 'facturador' actualizado\n";
    
    // Actualizar medico
    $db->query("UPDATE users SET password_hash = '$correctHash' WHERE username = 'medico'");
    echo "✓ Password de 'medico' actualizado\n";
    
    // Verificar
    echo "\nVerificando...\n\n";
    $users = $db->query("SELECT username, password_hash FROM users WHERE username IN ('admin', 'facturador', 'medico') ORDER BY username");
    
    foreach ($users as $user) {
        $matches = password_verify('admin123', $user['password_hash']);
        $status = $matches ? '✓ OK' : '✗ FALLO';
        echo "{$user['username']}: $status\n";
    }
    
    echo "\n=== COMPLETADO ===\n";
    echo "\nCredenciales actualizadas:\n";
    echo "  - admin / admin123\n";
    echo "  - facturador / admin123\n";
    echo "  - medico / admin123\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>
