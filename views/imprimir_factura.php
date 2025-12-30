require_once '../vendor/autoload.php';
require_once '../src/Facturacion.php';
require_once '../src/Paciente.php';
require_once '../src/Configuracion.php';
require_once '../includes/auth_helper.php';

$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

use App\Facturacion;
use App\Paciente;
use App\Configuracion;

session_start();
requireLogin();

$id = $_GET['id'] ?? null;
if (!$id) die("ID de factura no válido");

$facturaModel = new Facturacion();
$factura = $facturaModel->obtenerFactura($id);

if (!$factura) die("Factura no encontrada");

$pacienteModel = new Paciente();
$paciente = $pacienteModel->obtenerPaciente($factura['paciente_id']);

$configModel = new Configuracion();
$config = $configModel->obtenerConfiguracion();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura #<?php echo str_pad($factura['id'], 6, '0', STR_PAD_LEFT); ?></title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 14px; color: #333; }
        .invoice-box { max-width: 800px; margin: auto; padding: 30px; border: 1px solid #eee; box-shadow: 0 0 10px rgba(0, 0, 0, .15); }
        .header { margin-bottom: 20px; border-bottom: 2px solid #eee; padding-bottom: 20px; }
        .company-name { font-size: 24px; font-weight: bold; color: <?php echo $config['color_principal']; ?>; }
        .invoice-details { text-align: right; }
        .table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .table th { background-color: #f9f9f9; }
        .total { font-size: 18px; font-weight: bold; text-align: right; margin-top: 20px; }
        @media print {
            .no-print { display: none; }
            .invoice-box { border: none; box-shadow: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align: center; margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; cursor: pointer;">Imprimir Factura</button>
        <button onclick="window.close()" style="padding: 10px 20px; cursor: pointer;">Cerrar</button>
    </div>

    <div class="invoice-box">
        <div class="header">
            <table style="width: 100%; border: none;">
                <tr>
                    <td style="border: none;">
                         <?php if (!empty($config['logo_url'])): ?>
                            <img src="<?php echo htmlspecialchars($config['logo_url']); ?>" style="height: 50px;">
                        <?php endif; ?>
                        <div class="company-name"><?php echo htmlspecialchars($config['nombre_institucion']); ?></div>
                    </td>
                    <td style="border: none; text-align: right;">
                        <h2>FACTURA DE VENTA</h2>
                        <p>No. <?php echo str_pad($factura['id'], 6, '0', STR_PAD_LEFT); ?></p>
                        <p>Fecha: <?php echo date('d/m/Y', strtotime($factura['fecha'])); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <div class="client-info">
            <strong>Paciente:</strong> <?php echo htmlspecialchars($paciente['primer_nombre'] . ' ' . $paciente['primer_apellido']); ?><br>
            <strong>Documento:</strong> <?php echo htmlspecialchars($paciente['documento_id']); ?><br>
            <strong>Dirección:</strong> <?php echo htmlspecialchars($paciente['direccion'] ?? 'N/A'); ?>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>Descripción</th>
                    <th style="width: 80px;">Cant.</th>
                    <th style="width: 120px;">Precio Unit.</th>
                    <th style="width: 120px;">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($factura['items'] as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['concepto']); ?></td>
                    <td><?php echo $item['cantidad']; ?></td>
                    <td>$<?php echo number_format($item['precio_unitario'], 2); ?></td>
                    <td>$<?php echo number_format($item['subtotal'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="total">
            Total a Pagar: $<?php echo number_format($factura['total'], 2); ?>
        </div>
        
        <div style="margin-top: 40px; font-size: 12px; color: #777;">
            <p>Generado por Sistema de Gestión Médica</p>
        </div>
    </div>
</body>
</html>
