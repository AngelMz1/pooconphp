<?php
/**
 * Verificar usuarios y permisos configurados
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

echo "<style>
body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; padding: 20px; }
table { width: 100%; border-collapse: collapse; background: white; margin: 20px 0; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
th { background: #4CAF50; color: white; }
tr:hover { background: #f5f5f5; }
h2 { color: #333; border-bottom: 3px solid #4CAF50; padding-bottom: 10px; }
.success { color: #4CAF50; font-weight: bold; }
.error { color: #f44336; font-weight: bold; }
.badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 12px; }
.badge-admin { background: #f44336; color: white; }
.badge-facturador { background: #2196F3; color: white; }
.badge-medico { background: #4CAF50; color: white; }
</style>";

echo "<h1>🔐 Verificación de Usuarios y Permisos</h1>";

try {
    // 1. Listar usuarios
    echo "<h2>📋 Usuarios del sistema</h2>";
    $users = $db->select('users', 'id, username, nombre_completo, rol, active', '', '', 'username');
    
    echo "<table>";
    echo "<tr><th>ID</th><th>Username</th><th>Nombre Completo</th><th>Rol</th><th>Activo</th></tr>";
    foreach ($users as $user) {
        $badgeClass = "badge badge-" . $user['rol'];
        $activeText = $user['active'] === 't' || $user['active'] === true ? '<span class="success">✓ Sí</span>' : '<span class="error">✗ No</span>';
        echo "<tr>";
        echo "<td>{$user['id']}</td>";
        echo "<td><strong>{$user['username']}</strong></td>";
        echo "<td>{$user['nombre_completo']}</td>";
        echo "<td><span class='$badgeClass'>{$user['rol']}</span></td>";
        echo "<td>$activeText</td>";
        echo "</tr>";
    }
    echo "</table>";

    // 2. Contar permisos por rol
    echo "<h2>🔑 Permisos asignados por rol</h2>";
    $permisos = $db->query("
        SELECT rol, COUNT(*) as total_permisos
        FROM rol_permisos
        GROUP BY rol
        ORDER BY rol
    ");
    
    echo "<table>";
    echo "<tr><th>Rol</th><th>Total Permisos</th></tr>";
    foreach ($permisos as $p) {
        echo "<tr><td><span class='badge badge-{$p['rol']}'>{$p['rol']}</span></td><td>{$p['total_permisos']}</td></tr>";
    }
    echo "</table>";

    // 3. Permisos detallados de facturador
    echo "<h2>👤 Permisos del rol Facturador</h2>";
    $facturador_perms = $db->query("
        SELECT p.codigo, p.nombre, p.categoria
        FROM rol_permisos rp
        JOIN permisos p ON rp.permiso_codigo = p.codigo
        WHERE rp.rol = 'facturador'
        ORDER BY p.categoria, p.nombre
    ");
    
    echo "<table>";
    echo "<tr><th>Código</th><th>Nombre</th><th>Categoría</th></tr>";
    foreach ($facturador_perms as $perm) {
        echo "<tr>";
        echo "<td><code>{$perm['codigo']}</code></td>";
        echo "<td>{$perm['nombre']}</td>";
        echo "<td>{$perm['categoria']}</td>";
        echo "</tr>";
    }
    echo "</table>";

    // 4. Permisos detallados de médico
    echo "<h2>👨‍⚕️ Permisos del rol Médico</h2>";
    $medico_perms = $db->query("
        SELECT p.codigo, p.nombre, p.categoria
        FROM rol_permisos rp
        JOIN permisos p ON rp.permiso_codigo = p.codigo
        WHERE rp.rol = 'medico'
        ORDER BY p.categoria, p.nombre
    ");
    
    echo "<table>";
    echo "<tr><th>Código</th><th>Nombre</th><th>Categoría</th></tr>";
    foreach ($medico_perms as $perm) {
        echo "<tr>";
        echo "<td><code>{$perm['codigo']}</code></td>";
        echo "<td>{$perm['nombre']}</td>";
        echo "<td>{$perm['categoria']}</td>";
        echo "</tr>";
    }
    echo "</table>";

    // 5. Verificar password hash
    echo "<h2>🔒 Hash de contraseñas</h2>";
    echo "<table>";
    echo "<tr><th>Username</th><th>Password Match (admin123)</th></tr>";
    
    $stmt = $db->query("SELECT username, password_hash FROM users WHERE username IN ('admin', 'facturador', 'medico') ORDER BY username");
    foreach ($stmt as $row) {
        $matches = password_verify('admin123', $row['password_hash']);
        $matchText = $matches ? '<span class="success">✓ Coincide</span>' : '<span class="error">✗ No coincide</span>';
        echo "<tr><td><strong>{$row['username']}</strong></td><td>$matchText</td></tr>";
    }
    echo "</table>";

    echo "<hr><p class='success'>✅ Verificación completada exitosamente</p>";

} catch (Exception $e) {
    echo "<p class='error'>❌ ERROR: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
