require_once '../src/Configuracion.php';
require_once '../includes/header.php';

use App\Configuracion;

// Verificar permisos de admin
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$configuracion = new Configuracion();
$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre_institucion'] ?? '';
    $color_p = $_POST['color_principal'] ?? '#0d6efd';
    $color_s = $_POST['color_secundario'] ?? '#6c757d';
    $logo = $_POST['logo_url'] ?? '';

    if ($configuracion->actualizarConfiguracion($nombre, $color_p, $color_s, $logo)) {
        $mensaje = "<div class='alert alert-success'>Configuración actualizada correctamente.</div>";
    } else {
        $mensaje = "<div class='alert alert-danger'>Error al actualizar la configuración.</div>";
    }
}

$datos = $configuracion->obtenerConfiguracion();
?>

<div class="container mt-4">
    <h2>Configuración del Sistema</h2>
    <?php echo $mensaje; ?>
    
    <div class="card shadow">
        <div class="card-body">
            <form method="POST" action="configuracion.php">
                <div class="mb-3">
                    <label for="nombre_institucion" class="form-label">Nombre de la Institución</label>
                    <input type="text" class="form-control" id="nombre_institucion" name="nombre_institucion" 
                           value="<?php echo htmlspecialchars($datos['nombre_institucion'] ?? ''); ?>" required>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="color_principal" class="form-label">Color Principal</label>
                        <input type="color" class="form-control form-control-color" id="color_principal" name="color_principal" 
                               value="<?php echo htmlspecialchars($datos['color_principal'] ?? '#0d6efd'); ?>" title="Elige tu color">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="color_secundario" class="form-label">Color Secundario</label>
                        <input type="color" class="form-control form-control-color" id="color_secundario" name="color_secundario" 
                               value="<?php echo htmlspecialchars($datos['color_secundario'] ?? '#6c757d'); ?>" title="Elige tu color">
                    </div>
                </div>

                <div class="mb-3">
                    <label for="logo_url" class="form-label">URL del Logo (Opcional)</label>
                    <input type="url" class="form-control" id="logo_url" name="logo_url" 
                           value="<?php echo htmlspecialchars($datos['logo_url'] ?? ''); ?>">
                </div>

                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
