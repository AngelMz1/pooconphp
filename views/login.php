<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/auth_helper.php';

use App\DatabaseFactory;
use App\SupabaseClient;
use Dotenv\Dotenv;

// Si ya está logueado, ir al dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    try {
        $dotenv = Dotenv::createImmutable(__DIR__ . '/..');
        $dotenv->load();

        $supabase = DatabaseFactory::create();

        // Buscar usuario
        // Nota: SupabaseClient::select devuelve un array de resultados
        $users = $supabase->select('users', '*', "username=eq.$username");

        if (!empty($users) && isset($users[0])) {
            $user = $users[0];
            
            // Verificar contraseña
            if (password_verify($password, $user['password_hash'])) {
                // Login exitoso
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['nombre_completo'];
                $_SESSION['user_role'] = $user['rol'];
                
                
                // --- Cargar Permisos del Usuario (desde user_permissions) ---
                try {
                    $userId = $user['id'];
                    // Consultar permisos directamente desde user_permissions
                    $perms = $supabase->query("
                        SELECT p.codigo
                        FROM user_permissions up
                        JOIN permisos p ON up.permission_id = p.id
                        WHERE up.user_id = $userId
                    ");
                    
                    $permList = [];
                    if (!empty($perms)) {
                        foreach ($perms as $p) {
                            $permList[] = $p['codigo'];
                        }
                    }
                    $_SESSION['permissions'] = $permList;
                } catch (Exception $e) {
                    // Si falla cargar permisos, iniciar vacío
                    error_log("Error loading user permissions: " . $e->getMessage());
                    $_SESSION['permissions'] = [];
                }


                header("Location: ../index.php");
                exit;
            } else {
                $error = 'Contraseña incorrecta';
            }
        } else {
            $error = 'Usuario no encontrado';
        }

    } catch (Exception $e) {
        $error = 'Error de sistema: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Login - Gestión Médica</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: var(--bg-secondary);
        }
        .login-card {
            width: 100%;
            max-width: 400px;
            padding: 2rem;
            background: var(--bg-card);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-xl);
        }
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-logo {
            font-size: 3rem;
            display: block;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <span class="login-logo">🏥</span>
            <h2>Iniciar Sesión</h2>
            <p>Sistema de Gestión Médica</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Usuario</label>
                <input type="text" name="username" required autofocus placeholder="Ej. admin">
            </div>

            <div class="form-group">
                <label>Contraseña</label>
                <input type="password" name="password" required placeholder="••••••">
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%">
                Entrar
            </button>
        </form>
    </div>
</body>
</html>
