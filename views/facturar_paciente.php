require_once '../src/Facturacion.php';
require_once '../src/Tarifario.php';
require_once '../src/Paciente.php';
require_once '../includes/header.php';

use App\Facturacion;
use App\Tarifario;
use App\Paciente;

$pacienteId = $_GET['paciente_id'] ?? null;
$consultaId = $_GET['consulta_id'] ?? null;

if (!$pacienteId) {
    echo "<div class='container mt-5 alert alert-danger'>ID de paciente no especificado.</div>";
    exit;
}

$pacienteModel = new Paciente();
$paciente = $pacienteModel->obtenerPaciente($pacienteId);

$tarifarioModel = new Tarifario();
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
        $facturaModel = new Facturacion();
        $facturaId = $facturaModel->crearFactura($pacienteId, $consultaId, $items);
        if ($facturaId) {
            // Redirect to Print Invoice
            echo "<script>window.location.href = 'imprimir_factura.php?id=$facturaId';</script>";
            exit;
        } else {
            $mensaje = "<div class='alert alert-danger'>Error al generar la factura.</div>";
        }
    } else {
        $mensaje = "<div class='alert alert-warning'>Debe seleccionar al menos un servicio.</div>";
    }
}
?>

<div class="container mt-4">
    <h2>Facturación de Paciente</h2>
    <div class="card mb-4">
        <div class="card-body">
            <h5>Paciente: <?php echo htmlspecialchars($paciente['primer_nombre'] . ' ' . $paciente['primer_apellido']); ?></h5>
            <p>Documento: <?php echo htmlspecialchars($paciente['documento_id']); ?></p>
        </div>
    </div>

    <?php echo $mensaje; ?>

    <form method="POST">
        <div class="card shadow">
            <div class="card-header">
                <h4>Detalle de Factura</h4>
            </div>
            <div class="card-body">
                <div id="items-container">
                    <div class="row mb-2 item-row">
                        <div class="col-md-8">
                            <label>Servicio</label>
                            <select name="servicios[]" class="form-control">
                                <option value="">Seleccione un servicio...</option>
                                <?php foreach ($servicios as $svc): ?>
                                    <option value="<?php echo $svc['id']; ?>">
                                        <?php echo htmlspecialchars($svc['nombre_servicio']) . " - $" . number_format($svc['precio'], 2); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                             <label>Cantidad</label>
                             <input type="number" value="1" class="form-control" readonly>
                        </div>
                    </div>
                </div>
                
                <button type="button" class="btn btn-secondary btn-sm mt-2" onclick="agregarFila()">+ Agregar Item</button>

                <div class="mt-4 text-right">
                    <button type="submit" class="btn btn-success btn-lg">Generar Factura</button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function agregarFila() {
    // Clone first row logic simple for MVP
    const container = document.getElementById('items-container');
    const firstRow = container.querySelector('.item-row');
    const newRow = firstRow.cloneNode(true);
    // clean value
    newRow.querySelector('select').value = "";
    container.appendChild(newRow);
}
</script>

<?php require_once '../includes/footer.php'; ?>
