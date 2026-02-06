<?php
require_once '../vendor/autoload.php';
require_once '../includes/auth_helper.php';

// Solo administradores
requireRole('admin');

use App\DatabaseFactory;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
try {
    $dotenv->safeLoad();
} catch (Exception $e) { }

$db = DatabaseFactory::create();

$mensaje = '';
$error = '';

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['accion'])) {
            switch ($_POST['accion']) {
                case 'crear':
                    $datos = [
                        'codigo' => $_POST['codigo'],
                        'nombre_servicio' => $_POST['nombre_servicio'],
                        'descripcion' => $_POST['descripcion'] ?? '',
                        'precio' => floatval($_POST['precio']),
                        'activo' => isset($_POST['activo']),
                        'tipo_tarifa' => $_POST['tipo_tarifa'],
                        'eps_id' => !empty($_POST['eps_id']) ? intval($_POST['eps_id']) : null,
                        'porcentaje_copago' => floatval($_POST['porcentaje_copago'] ?? 0),
                        'cups_codigo' => $_POST['cups_codigo'] ?? null
                    ];
                    $db->insert('tarifarios', $datos);
                    $mensaje = "Tarifa creada exitosamente";
                    break;
                    
                case 'actualizar':
                    $id = intval($_POST['id']);
                    $datos = [
                        'codigo' => $_POST['codigo'],
                        'nombre_servicio' => $_POST['nombre_servicio'],
                        'descripcion' => $_POST['descripcion'] ?? '',
                        'precio' => floatval($_POST['precio']),
                        'activo' => isset($_POST['activo']),
                        'tipo_tarifa' => $_POST['tipo_tarifa'],
                        'eps_id' => !empty($_POST['eps_id']) ? intval($_POST['eps_id']) : null,
                        'porcentaje_copago' => floatval($_POST['porcentaje_copago'] ?? 0),
                        'cups_codigo' => $_POST['cups_codigo'] ?? null
                    ];
                    $db->update('tarifarios', $datos, "id=eq.$id");
                    $mensaje = "Tarifa actualizada exitosamente";
                    break;
                    
                case 'eliminar':
                    $id = intval($_POST['id']);
                    $db->delete('tarifarios', "id=eq.$id");
                    $mensaje = "Tarifa eliminada exitosamente";
                    break;
            }
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Obtener todas las tarifas
$tarifas = $db->select('tarifarios', '*', '', 'nombre_servicio.asc');

// Obtener EPS para el formulario
$epsList = $db->select('eps', 'id,nombre_eps', '', 'nombre_eps.asc');

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Administración de Tarifarios - Sistema Médico</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .tarifa-card {
            background: var(--gray-50);
            border: 1px solid var(--gray-200);
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        
        .tarifa-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 0.5rem;
        }
        
        .tarifa-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--primary);
        }
        
        .tarifa-code {
            background: var(--gray-200);
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-family: monospace;
            font-size: 0.875rem;
        }
        
        .tarifa-precio {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--success);
        }
        
        .badge-tipo {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-right: 0.5rem;
        }
        
        .tipo-general {
            background: #e0e7ff;
            color: #3730a3;
        }
        
        .tipo-eps {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .tipo-particular {
            background: #fef3c7;
            color: #92400e;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: 8px;
            padding: 2rem;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
    </style>
</head>
<body class="dashboard-container">
    <?php include '../includes/sidebar.php'; ?>
    <?php include '../includes/header.php'; ?>
    
    <main class="main-content">
        <div class="container">
            <div class="page-header">
                <h1>⚙️ Administración de Tarifarios</h1>
                <div class="page-actions">
                    <button onclick="abrirModal()" class="btn btn-primary">
                        + Nueva Tarifa
                    </button>
                </div>
            </div>

            <?php if ($mensaje): ?>
                <div class="alert alert-success"><?= htmlspecialchars($mensaje) ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="card">
                <h2>Tarifas Registradas (<?= count($tarifas) ?>)</h2>
                
                <?php if (empty($tarifas)): ?>
                    <p style="text-align: center; color: var(--gray-400); padding: 3rem;">
                        No hay tarifas registradas
                    </p>
                <?php else: ?>
                    <div style="margin-top: 1.5rem;">
                        <?php foreach ($tarifas as $tarifa): ?>
                            <div class="tarifa-card">
                                <div class="tarifa-header">
                                    <div>
                                        <div class="tarifa-title"><?= htmlspecialchars($tarifa['nombre_servicio']) ?></div>
                                        <div style="margin-top: 0.25rem;">
                                            <span class="tarifa-code"><?= htmlspecialchars($tarifa['codigo']) ?></span>
                                            <?php if ($tarifa['cups_codigo']): ?>
                                                <span class="tarifa-code">CUPS: <?= htmlspecialchars($tarifa['cups_codigo']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="tarifa-precio">$<?= number_format($tarifa['precio'], 0, ',', '.') ?></div>
                                    </div>
                                </div>
                                
                                <div style="margin: 0.75rem 0;">
                                    <span class="badge-tipo tipo-<?= $tarifa['tipo_tarifa'] ?>">
                                        <?= ucfirst($tarifa['tipo_tarifa']) ?>
                                    </span>
                                    <?php if ($tarifa['porcentaje_copago'] > 0): ?>
                                        <span class="badge-tipo" style="background: #fef3c7; color: #92400e;">
                                            Copago: <?= $tarifa['porcentaje_copago'] ?>%
                                        </span>
                                    <?php endif; ?>
                                    <span class="badge-tipo" style="background: <?= $tarifa['activo'] ? '#d1fae5' : '#fee2e2' ?>; color: <?= $tarifa['activo'] ? '#065f46' : '#991b1b' ?>;">
                                        <?= $tarifa['activo'] ? 'Activo' : 'Inactivo' ?>
                                    </span>
                                </div>
                                
                                <?php if ($tarifa['descripcion']): ?>
                                    <p style="color: var(--gray-600); font-size: 0.875rem;">
                                        <?= htmlspecialchars($tarifa['descripcion']) ?>
                                    </p>
                                <?php endif; ?>
                                
                                <div style="margin-top: 0.75rem; display: flex; gap: 0.5rem;">
                                    <button onclick="editarTarifa(<?= htmlspecialchars(json_encode($tarifa)) ?>)" class="btn btn-sm btn-outline">
                                        ✏️ Editar
                                    </button>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('¿Eliminar esta tarifa?');">
                                        <input type="hidden" name="accion" value="eliminar">
                                        <input type="hidden" name="id" value="<?= $tarifa['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">🗑️ Eliminar</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Modal Crear/Editar -->
    <div id="modalTarifa" class="modal">
        <div class="modal-content">
            <h2 id="modalTitulo">Nueva Tarifa</h2>
            
            <form method="POST" id="formTarifa">
                <input type="hidden" name="accion" id="accion" value="crear">
                <input type="hidden" name="id" id="tarifa_id">
                
                <div class="grid grid-2">
                    <div class="form-group">
                        <label>Código *</label>
                        <input type="text" name="codigo" id="codigo" required>
                    </div>
                    <div class="form-group">
                        <label>Tipo de Tarifa *</label>
                        <select name="tipo_tarifa" id="tipo_tarifa" required>
                            <option value="general">General</option>
                            <option value="eps">EPS</option>
                            <option value="particular">Particular</option>
                            <option value="subsidiado">Subsidiado</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Nombre del Servicio *</label>
                    <input type="text" name="nombre_servicio" id="nombre_servicio" required>
                </div>
                
                <div class="form-group">
                    <label>Descripción</label>
                    <textarea name="descripcion" id="descripcion" rows="2"></textarea>
                </div>
                
                <div class="grid grid-2">
                    <div class="form-group">
                        <label>Precio ($) *</label>
                        <input type="number" name="precio" id="precio" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>% Copago</label>
                        <input type="number" name="porcentaje_copago" id="porcentaje_copago" step="0.01" value="0" min="0" max="100">
                    </div>
                </div>
                
                <div class="grid grid-2">
                    <div class="form-group">
                        <label>EPS (opcional)</label>
                        <select name="eps_id" id="eps_id">
                            <option value="">Todas las EPS</option>
                            <?php foreach ($epsList as $eps): ?>
                                <option value="<?= $eps['id'] ?>"><?= htmlspecialchars($eps['nombre_eps']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Código CUPS (opcional)</label>
                        <input type="text" name="cups_codigo" id="cups_codigo">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="activo" id="activo" checked>
                        Activo
                    </label>
                </div>
                
                <div style="margin-top: 1.5rem; display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" onclick="cerrarModal()" class="btn btn-secondary">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function abrirModal() {
            document.getElementById('modalTitulo').textContent = 'Nueva Tarifa';
            document.getElementById('accion').value = 'crear';
            document.getElementById('formTarifa').reset();
            document.getElementById('modalTarifa').classList.add('active');
        }
        
        function cerrarModal() {
            document.getElementById('modalTarifa').classList.remove('active');
        }
        
        function editarTarifa(tarifa) {
            document.getElementById('modalTitulo').textContent = 'Editar Tarifa';
            document.getElementById('accion').value = 'actualizar';
            document.getElementById('tarifa_id').value = tarifa.id;
            document.getElementById('codigo').value = tarifa.codigo;
            document.getElementById('nombre_servicio').value = tarifa.nombre_servicio;
            document.getElementById('descripcion').value = tarifa.descripcion || '';
            document.getElementById('precio').value = tarifa.precio;
            document.getElementById('tipo_tarifa').value = tarifa.tipo_tarifa;
            document.getElementById('eps_id').value = tarifa.eps_id || '';
            document.getElementById('porcentaje_copago').value = tarifa.porcentaje_copago || 0;
            document.getElementById('cups_codigo').value = tarifa.cups_codigo || '';
            document.getElementById('activo').checked = tarifa.activo;
            document.getElementById('modalTarifa').classList.add('active');
        }
        
        // Cerrar modal al hacer clic fuera
        document.getElementById('modalTarifa').addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModal();
            }
        });
    </script>
</body>
</html>
