<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/auth_helper.php';

// Solo admin
requireRole('admin');

use App\SupabaseClient;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();
$supabase = new SupabaseClient($_ENV['SUPABASE_URL'], $_ENV['SUPABASE_KEY']);

$msg = '';
$error = '';

// --- Crear Usuario ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'crear') {
    $username = trim($_POST['username']);
    $nombre = trim($_POST['nombre_completo']);
    $rol = $_POST['rol'];
    $password = $_POST['password'];
    
    if (strlen($password) < 4) {
        $error = "La contraseña debe tener al menos 4 caracteres.";
    } else {
        $data = [
            'username' => $username,
            'nombre_completo' => $nombre,
            'rol' => $rol,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'active' => true
        ];
        
        try {
            // Intentar insertar
            $result = $supabase->insert('users', $data);
            
            if (isset($result['error'])) {
                 $error = "Error al crear usuario (¿Usuario duplicado?).";
            } else {
                 header("Location: gestionar_usuarios.php?msg=Usuario Creado");
                 exit;
            }
        } catch (Exception $e) {
            $error = "Error sistema: " . $e->getMessage();
        }
    }
}

// Obtener usuarios existentes
$usuarios = $supabase->select('users', '*', null, 'created_at.desc');

if (isset($_GET['msg'])) $msg = $_GET['msg'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Gestión de Usuarios - Admin</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body class="dashboard-container">
    <?php include '../includes/sidebar.php'; ?>
    <?php include '../includes/header.php'; ?>
    
    <main class="main-content">
        <div class="container">
            <h1>🛠️ Gestión de Usuarios</h1>
            
            <?php if($msg): ?>
                <div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
            <?php endif; ?>
            
            <?php if($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="card mb-4">
                <h2>Crear Nuevo Usuario (Médico/Personal)</h2>
                <form method="POST" class="form-row">
                    <input type="hidden" name="action" value="crear">
                    
                    <div class="form-group" style="flex: 1;">
                        <label>Rol</label>
                        <select name="rol" required>
                            <option value="medico">Médico</option>
                            <option value="cajero">Cajero</option>
                            <option value="admin">Administrador</option>
                        </select>
                    </div>

                    <div class="form-group" style="flex: 1;">
                        <label>Nombre Completo</label>
                        <input type="text" name="nombre_completo" required placeholder="Ej. Dr. Gregory House">
                    </div>
                    
                    <div class="form-group" style="flex: 1;">
                        <label>Usuario (Login)</label>
                        <input type="text" name="username" required placeholder="Ej. house">
                    </div>

                    <div class="form-group" style="flex: 1;">
                        <label>Contraseña</label>
                        <input type="password" name="password" required placeholder="******">
                    </div>

                    <div class="form-group" style="display: flex; align-items: flex-end;">
                        <button type="submit" class="btn btn-primary">Crear Usuario</button>
                    </div>
                </form>
            </div>

            <div class="card">
                <h2>Usuarios del Sistema</h2>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Usuario</th>
                                <th>Nombre</th>
                                <th>Rol</th>
                                <th>Estado</th>
                                <th>Creado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($usuarios)): ?>
                                <tr><td colspan="5" class="text-center">No hay usuarios.</td></tr>
                            <?php else: ?>
                                <?php foreach($usuarios as $u): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($u['username']) ?></strong></td>
                                        <td><?= htmlspecialchars($u['nombre_completo']) ?></td>
                                        <td>
                                            <span class="badge <?= $u['rol'] === 'admin' ? 'badge-primary' : ($u['rol'] === 'medico' ? 'badge-success' : 'badge-secondary') ?>">
                                                <?= ucfirst($u['rol']) ?>
                                            </span>
                                        </td>
                                        <td><?= isset($u['active']) && $u['active'] ? 'Activo' : 'Inactivo' ?></td>
                                        <td><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
