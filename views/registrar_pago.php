<?php
require_once '../vendor/autoload.php';
require_once '../includes/auth_helper.php';

requirePermission('registrar_pago'); // Verificar permiso para registrar pagos

use App\DatabaseFactory;
use App\Facturacion;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
try {
    $dotenv->safeLoad();
} catch (Exception $e) { }

$db = DatabaseFactory::create();
$facturacionModel = new Facturacion($db);

$facturaId = $_GET['factura_id'] ?? null;
$mensaje = '';
$error = '';

if (!$facturaId) {
    header("Location: listar_facturas.php");
    exit;
}

// Obtener factura
$factura = $facturacionModel->obtenerFactura($facturaId);

if (!$factura) {
    die("Factura no encontrada");
}

// Procesar pago
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $datosPago = [
            'monto' => floatval($_POST['monto']),
            'metodo_pago' => $_POST['metodo_pago'],
            'referencia' => $_POST['referencia'] ?? null,
            'observaciones' => $_POST['observaciones'] ?? '',
            'usuario_id' => $_SESSION['user_id'] ?? null
        ];

        // Validar monto
        if ($datosPago['monto'] <= 0) {
            throw new Exception("El monto debe ser mayor a cero");
        }

        if ($datosPago['monto'] > $factura['total']) {
            throw new Exception("El monto no puede ser mayor al total de la factura");
        }

        // Registrar pago
        $resultado = $facturacionModel->registrarPago($facturaId, $datosPago);

        if ($resultado) {
            $mensaje = "Pago registrado exitosamente";
            // Recargar factura para mostrar nuevo estado
            $factura = $facturacionModel->obtenerFactura($facturaId);
        } else {
            $error = "Error al registrar el pago";
        }

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Calcular total pagado
$totalPagado = 0;
if (!empty($factura['pagos'])) {
    foreach ($factura['pagos'] as $pago) {
        $totalPagado += $pago['monto'];
    }
}

$pendiente = $factura['total'] - $totalPagado;

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Pago - Sistema Médico</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body class="dashboard-container">
    <?php include '../includes/sidebar.php'; ?>
    <?php include '../includes/header.php'; ?>
    
    <main class="main-content">
        <div class="container" style="max-width: 800px;">
            <div class="card">
                <h1>💳 Registrar Pago</h1>

                <?php if ($mensaje): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($mensaje) ?></div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <!-- Información de la factura -->
                <div style="padding: 1rem; background: var(--gray-50); border-radius: 8px; margin-bottom: 1.5rem;">
                    <div class="grid grid-2">
                        <div>
                            <strong>Factura No.:</strong> #<?= str_pad($factura['id'], 6, '0', STR_PAD_LEFT) ?>
                        </div>
                        <div>
                            <strong>Fecha:</strong> <?= date('d/m/Y', strtotime($factura['fecha'])) ?>
                        </div>
                        <div>
                            <strong>Total Factura:</strong> 
                            <span style="font-size: 1.25rem; color: var(--primary);">$<?= number_format($factura['total'], 0, ',', '.') ?></span>
                        </div>
                        <div>
                            <strong>Total Pagado:</strong> 
                            <span style="font-size: 1.25rem; color: var(--success);">$<?= number_format($totalPagado, 0, ',', '.') ?></span>
                        </div>
                    </div>
                    
                    <div style="margin-top: 1rem; padding: 1rem; background: <?= $pendiente > 0 ? '#fff3cd' : '#d4edda' ?>; border-radius: 4px;">
                        <strong>Saldo Pendiente:</strong> 
                        <span style="font-size: 1.5rem; font-weight: 600;">$<?= number_format($pendiente, 0, ',', '.') ?></span>
                    </div>
                </div>

                <?php if ($pendiente > 0): ?>
                    <!-- Formulario de pago -->
                    <form method="POST">
                        <div class="form-group">
                            <label for="monto">Monto a Pagar *</label>
                            <input type="number" 
                                   id="monto" 
                                   name="monto" 
                                   step="0.01" 
                                   value="<?= $pendiente ?>"
                                   max="<?= $pendiente ?>"
                                   required>
                            <small class="form-hint">Máximo: $<?= number_format($pendiente, 0, ',', '.') ?></small>
                        </div>

                        <div class="form-group">
                            <label for="metodo_pago">Método de Pago *</label>
                            <select id="metodo_pago" name="metodo_pago" required>
                                <option value="">Seleccione...</option>
                                <option value="Efectivo">💵 Efectivo</option>
                                <option value="Tarjeta Débito">💳 Tarjeta Débito</option>
                                <option value="Tarjeta Crédito">💳 Tarjeta Crédito</option>
                                <option value="Transferencia">🏦 Transferencia Bancaria</option>
                                <option value="Datafono">📱 Datafono</option>
                                <option value="Nequi">📲 Nequi</option>
                                <option value="Daviplata">📲 Daviplata</option>
                                <option value="Otro">Otro</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="referencia">Referencia / No. Transacción</label>
                            <input type="text" 
                                   id="referencia" 
                                   name="referencia" 
                                   placeholder="Ej: Voucher 12345, Ref. Bancaria, etc.">
                            <small class="form-hint">Opcional, pero recomendado para auditoría</small>
                        </div>

                        <div class="form-group">
                            <label for="observaciones">Observaciones</label>
                            <textarea id="observaciones" 
                                      name="observaciones" 
                                      rows="2" 
                                      placeholder="Comentarios adicionales..."></textarea>
                        </div>

                        <div class="mt-4 text-right">
                            <a href="resumen_facturacion.php?factura_id=<?= $facturaId ?>" class="btn btn-secondary">
                                Cancelar
                            </a>
                            <button type="submit" class="btn btn-success btn-lg">
                                💾 Registrar Pago
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="alert alert-success">
                        <h3>✅ Factura Completamente Pagada</h3>
                        <p>Esta factura ya ha sido pagada en su totalidad.</p>
                    </div>

                    <div class="mt-4 text-center">
                        <a href="imprimir_factura.php?id=<?= $facturaId ?>" class="btn btn-primary" target="_blank">
                            🖨️ Imprimir Factura
                        </a>
                        <a href="listar_facturas.php" class="btn btn-secondary">
                            📋 Ver Todas las Facturas
                        </a>
                    </div>
                <?php endif; ?>

                <!-- Historial de pagos -->
                <?php if (!empty($factura['pagos'])): ?>
                    <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 2px solid var(--gray-200);">
                        <h3>📜 Historial de Pagos</h3>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Monto</th>
                                    <th>Método</th>
                                    <th>Referencia</th>
                                    <th>Observaciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($factura['pagos'] as $pago): ?>
                                    <tr>
                                        <td><?= date('d/m/Y H:i', strtotime($pago['fecha_pago'])) ?></td>
                                        <td><strong>$<?= number_format($pago['monto'], 0, ',', '.') ?></strong></td>
                                        <td><?= htmlspecialchars($pago['metodo_pago']) ?></td>
                                        <td><?= htmlspecialchars($pago['referencia'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($pago['observaciones'] ?? '-') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>
