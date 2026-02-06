<?php
/**
 * API: Save User Permissions
 * Updates the permissions assigned to a specific user
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/auth_helper.php';

use App\DatabaseFactory;

// Only users with gestionar_usuarios permission can manage permissions
requirePermission('gestionar_usuarios');

header('Content-Type: application/json');

// Get JSON input
$data = json_decode(file_get_contents('php://input'), true);
$userId = $data['user_id'] ?? null;
$permissionCodes = $data['permissions'] ?? [];

if (!$userId || !is_numeric($userId)) {
    echo json_encode(['error' => 'user_id es requerido y debe ser numérico']);
    http_response_code(400);
    exit;
}

if (!is_array($permissionCodes)) {
    echo json_encode(['error' => 'permissions debe ser un array']);
    http_response_code(400);
    exit;
}

try {
    $supabase = DatabaseFactory::create();
    
    // Verify user exists
    $user = $supabase->select('users', 'id,username', "id=eq.$userId");
    if (empty($user)) {
        echo json_encode(['error' => 'Usuario no encontrado']);
        http_response_code(404);
        exit;
    }
    
    // Get current user ID (who is granting the permissions)
    $grantedBy = $_SESSION['user_id'];
    
    // Start transaction (delete all + insert new)
    $supabase->query("DELETE FROM user_permissions WHERE user_id = $userId");
    
    $insertedCount = 0;
    foreach ($permissionCodes as $permCode) {
        // Get permission ID from code
        $perm = $supabase->select('permisos', 'id', "codigo=eq.$permCode");
        
        if (!empty($perm)) {
            $permId = $perm[0]['id'];
            
            // Insert permission
            $supabase->query("
                INSERT INTO user_permissions (user_id, permission_id, granted_by, notes)
                VALUES ($userId, $permId, $grantedBy, 'Asignado vía UI')
            ");
            
            $insertedCount++;
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Permisos actualizados correctamente',
        'user_id' => $userId,
        'permissions_assigned' => $insertedCount
    ]);
    
} catch (Exception $e) {
    error_log("Error in save_user_permissions.php: " . $e->getMessage());
    echo json_encode([
        'error' => 'Error al guardar permisos',
        'message' => $e->getMessage()
    ]);
    http_response_code(500);
}
