<?php
/**
 * API: Get User Permissions
 * Returns all available permissions and the permissions assigned to a specific user
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/auth_helper.php';

use App\DatabaseFactory;

// Only users with gestionar_usuarios permission can view/manage permissions
requirePermission('gestionar_usuarios');

header('Content-Type: application/json');

$userId = $_GET['user_id'] ?? null;

if (!$userId || !is_numeric($userId)) {
    echo json_encode(['error' => 'user_id es requerido y debe ser numérico']);
    http_response_code(400);
    exit;
}

try {
    $supabase = DatabaseFactory::create();
    
    // Get all available permissions
    $allPerms = $supabase->select('permisos', 'id,codigo,nombre,categoria', '');
    
    // Get user's assigned permissions with details
    $userPerms = $supabase->query("
        SELECT 
            up.permission_id,
            p.codigo,
            p.nombre,
            up.granted_at,
            up.notes,
            u.nombre_completo as granted_by_name
        FROM user_permissions up
        JOIN permisos p ON up.permission_id = p.id
        LEFT JOIN users u ON up.granted_by = u.id
        WHERE up.user_id = $userId
    ");
    
    // Get user info
    $user = $supabase->select('users', 'id,username,nombre_completo,rol', "id=eq.$userId");
    
    if (empty($user)) {
        echo json_encode(['error' => 'Usuario no encontrado']);
        http_response_code(404);
        exit;
    }
    
    // Organize all permissions by category
    $permsByCategory = [];
    foreach ($allPerms as $perm) {
        $cat = $perm['categoria'] ?? 'Sin Categoría';
        if (!isset($permsByCategory[$cat])) {
            $permsByCategory[$cat] = [];
        }
        $permsByCategory[$cat][] = $perm;
    }
    
    echo json_encode([
        'success' => true,
        'user' => $user[0],
        'all_permissions' => $allPerms,
        'permissions_by_category' => $permsByCategory,
        'user_permissions' => array_column($userPerms, 'codigo'),
        'user_permissions_detail' => $userPerms
    ]);
    
} catch (Exception $e) {
    error_log("Error in get_user_permissions.php: " . $e->getMessage());
    echo json_encode([
        'error' => 'Error al cargar permisos',
        'message' => $e->getMessage()
    ]);
    http_response_code(500);
}
