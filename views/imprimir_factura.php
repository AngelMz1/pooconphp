<?php
require_once '../vendor/autoload.php';
require_once '../includes/auth_helper.php';

use App\SupabaseClient;
use App\Facturacion;
use App\Paciente;
use App\Configuracion;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

requireLogin();

$id = $_GET['id'] ?? null;
if (!$id) {
    die("ID de factura no válido");
}

try {
    $supabase = new SupabaseClient($_ENV['SUPABASE_URL'], $_ENV['SUPABASE_KEY']);
    
    // Obtener factura
    $facturaModel = new Facturacion($supabase);
    $factura = $facturaModel->obtenerFactura($id);
    
    if (!$factura) {
        throw new Exception("Factura no encontrada. Verifique que las tablas de facturación existan en la base de datos.");
    }
    
    // Obtener paciente
    $pacienteModel = new Paciente($supabase);
    $paciente = $pacienteModel->obtenerPorId($factura['paciente_id']);
    
    if (!$paciente) {
        throw new Exception("Paciente no encontrado");
    }
    
    // Obtener configuración de la institución
    $configModel = new Configuracion($supabase);
    $config = $configModel->obtenerConfiguracion();
    
} catch (Exception $e) {
    die("<div style='padding: 2rem; font-family: sans-serif;'>
        <h1 style='color: #dc3545;'>❌ Error</h1>
        <p>{$e->getMessage()}</p>
        <p><small>Si la tabla 'facturas' no existe, ejecute el script SQL de facturación en Supabase.</small></p>
        <a href='javascript:history.back()' style='color: #007bff;'>← Volver</a>
    </div>");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura #<?= str_pad($factura['id'], 6, '0', STR_PAD_LEFT) ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 14px; color: #333; background: #f5f5f5; }
        .invoice-box { 
            max-width: 800px; 
            margin: 20px auto; 
            padding: 30px; 
            background: white;
            border: 1px solid #eee; 
            box-shadow: 0 0 15px rgba(0, 0, 0, .1);
            border-radius: 8px;
        }
        .header { 
            display: flex; 
            justify-content: space-between; 
            align-items: flex-start;
            margin-bottom: 30px; 
            border-bottom: 3px solid <?= $config['color_principal'] ?? '#0d9488' ?>; 
            padding-bottom: 20px; 
        }
        .company-info h1 { 
            font-size: 24px; 
            color: <?= $config['color_principal'] ?? '#0d9488' ?>; 
            margin-bottom: 5px;
        }
        .company-info p { color: #666; font-size: 13px; }
        .invoice-title { text-align: right; }
        .invoice-title h2 { color: #333; font-size: 28px; margin-bottom: 10px; }
        .invoice-title .invoice-number { 
            background: <?= $config['color_principal'] ?? '#0d9488' ?>; 
            color: white; 
            padding: 5px 15px; 
            border-radius: 20px;
            font-size: 14px;
        }
        .invoice-title .date { color: #666; margin-top: 10px; }
        
        .client-info { 
            background: #f8f9fa; 
            padding: 20px; 
            border-radius: 8px; 
            margin-bottom: 30px; 
        }
        .client-info h3 { color: #333; margin-bottom: 10px; border-bottom: 1px solid #ddd; padding-bottom: 5px; }
        .client-info p { margin: 5px 0; }
        
        .table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .table th { 
            background: <?= $config['color_principal'] ?? '#0d9488' ?>; 
            color: white; 
            padding: 12px; 
            text-align: left; 
        }
        .table td { padding: 12px; border-bottom: 1px solid #eee; }
        .table tr:hover { background: #f9f9f9; }
        .table .text-right { text-align: right; }
        
        .totals { 
            margin-top: 30px; 
            text-align: right; 
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .totals .total-row { 
            display: flex; 
            justify-content: flex-end; 
            gap: 50px; 
            margin: 5px 0;
        }
        .totals .grand-total { 
            font-size: 24px; 
            font-weight: bold; 
            color: <?= $config['color_principal'] ?? '#0d9488' ?>; 
            border-top: 2px solid #ddd;
            padding-top: 10px;
            margin-top: 10px;
        }
        
        .footer { 
            margin-top: 40px; 
            text-align: center; 
            font-size: 12px; 
            color: #888; 
            border-top: 1px solid #eee;
            padding-top: 20px;
        }
        
        .no-print { 
            text-align: center; 
            margin-bottom: 20px; 
            padding: 15px;
            background: #fff;
        }
        .no-print button { 
            padding: 10px 25px; 
            cursor: pointer; 
            margin: 0 5px;
            border: none;
            border-radius: 5px;
            font-size: 14px;
        }
        .btn-print { background: <?= $config['color_principal'] ?? '#0d9488' ?>; color: white; }
        .btn-close { background: #6c757d; color: white; }
        
        @media print {
            .no-print { display: none; }
            .invoice-box { border: none; box-shadow: none; margin: 0; }
            body { background: white; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" class="btn-print">🖨️ Imprimir Factura</button>
        <button onclick="window.close()" class="btn-close">✖️ Cerrar</button>
    </div>

    <div class="invoice-box">
        <div class="header">
            <div class="company-info">
                <?php if (!empty($config['logo_url'])): ?>
                    <img src="<?= htmlspecialchars($config['logo_url']) ?>" style="height: 60px; margin-bottom: 10px;">
                <?php endif; ?>
                <h1><?= htmlspecialchars($config['nombre_institucion'] ?? 'Institución de Salud') ?></h1>
                <p><?= htmlspecialchars($config['direccion'] ?? '') ?></p>
                <p><?= htmlspecialchars($config['telefono'] ?? '') ?></p>
                <p>NIT: <?= htmlspecialchars($config['nit'] ?? 'N/A') ?></p>
            </div>
            <div class="invoice-title">
                <h2>FACTURA</h2>
                <span class="invoice-number">No. <?= str_pad($factura['id'], 6, '0', STR_PAD_LEFT) ?></span>
                <p class="date">Fecha: <?= date('d/m/Y', strtotime($factura['fecha'])) ?></p>
                <p class="date">Hora: <?= date('h:i A', strtotime($factura['fecha'])) ?></p>
            </div>
        </div>

        <div class="client-info">
            <h3>👤 Datos del Paciente</h3>
            <p><strong>Nombre:</strong> <?= htmlspecialchars($paciente['primer_nombre'] . ' ' . $paciente['primer_apellido']) ?></p>
            <p><strong>Documento:</strong> <?= htmlspecialchars($paciente['documento_id']) ?></p>
            <p><strong>Dirección:</strong> <?= htmlspecialchars($paciente['direccion'] ?? 'N/A') ?></p>
            <p><strong>Teléfono:</strong> <?= htmlspecialchars($paciente['telefono'] ?? 'N/A') ?></p>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th style="width: 50%;">Descripción</th>
                    <th class="text-right" style="width: 15%;">Cantidad</th>
                    <th class="text-right" style="width: 17%;">Precio Unit.</th>
                    <th class="text-right" style="width: 18%;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($factura['items'])): ?>
                    <?php foreach ($factura['items'] as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['concepto'] ?? 'Concepto') ?></td>
                        <td class="text-right"><?= $item['cantidad'] ?? 1 ?></td>
                        <td class="text-right">$<?= number_format($item['precio_unitario'] ?? 0, 2, ',', '.') ?></td>
                        <td class="text-right">$<?= number_format($item['subtotal'] ?? 0, 2, ',', '.') ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4" style="text-align: center; color: #888;">No hay items en esta factura</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="totals">
            <div class="total-row">
                <span>Subtotal:</span>
                <span>$<?= number_format($factura['total'] ?? 0, 2, ',', '.') ?></span>
            </div>
            <div class="total-row grand-total">
                <span>TOTAL A PAGAR:</span>
                <span>$<?= number_format($factura['total'] ?? 0, 2, ',', '.') ?></span>
            </div>
        </div>
        
        <?php if (!empty($factura['observaciones'])): ?>
        <div style="margin-top: 20px; padding: 15px; background: #fff3cd; border-radius: 5px;">
            <strong>Observaciones:</strong> <?= htmlspecialchars($factura['observaciones']) ?>
        </div>
        <?php endif; ?>

        <div class="footer">
            <p>Gracias por su confianza</p>
            <p>Generado por Sistema de Gestión Médica - <?= date('d/m/Y H:i') ?></p>
        </div>
    </div>
</body>
</html>
