<?php
require_once '../vendor/autoload.php';
require_once '../includes/auth_helper.php';

requirePermission('ver_facturas'); // Verificar permiso para imprimir facturas

use App\DatabaseFactory;
use App\Facturacion;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
try {
    $dotenv->safeLoad();
} catch (Exception $e) { }

$db = DatabaseFactory::create();
$facturacionModel = new Facturacion($db);

$facturaId = $_GET['id'] ?? null;

if (!$facturaId) {
    die("ID de factura no especificado");
}

$factura = $facturacionModel->obtenerFactura($facturaId);

if (!$factura) {
    die("Factura no encontrada");
}

// Obtener datos del paciente
$pacienteData = $db->select('pacientes', '*', "id_paciente=eq.{$factura['paciente_id']}");
$paciente = $pacienteData[0] ?? null;

// Obtener EPS
$eps = null;
if (!empty($paciente['eps_id'])) {
    $epsData = $db->select('eps', 'nombre_eps,regimen', "id=eq.{$paciente['eps_id']}");
    $eps = $epsData[0] ?? null;
}

// Obtener médico si hay consulta asociada
$medico = null;
if (!empty($factura['consulta_id'])) {
    $consultaData = $db->select('consultas', 'medico_id', "id_consulta=eq.{$factura['consulta_id']}");
    if (!empty($consultaData)) {
        $medicoData = $db->select('medicos', 'num_licencia', "id=eq.{$consultaData[0]['medico_id']}");
        $medico = $medicoData[0] ?? null;
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura #<?= str_pad($factura['id'], 6, '0', STR_PAD_LEFT) ?></title>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { margin: 0; }
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            font-size: 12pt;
            line-height: 1.4;
            padding: 20px;
        }
        
        .factura-container {
            max-width: 800px;
            margin: 0 auto;
            border: 2px solid #000;
            padding: 30px;
        }
        
        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        
        .header h1 {
            font-size: 24pt;
            margin-bottom: 5px;
        }
        
        .header .nit {
            font-weight: bold;
            margin: 5px 0;
        }
        
        .factura-numero {
            background: #000;
            color: #fff;
            padding: 10px;
            text-align: center;
            font-size: 16pt;
            font-weight: bold;
            margin: 20px 0;
        }
        
        .info-section {
            margin: 20px 0;
        }
        
        .info-section h3 {
            background: #f0f0f0;
            padding: 8px;
            border: 1px solid #000;
            margin-bottom: 10px;
        }
        
        .info-row {
            display: flex;
            padding: 5px 0;
            border-bottom: 1px dotted #ccc;
        }
        
        .info-label {
            font-weight: bold;
            width: 150px;
        }
        
        .tabla-items {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        .tabla-items th {
            background: #000;
            color: #fff;
            padding: 10px;
            text-align: left;
            border: 1px solid #000;
        }
        
        .tabla-items td {
            padding: 8px;
            border: 1px solid #000;
        }
        
        .tabla-items tfoot td {
            font-weight: bold;
        }
        
        .totales {
            margin-left: auto;
            width: 300px;
        }
        
        .total-final {
            background: #000;
            color: #fff;
            font-size: 14pt;
            padding: 12px;
        }
        
        .footer {
            margin-top: 40px;
            border-top: 2px solid #000;
            padding-top: 20px;
            text-align: center;
            font-size: 9pt;
        }
        
        .firma-linea {
            border-top: 1px solid #000;
            width: 300px;
            margin: 50px auto 10px auto;
        }
        
        .print-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 24px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14pt;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        
        .print-btn:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <button class="print-btn no-print" onclick="window.print()">🖨️ Imprimir</button>
    
    <div class="factura-container">
        <!-- Header -->
        <div class="header">
            <h1>CLÍNICA MÉDICA POO PHP</h1>
            <div class="nit">NIT: 900.123.456-7</div>
            <div>RÉGIMEN SIMPLIFICADO</div>
            <div>Dirección: Calle 123 #45-67, Bogotá, Colombia</div>
            <div>Teléfono: (601) 123-4567</div>
        </div>

        <!-- Número de Factura -->
        <div class="factura-numero">
            FACTURA DE VENTA No. <?= str_pad($factura['id'], 6, '0', STR_PAD_LEFT) ?>
        </div>

        <!-- Información de Fecha y Estado -->
        <div class="info-section">
            <div class="info-row">
                <span class="info-label">Fecha de Emisión:</span>
                <span><?= date('d/m/Y H:i', strtotime($factura['fecha'])) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Estado:</span>
                <span style="text-transform: uppercase; font-weight: bold; color: <?= $factura['estado'] === 'pagada' ? 'green' : ($factura['estado'] === 'anulada' ? 'red' : 'orange') ?>">
                    <?= $factura['estado'] ?>
                </span>
            </div>
            <?php if ($factura['estado'] === 'pagada' && !empty($factura['fecha_pago'])): ?>
                <div class="info-row">
                    <span class="info-label">Fecha de Pago:</span>
                    <span><?= date('d/m/Y H:i', strtotime($factura['fecha_pago'])) ?></span>
                </div>
            <?php endif; ?>
        </div>

        <!-- Datos del Paciente -->
        <div class="info-section">
            <h3>DATOS DEL PACIENTE</h3>
            <div class="info-row">
                <span class="info-label">Nombre Completo:</span>
                <span><?= htmlspecialchars($paciente['primer_nombre'] . ' ' . ($paciente['segundo_nombre'] ?? '') . ' ' . $paciente['primer_apellido'] . ' ' . ($paciente['segundo_apellido'] ?? '')) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Documento:</span>
                <span><?= htmlspecialchars($paciente['documento_id']) ?></span>
            </div>
            <?php if ($eps): ?>
                <div class="info-row">
                    <span class="info-label">EPS:</span>
                    <span><?= htmlspecialchars($eps['nombre_eps']) ?> - <?= htmlspecialchars($eps['regimen']) ?></span>
                </div>
            <?php else: ?>
                <div class="info-row">
                    <span class="info-label">Tipo:</span>
                    <span><strong>PARTICULAR</strong></span>
                </div>
            <?php endif; ?>
        </div>

        <!-- Detalle de Servicios -->
        <table class="tabla-items">
            <thead>
                <tr>
                    <th>Descripción</th>
                    <th style="text-align: center; width: 80px;">Cant.</th>
                    <th style="text-align: right; width: 120px;">Valor Unit.</th>
                    <th style="text-align: right; width: 120px;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($factura['items'] as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['concepto']) ?></td>
                        <td style="text-align: center;"><?= $item['cantidad'] ?></td>
                        <td style="text-align: right;">$<?= number_format($item['precio_unitario'], 0, ',', '.') ?></td>
                        <td style="text-align: right;">$<?= number_format($item['subtotal'], 0, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Totales -->
        <div class="totales">
            <table style="width: 100%; border-collapse: collapse;">
                <?php if ($factura['subtotal'] > 0): ?>
                    <tr>
                        <td style="padding: 8px; text-align: right;">SUBTOTAL:</td>
                        <td style="padding: 8px; text-align: right; font-weight: bold;">$<?= number_format($factura['subtotal'], 0, ',', '.') ?></td>
                    </tr>
                <?php endif; ?>
                
                <?php if ($factura['copago'] > 0): ?>
                    <tr>
                        <td style="padding: 8px; text-align: right;">Copago EPS (<?= number_format(($factura['copago'] / $factura['subtotal']) * 100, 0) ?>%):</td>
                        <td style="padding: 8px; text-align: right; color: green;">-$<?= number_format($factura['copago'], 0, ',', '.') ?></td>
                    </tr>
                <?php endif; ?>
                
                <?php if ($factura['descuento'] > 0): ?>
                    <tr>
                        <td style="padding: 8px; text-align: right;">Descuento:</td>
                        <td style="padding: 8px; text-align: right; color: green;">-$<?= number_format($factura['descuento'], 0, ',', '.') ?></td>
                    </tr>
                <?php endif; ?>
                
                <tr>
                    <td colspan="2" class="total-final" style="text-align: right;">
                        TOTAL A PAGAR: $<?= number_format($factura['total'], 0, ',', '.') ?>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Información de Pago -->
        <?php if ($factura['estado'] === 'pagada' && !empty($factura['metodo_pago'])): ?>
            <div class="info-section">
                <h3>INFORMACIÓN DEL PAGO</h3>
                <div class="info-row">
                    <span class="info-label">Método de Pago:</span>
                    <span><?= htmlspecialchars($factura['metodo_pago']) ?></span>
                </div>
                <?php if (!empty($factura['referencia_pago'])): ?>
                    <div class="info-row">
                        <span class="info-label">Referencia:</span>
                        <span><?= htmlspecialchars($factura['referencia_pago']) ?></span>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Médico Tratante -->
        <?php if ($medico): ?>
            <div class="info-section">
                <div class="info-row">
                    <span class="info-label">Médico Tratante:</span>
                    <span>Registro Médico: <?= htmlspecialchars($medico['num_licencia']) ?></span>
                </div>
            </div>
        <?php endif; ?>

        <!-- Firma -->
        <div style="margin-top: 60px;">
            <div class="firma-linea"></div>
            <div style="text-align: center; font-weight: bold;">Firma del Paciente</div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p><strong>Sistema de Gestión Médica</strong></p>
            <p>Este documento es una representación fiel de la transacción realizada.</p>
            <p style="margin-top: 10px; font-size: 8pt;">
                "No somos responsables cuando la mercancía sale de nuestro local"
            </p>
        </div>
    </div>
</body>
</html>
