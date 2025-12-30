require_once '../src/Tarifario.php';
require_once '../includes/header.php';

use App\Tarifario;

// Check Admin
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    echo "<div class='container mt-5 alert alert-danger'>Acceso denegado.</div>";
    require_once '../includes/footer.php';
    exit;
}

$tarifario = new Tarifario();
$mensaje = '';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'crear') {
        $codigo = $_POST['codigo'];
        $nombre = $_POST['nombre_servicio'];
        $precio = $_POST['precio'];
        
        if ($tarifario->crearServicio($codigo, $nombre, $precio)) {
            $mensaje = "<div class='alert alert-success'>Servicio creado correctamente.</div>";
        } else {
            $mensaje = "<div class='alert alert-danger'>Error al crear servicio. Verifique el código.</div>";
        }
    }
}

$servicios = $tarifario->listarServicios(false); // List all active and inactive
?>

<div class="container mt-4">
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
                            <?php foreach ($servicios as $s): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($s['codigo']); ?></td>
                                    <td><?php echo htmlspecialchars($s['nombre_servicio']); ?></td>
                                    <td>$<?php echo number_format($s['precio'], 2); ?></td>
                                    <td>
                                        <span class="badge <?php echo $s['activo'] === 't' ? 'badge-success' : 'badge-danger'; ?>">
                                            <?php echo $s['activo'] === 't' ? 'Activo' : 'Inactivo'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-secondary">Editar</button>
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

<?php require_once '../includes/footer.php'; ?>
