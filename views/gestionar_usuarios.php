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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'crear') {
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
                $result = $supabase->insert('users', $data);
                if (isset($result['error'])) {
                     $error = "Error al crear usuario (¿Usuario duplicado?).";
                } else {
                     // Usuario creado, obtener ID
                     // Supabase devuelve el objeto creado si Prefer: return=representation. SupabaseClient lo hace.
                     $newUserId = null;
                     if (isset($result[0]['id'])) {
                         $newUserId = $result[0]['id'];
                     }
                     
                     // 2. Insertar Permisos
                     if ($newUserId && isset($_POST['permisos']) && is_array($_POST['permisos'])) {
                         $permData = [];
                         foreach ($_POST['permisos'] as $permId) {
                             $permData[] = [
                                 'user_id' => $newUserId,
                                 'permission_id' => (int)$permId
                             ];
                         }
                         if (!empty($permData)) {
                             // Bulk insert
                             $supabase->insert('user_permissions', $permData);
                         }
                     }

                     header("Location: gestionar_usuarios.php?msg=Usuario Creado");
                     exit;
                }
            } catch (Exception $e) {
                $error = "Error sistema: " . $e->getMessage();
            }
        }
    } elseif ($_POST['action'] === 'reset_password') {
        $userId = $_POST['user_id'];
        $newPass = $_POST['new_password'];
        
        if (strlen($newPass) < 4) {
            $error = "La contraseña nueva es muy corta.";
        } else {
            try {
                $hash = password_hash($newPass, PASSWORD_DEFAULT);
                $supabase->update('users', ['password_hash' => $hash], "id=eq.$userId");
                header("Location: gestionar_usuarios.php?msg=Contraseña Actualizada");
                exit;
            } catch (Exception $e) {
                $error = "Error al actualizar contraseña: " . $e->getMessage();
            }
        }
    } elseif ($_POST['action'] === 'eliminar') {
        $userId = $_POST['user_id'];
        
        // Evitar auto-eliminación
        if ($userId == $_SESSION['user_id']) {
            $error = "No puedes eliminar tu propia cuenta.";
        } else {
            try {
                // Intentar eliminar
                $result = $supabase->delete('users', "id=eq.$userId");
                // Verificar si hubo error (SupabaseClient a veces retorna array con error)
                if (isset($result['error'])) {
                     $error = "Error al eliminar usuario. Verifique que no tenga registros asociados.";
                } else {
                     header("Location: gestionar_usuarios.php?msg=Usuario Eliminado");
                     exit;
                }
            } catch (Exception $e) {
                $error = "No se puede eliminar el usuario. Es probable que tenga citas o registros asociados. " . $e->getMessage();
            }
        }
    } elseif ($_POST['action'] === 'update_permissions') {
        $userId = $_POST['user_id'];
        $permisos = isset($_POST['permisos']) ? $_POST['permisos'] : [];
        
        try {
            // 1. Eliminar permisos actuales
            $supabase->delete('user_permissions', "user_id=eq.$userId");
            
            // 2. Insertar nuevos
            if (!empty($permisos)) {
                $permData = [];
                foreach ($permisos as $pId) {
                    $permData[] = [
                        'user_id' => $userId, 
                        'permission_id' => (int)$pId
                    ];
                }
                $supabase->insert('user_permissions', $permData);
            }
            
            header("Location: gestionar_usuarios.php?msg=Permisos Actualizados");
            exit;
        } catch (Exception $e) {
            $error = "Error al actualizar permisos: " . $e->getMessage();
        }
    }
}

// Obtener usuarios y permisos
$usuarios = $supabase->select('users', '*', null, 'created_at.desc');
$permisosDisponibles = $supabase->select('permissions', '*', null, 'id.asc');

// Obtener mapa de permisos por usuario user_id => [perm_id, ...]
$allUserPerms = $supabase->select('user_permissions', '*');
$userPermissionsMap = [];
if (!empty($allUserPerms)) {
    foreach ($allUserPerms as $up) {
        $uid = $up['user_id'];
        if (!isset($userPermissionsMap[$uid])) {
            $userPermissionsMap[$uid] = [];
        }
        $userPermissionsMap[$uid][] = $up['permission_id'];
    }
}

if (isset($_GET['msg'])) $msg = $_GET['msg'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Gestión de Usuarios - Admin</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .checklist-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid var(--gray-300);
            padding: 10px;
            border-radius: 4px;
            background: var(--gray-50);
            /* color: #333; Eliminado para permitir texto blanco en form de creación (dark mode) */
        }
        .check-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
        }
    </style>
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
                <h2>Crear Nuevo Usuario</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="crear">
                    
                    <div class="grid grid-2">
                        <div class="form-group">
                            <label>Nombre Completo</label>
                            <input type="text" name="nombre_completo" required placeholder="Ej. Dr. Gregory House">
                        </div>
                        
                        <div class="form-group">
                            <label>Usuario (Login)</label>
                            <input type="text" name="username" required placeholder="Ej. house">
                        </div>

                        <div class="form-group">
                            <label>Contraseña</label>
                            <input type="password" name="password" required placeholder="******">
                        </div>

                        <div class="form-group">
                            <label>Rol Principal (Etiqueta)</label>
                            <select name="rol" required>
                                <option value="medico">Médico</option>
                                <option value="cajero">Cajero/Secretaria</option>
                                <option value="admin">Administrador</option>
                            </select>
                            <small class="form-help">Define si aparece en listas médicas.</small>
                        </div>
                    </div>

                    <div class="form-group mt-2">
                        <label>Asignar Permisos:</label>
                        <div class="checklist-container">
                            <?php foreach($permisosDisponibles as $p): ?>
                                <label class="check-item">
                                    <input type="checkbox" name="permisos[]" value="<?= $p['id'] ?>">
                                    <span title="<?= htmlspecialchars($p['description']) ?>">
                                        <?= htmlspecialchars($p['name']) ?>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="form-group text-right mt-2">
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
                                <th>Acciones</th>
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
                                        <td>
                                            <button class="btn btn-sm btn-info" onclick='abrirPermisos(<?= $u['id'] ?>, "<?= htmlspecialchars($u['username']) ?>")'>
                                                🛡️ Permisos
                                            </button>
                                            <button class="btn btn-sm btn-warning" onclick="abrirResetPassword(<?= $u['id'] ?>, '<?= htmlspecialchars($u['username']) ?>')">
                                                🔑 Clave
                                            </button>
                                            <form method="POST" onsubmit="return confirm('¿Está seguro de eliminar este usuario?');" style="display:inline;">
                                                <input type="hidden" name="action" value="eliminar">
                                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-danger" <?= ($u['id'] == $_SESSION['user_id']) ? 'disabled' : '' ?>>
                                                    🗑️
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Modal Password -->
            <div id="modal-password" style="display:none;" class="modal-overlay">
                <div class="modal-content">
                    <h3>Cambiar Contraseña</h3>
                    <p>Usuario: <strong id="modal-username"></strong></p>
                    <form method="POST">
                        <input type="hidden" name="action" value="reset_password">
                        <input type="hidden" name="user_id" id="modal-userid">
                        <div class="form-group">
                            <input type="password" name="new_password" required placeholder="Nueva contraseña" class="form-control">
                        </div>
                        <div class="flex gap-2 justify-end mt-4">
                            <button type="button" class="btn btn-secondary" onclick="document.getElementById('modal-password').style.display='none'">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Guardar</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Modal Editar Permisos -->
            <div id="modal-permisos" style="display:none;" class="modal-overlay">
                <div class="modal-content" style="width: 500px;">
                    <h3>Gestionar Permisos</h3>
                    <p>Usuario: <strong id="modal-perm-username"></strong></p>
                    <form method="POST">
                        <input type="hidden" name="action" value="update_permissions">
                        <input type="hidden" name="user_id" id="modal-perm-userid">
                        
                        <div class="checklist-container" id="modal-perm-list">
                            <?php foreach($permisosDisponibles as $p): ?>
                                <label class="check-item">
                                    <input type="checkbox" name="permisos[]" value="<?= $p['id'] ?>" id="perm-check-<?= $p['id'] ?>">
                                    <span title="<?= htmlspecialchars($p['description']) ?>">
                                        <?= htmlspecialchars($p['name']) ?>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>

                        <div class="flex gap-2 justify-end mt-4">
                            <button type="button" class="btn btn-secondary" onclick="document.getElementById('modal-permisos').style.display='none'">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Actualizar Permisos</button>
                        </div>
                    </form>
                </div>
            </div>

            <style>
                .modal-overlay {
                    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
                    background: rgba(0,0,0,0.5); z-index: 1000;
                    display: flex; justify-content: center; align-items: center;
                }
                .modal-content {
                    background: white; padding: 2rem; border-radius: 8px;
                    width: 400px; max-width: 90%;
                    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                    color: #333; /* Forzar texto oscuro en modal blanco */
                }
                
                /* Forzar color oscuro SOLO en elementos hijos del MODAL */
                .modal-content h3, 
                .modal-content p, 
                .modal-content strong,
                .modal-content label,
                .modal-content .checklist-container, /* Específico para checklist dentro de modal */
                .modal-content .check-item {
                    color: #333 !important;
                }
            </style>

            <script>
                // Data desde PHP
                const userPermissions = <?= json_encode($userPermissionsMap) ?>;

                function abrirResetPassword(id, username) {
                    document.getElementById('modal-userid').value = id;
                    document.getElementById('modal-username').textContent = username;
                    document.getElementById('modal-password').style.display = 'flex';
                }

                function abrirPermisos(id, username) {
                    document.getElementById('modal-perm-userid').value = id;
                    document.getElementById('modal-perm-username').textContent = username;
                    
                    // Resetear checkboxes
                    document.querySelectorAll('#modal-perm-list input[type="checkbox"]').forEach(cb => cb.checked = false);

                    // Cargar permisos actuales
                    if (userPermissions[id]) {
                        userPermissions[id].forEach(permId => {
                            const cb = document.getElementById('perm-check-' + permId);
                            if (cb) cb.checked = true;
                        });
                    }

                    document.getElementById('modal-permisos').style.display = 'flex';
                }
            </script>
        </div>
    </main>
</body>
</html>
