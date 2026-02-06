<?php
require_once '../vendor/autoload.php';
require_once '../includes/auth_helper.php';

use App\SupabaseClient;
use App\Facturacion;
use App\Tarifario;
use App\Paciente;
use Dotenv\Dotenv;

// Cargar configuración
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

requirePermission('generar_factura'); // Verificar permiso para generar facturas

$supabase = new SupabaseClient($_ENV['SUPABASE_URL'], $_ENV['SUPABASE_KEY']);

$pacienteId = $_GET['paciente_id'] ?? null;
$consultaId = $_GET['consulta_id'] ?? null;

if (!$pacienteId) {
    echo "<div class='container mt-5 alert alert-danger'>ID de paciente no especificado.</div>";
    exit;
}

$pacienteModel = new Paciente($supabase);
$paciente = $pacienteModel->obtenerPorId($pacienteId);

if (!$paciente) {
    echo "<div class='container mt-5 alert alert-danger'>Paciente no encontrado.</div>";
    exit;
}

$tarifarioModel = new Tarifario($supabase);
$servicios = $tarifarioModel->listarServicios(true);

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Process Billing
    $items = [];
    $total = 0;
    
    // Simple handling of one item for MVP, ideally this is a dynamic JS list
    if (isset($_POST['servicios'])) {
        foreach ($_POST['servicios'] as $key => $servicioId) {
            if ($servicioId) {
                // Find service details
                $svc = null;
                foreach($servicios as $s) { if ($s['id'] == $servicioId) $svc = $s; }
                
                if ($svc) {
                    $items[] = [
                        'tarifario_id' => $svc['id'],
                        'concepto' => $svc['nombre_servicio'],
                        'cantidad' => 1,
                        'precio_unitario' => $svc['precio'],
                        'subtotal' => $svc['precio']
                    ];
                }
            }
        }
    }

    if (!empty($items)) {
        $facturaModel = new Facturacion($supabase);
        $facturaId = $facturaModel->crearFactura($pacienteId, $consultaId, $items);
        if ($facturaId) {
            // Redirect to Print Invoice
            echo "<script>window.location.href = 'imprimir_factura.php?id=$facturaId';</script>";
            exit;
        } else {
            $mensaje = "<div class='alert alert-danger'>Error al generar la factura. Verifique que las tablas de facturación existan.</div>";
        }
    } else {
        $mensaje = "<div class='alert alert-warning'>Debe seleccionar al menos un servicio.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facturación - Sistema Médico</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body class="dashboard-container">
    <?php include '../includes/sidebar.php'; ?>
    <?php include '../includes/header.php'; ?>
    
    <main class="main-content">
        <div class="container">
            <h1>💳 Facturación de Paciente</h1>
            
            <div class="card mb-4">
                <h3>Paciente</h3>
                <p class="big-text"><?= htmlspecialchars($paciente['primer_nombre'] . ' ' . $paciente['primer_apellido']) ?></p>
                <p>🆔 Documento: <?= htmlspecialchars($paciente['documento_id']) ?></p>
            </div>

            <?= $mensaje ?>

            <form method="POST">
                <div class="card">
                    <h3>📋 Detalle de Factura</h3>
                    
                    <div id="items-container">
                        <div class="item-row mb-2" style="background: var(--gray-100); padding: 1rem; border-radius: 8px;">
                            <div class="form-group">
                                <label>Servicio</label>
                                <select name="servicios[]" class="form-control" required>
                                    <option value="">Seleccione un servicio...</option>
                                    <?php foreach ($servicios as $svc): ?>
                                        <option value="<?= $svc['id'] ?>">
                                            <?= htmlspecialchars($svc['nombre_servicio']) ?> - $<?= number_format($svc['precio'], 2) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <button type="button" class="btn btn-secondary btn-sm mt-2" onclick="agregarFila()">+ Agregar Item</button>

                    <div class="mt-4 text-right" style="border-top: 1px solid var(--gray-200); padding-top: 1rem;">
                        <a href="ver_paciente.php?id=<?= $pacienteId ?>" class="btn btn-outline">Cancelar</a>
                        <button type="submit" class="btn btn-success btn-lg">Generar Factura 🧾</button>
                    </div>
                </div>
            </form>
        </div>
    </main>

<script>
function agregarFila() {
    const container = document.getElementById('items-container');
    const firstRow = container.querySelector('.item-row');
    const newRow = firstRow.cloneNode(true);
    newRow.querySelector('select').value = "";
    container.appendChild(newRow);
}
</script>
</body>
</html>
