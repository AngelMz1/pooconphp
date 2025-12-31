<?php
require_once '../vendor/autoload.php';
require_once '../includes/auth_helper.php';

use App\SupabaseClient;
use App\Tarifario;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

requireLogin();
requireRole('admin');

$supabase = new SupabaseClient($_ENV['SUPABASE_URL'], $_ENV['SUPABASE_KEY']);
$tarifarioModel = new Tarifario($supabase);
$mensaje = '';

// Procesar Acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'crear') {
            $codigo = $_POST['codigo'] ?? '';
            $nombre = $_POST['nombre_servicio'] ?? '';
            $precio = $_POST['precio'] ?? 0;
            $desc = $_POST['descripcion'] ?? '';
            
            $res = $tarifarioModel->crearServicio($codigo, $nombre, $precio, $desc);
            if ($res) {
                 $mensaje = "<div class='alert alert-success'>Servicio agregado correctamente.</div>";
            }
        } elseif ($action === 'toggle') {
            $id = $_POST['id'] ?? 0;
            $currentStatus = $_POST['current_status'] ?? 'f';
            $newStatus = ($currentStatus == 't' || $currentStatus == '1' || $currentStatus === true) ? false : true;
            
            $tarifarioModel->actualizarServicio($id, ['activo' => $newStatus]);
             $mensaje = "<div class='alert alert-success'>Estado actualizado.</div>";
        }
    } catch (Exception $e) {
        $mensaje = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    }
}

// Obtener Lista
try {
    // Listar todos (activo e inactivo)
    // El método listarServicios por defecto trae activos.
    // Modificamos Tarifario.php si es necesario o usamos select directo para admin
    $servicios = $supabase->select('tarifarios', '*', '', 'codigo.asc');
} catch (Exception $e) {
    $servicios = [];
    $mensaje = "<div class='alert alert-danger'>Error al cargar tarifarios: " . $e->getMessage() . "</div>";
}
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Tarifarios</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <div class="dashboard-container">
        <?php include '../includes/sidebar.php'; ?>
        <?php include '../includes/header.php'; ?>
        
        <main class="main-content">
            <div class="container">
                <h2>Gestión de Tarifarios</h2>
                <?php echo $mensaje; ?>

    <div class="card shadow mb-4">
        <div class="card-header">
            <h4>Nuevo Servicio</h4>
        </div>
        <div class="card-body">
            <form method="POST" action="gestionar_tarifarios.php" class="row g-3">
                <input type="hidden" name="action" value="crear">
                <div class="col-md-3">
                    <label class="form-label">Código</label>
                    <input type="text" name="codigo" class="form-control" required>
                </div>
                <div class="col-md-5">
                    <label class="form-label">Nombre del Servicio</label>
                    <input type="text" name="nombre_servicio" class="form-control" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Precio</label>
                    <input type="number" step="0.01" name="precio" class="form-control" required>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Agregar</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow">
        <div class="card-header">
            <h4>Listado de Servicios</h4>
        </div>
        <div class="card-body">
            <div class="table-container">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Servicio</th>
                            <th>Precio</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($servicios)): ?>
                            <?php foreach ($servicios as $s): 
                                $isActivo = ($s['activo'] === true || $s['activo'] === 't' || $s['activo'] === 1);
                            ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($s['codigo']); ?></strong></td>
                                    <td>
                                        <?php echo htmlspecialchars($s['nombre_servicio']); ?>
                                        <?php if(!empty($s['descripcion'])): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($s['descripcion']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>$<?php echo number_format($s['precio'], 2); ?></td>
                                    <td>
                                        <span class="badge <?php echo $isActivo ? 'badge-success' : 'badge-danger'; ?>">
                                            <?php echo $isActivo ? 'Activo' : 'Inactivo'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="action" value="toggle">
                                            <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                            <input type="hidden" name="current_status" value="<?= $isActivo ? 't' : 'f' ?>">
                                            <?php if($isActivo): ?>
                                                <button type="submit" class="btn btn-sm btn-danger" title="Desactivar">🚫</button>
                                            <?php else: ?>
                                                <button type="submit" class="btn btn-sm btn-success" title="Activar">✅</button>
                                            <?php endif; ?>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center">No hay servicios registrados.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
