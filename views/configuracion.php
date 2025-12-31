<?php
require_once '../vendor/autoload.php';
require_once '../includes/auth_helper.php';

use App\SupabaseClient;
use App\Configuracion;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

requireLogin();
requireRole('admin');

$supabase = new SupabaseClient($_ENV['SUPABASE_URL'], $_ENV['SUPABASE_KEY']);
$configuracion = new Configuracion($supabase);
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
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración del Sistema</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <!-- Inject dynamic colors if set -->
    <?php if(!empty($datos['color_principal'])): ?>
    <style>
        :root {
            --primary: <?= htmlspecialchars($datos['color_principal']) ?>;
            --secondary: <?= htmlspecialchars($datos['color_secundario']) ?>;
        }
    </style>
    <?php endif; ?>
</head>
<body>
    <div class="dashboard-container">
        <?php include '../includes/sidebar.php'; ?>
        <?php include '../includes/header.php'; ?>
        
        <main class="main-content">
            <div class="container">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                    <h2>⚙️ Configuración del Sistema</h2>
                </div>
                
                <?php echo $mensaje; ?>
    
                <div class="card shadow fade-in">
                    <div class="card-body">
                        <form method="POST" action="configuracion.php">
                            <div class="mb-3">
                                <label for="nombre_institucion" class="form-label">Nombre de la Institución</label>
                                <input type="text" class="form-control" id="nombre_institucion" name="nombre_institucion" 
                                       value="<?php echo htmlspecialchars($datos['nombre_institucion'] ?? ''); ?>" required>
                                <small class="text-muted">Este nombre aparecerá en los reportes y encabezados.</small>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="color_principal" class="form-label">Color Principal</label>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <input type="color" class="form-control form-control-color" id="color_principal" name="color_principal" 
                                               value="<?php echo htmlspecialchars($datos['color_principal'] ?? '#0d6efd'); ?>" title="Elige tu color">
                                         <span>Color de botones y destacados</span>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="color_secundario" class="form-label">Color Secundario</label>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <input type="color" class="form-control form-control-color" id="color_secundario" name="color_secundario" 
                                               value="<?php echo htmlspecialchars($datos['color_secundario'] ?? '#6c757d'); ?>" title="Elige tu color">
                                        <span>Color de fondo y detalles laterales</span>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="logo_url" class="form-label">URL del Logo</label>
                                <input type="url" class="form-control" id="logo_url" name="logo_url" 
                                       value="<?php echo htmlspecialchars($datos['logo_url'] ?? ''); ?>" placeholder="https://ejemplo.com/logo.png">
                                <?php if(!empty($datos['logo_url'])): ?>
                                    <div class="mt-2">
                                        <p>Vista previa:</p>
                                        <img src="<?= htmlspecialchars($datos['logo_url']) ?>" alt="Logo actual" style="max-height: 50px;">
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div style="text-align: right; margin-top: 2rem;">
                                <button type="submit" class="btn btn-primary btn-lg">💾 Guardar Cambios</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
