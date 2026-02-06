<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Verifica si el usuario está logueado. Si no, redirige al login.
 */
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        // Detectar si estamos en la raíz (index.php) o en una subcarpeta (views/)
        // Si el script actual es index.php (o no está en views), la ruta debe ser views/login.php
        $redirect = 'views/login.php';
        
        if (strpos($_SERVER['SCRIPT_NAME'], '/views/') !== false) {
            $redirect = 'login.php';
        }
        
        header("Location: " . $redirect);
        exit;
    }
}

/**
 * Verifica si el usuario tiene uno de los roles permitidos.
 * Si no, muestra error o redirige.
 */
function requireRole($allowedRoles) {
    requireLogin();
    
    if (!is_array($allowedRoles)) {
        $allowedRoles = [$allowedRoles];
    }
    
    if (!in_array($_SESSION['user_role'], $allowedRoles)) {
        // Acceso denegado
        http_response_code(403);
        die("⛔ Acceso Denegado: No tienes permiso para ver esta página.");
    }
}

/**
 * Verifica rol sin detener ejecución (para mostrar/ocultar botones)
 */
function hasRole($role) {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}

/**
 * Cierra la sesión
 */
function logout() {
    session_destroy();
    header("Location: login.php");
    exit;
}

/**
 * Verifica si el usuario tiene un permiso específico.
 */
function hasPermission($permission) {
    // Todos los usuarios deben tener permisos explícitos (incluido admin)
    
    if (!isset($_SESSION['permissions']) || !is_array($_SESSION['permissions'])) {
        return false;
    }
    
    return in_array($permission, $_SESSION['permissions']);
}

/**
 * Detiene la ejecución si no tiene el permiso.
 */
function requirePermission($permission) {
    requireLogin();
    
    if (!hasPermission($permission)) {
        http_response_code(403);
        die("⛔ Acceso Denegado: No tienes permiso para realizar esta acción ($permission).");
    }
}
