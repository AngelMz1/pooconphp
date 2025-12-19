<?php
require_once 'vendor/autoload.php';

use App\SupabaseClient;
use App\HistoriaClinica;
use App\FormulaMedica;
use App\PlanManejo;
use App\Procedimiento;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$supabase = new SupabaseClient($_ENV['SUPABASE_URL'], $_ENV['SUPABASE_KEY']);
$historiaModel = new HistoriaClinica($supabase);
$formulaModel = new FormulaMedica($supabase);
$planModel = new PlanManejo($supabase);
$procModel = new Procedimiento($supabase);

$mensaje = '';
$error = '';
$historia = null;

if (!isset($_GET['id'])) {
    header('Location: listar_historias.php');
    exit;
}

$id_historia = $_GET['id'];

try {
    $historia = $historiaModel->obtenerPorId($id_historia);
    if (!$historia) {
        throw new Exception("Historia clínica no encontrada");
    }
} catch (Exception $e) {
    $error = $e->getMessage();
}

if ($_POST) {
    try {
        $datos = $_POST;
        $datos['id_historia'] = $id_historia;

        // 1. Guardar Plan de Manejo
        if (!empty($datos['guardar_plan'])) {
            $planModel->crear($datos);
            $mensaje .= "✅ Plan de manejo guardado. ";
        }

        // 2. Guardar Fórmula Médica
        if (!empty($datos['guardar_formula'])) {
            // Guardar encabezado
            $resFormula = $formulaModel->crear($datos);
            $id_formula = $resFormula[0]['id_formula'];

            // Guardar medicamentos (si hay)
            if (!empty($datos['medicamentos'])) {
                foreach ($datos['medicamentos'] as $med) {
                    if (!empty($med['nombre'])) {
                        $formulaModel->agregarMedicamento([
                            'id_formula' => $id_formula,
                            'nombre_medicamento' => $med['nombre'],
                            'presentacion' => $med['presentacion'],
                            'dosis' => $med['dosis'],
                            'frecuencia' => $med['frecuencia'],
                            'via_administracion' => $med['via'],
                            'duracion' => $med['duracion'],
                            'cantidad_total' => $med['cantidad'],
                            'observaciones' => $med['obs'] ?? ''
                        ]);
                    }
                }
            }
            $mensaje .= "✅ Fórmula médica creada. ";
        }

        // 3. Guardar Procedimientos
        if (!empty($datos['guardar_procedimientos'])) {
             if (!empty($datos['procedimientos'])) {
                foreach ($datos['procedimientos'] as $proc) {
                    if (!empty($proc['codigo'])) {
                        $procModel->crear([
                            'id_historia' => $id_historia,
                            'codigo_cups' => $proc['codigo'],
                            'nombre_procedimiento' => $proc['nombre'],
                            'cantidad' => $proc['cantidad'],
                            'justificacion' => $datos['justificacion_proc'] ?? ''
                        ]);
                    }
                }
            }
            $mensaje .= "✅ Procedimientos ordenados. ";
        }

    } catch (Exception $e) {
        $error = "Error al guardar: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Órdenes Médicas - Sistema Médico</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        .tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid var(--gray-200);
            flex-wrap: wrap;
        }
        .tab-btn {
            padding: 0.75rem 1.5rem;
            background: transparent;
            border: none;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            font-weight: 500;
            color: var(--gray-600);
            transition: all var(--transition-normal);
        }
        .tab-btn:hover {
            color: var(--primary);
            background: var(--gray-100);
        }
        .tab-btn.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
            font-weight: 600;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }
        .item-row {
            background: var(--gray-50);
            padding: 1rem;
            margin-bottom: 0.5rem;
            border-radius: var(--radius-sm);
            border: 1px solid var(--gray-200);
            position: relative;
        }
        .btn-remove {
            position: absolute;
            top: 5px;
            right: 5px;
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            cursor: pointer;
            font-size: 12px;
            line-height: 1;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card card-gradient text-center mb-4">
            <h1>💊 Órdenes Médicas y Plan de Manejo</h1>
            <p style="margin-bottom: 0;">Historia Clínica #<?= htmlspecialchars($id_historia) ?></p>
        </div>

        <?php if ($mensaje): ?>
            <div class="alert alert-success"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="card">
            <form method="POST" id="ordenesForm">
                
                <div class="tabs">
                    <button type="button" class="tab-btn active" onclick="showTab(0)">📋 Plan de Manejo</button>
                    <button type="button" class="tab-btn" onclick="showTab(1)">💊 Fórmula Médica</button>
                    <button type="button" class="tab-btn" onclick="showTab(2)">💉 Procedimientos</button>
                    <button type="button" class="tab-btn" onclick="showTab(3)">⚠️ Incapacidad</button>
                </div>

                <!-- Tab 1: Plan de Manejo -->
                <div class="tab-content active" id="tab-0">
                    <input type="hidden" name="guardar_plan" id="input_guardar_plan" value="0">
                    
                    <div class="form-group">
                        <label for="tipo_plan">Tipo de Manejo</label>
                        <select name="tipo_plan" id="tipo_plan">
                            <option value="Ambulatorio">Ambulatorio (Casa)</option>
                            <option value="Hospitalario">Hospitalario (Ingreso)</option>
                            <option value="Observacion">Observación</option>
                            <option value="Remision">Remisión</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="descripcion">Descripción del Plan *</label>
                        <textarea name="descripcion" id="descripcion" rows="4" placeholder="Conducta a seguir..."></textarea>
                    </div>

                    <div class="grid grid-2">
                        <div class="form-group">
                            <label>Dieta y Nutrición</label>
                            <input type="text" name="dieta" placeholder="Ej: Dieta blanda, hiposódica...">
                        </div>
                        <div class="form-group">
                            <label>Cuidados Generales</label>
                            <input type="text" name="cuidados" placeholder="Ej: Reposo relativo, elevar cabecera...">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Signos de Alarma (Educación al paciente)</label>
                        <textarea name="signos_alarma" rows="2" placeholder="Acudir a urgencias si presenta..."></textarea>
                    </div>

                    <button type="button" class="btn btn-primary" onclick="guardarSeccion('plan')">💾 Guardar Plan de Manejo</button>
                </div>

                <!-- Tab 2: Fórmula Médica -->
                <div class="tab-content" id="tab-1">
                    <input type="hidden" name="guardar_formula" id="input_guardar_formula" value="0">
                    
                    <div class="grid grid-2">
                        <div class="form-group">
                            <label>Tipo de Fórmula</label>
                            <select name="tipo_formula">
                                <option value="Ambulatoria">Ambulatoria</option>
                                <option value="Control">Control Especial</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Vigencia (Días)</label>
                            <input type="number" name="vigencia_dias" value="30">
                        </div>
                    </div>

                    <h3>Medicamentos</h3>
                    <div id="lista-medicamentos">
                        <!-- Items dynamicos -->
                    </div>

                    <button type="button" class="btn btn-outline mb-4" onclick="agregarMedicamento()">
                        ➕ Agregar Medicamento
                    </button>

                    <div class="form-group">
                        <label>Recomendaciones Farmacológicas</label>
                        <textarea name="recomendaciones" rows="2"></textarea>
                    </div>

                    <button type="button" class="btn btn-primary" onclick="guardarSeccion('formula')">💾 Generar Fórmula Médica</button>
                </div>

                <!-- Tab 3: Procedimientos -->
                <div class="tab-content" id="tab-2">
                    <input type="hidden" name="guardar_procedimientos" id="input_guardar_procedimientos" value="0">
                    
                    <h3>Solicitud de Procedimientos / Exámenes</h3>
                    <div id="lista-procedimientos">
                        <!-- Items dynamicos -->
                    </div>

                    <button type="button" class="btn btn-outline mb-4" onclick="agregarProcedimiento()">
                        ➕ Agregar Procedimiento
                    </button>
                    
                    <div class="form-group">
                        <label>Justificación Clínica</label>
                        <textarea name="justificacion_proc" rows="2"></textarea>
                    </div>

                    <button type="button" class="btn btn-primary" onclick="guardarSeccion('procedimientos')">💾 Ordenar Procedimientos</button>
                </div>

                 <!-- Tab 4: Incapacidad (Placeholder simple) -->
                 <div class="tab-content" id="tab-3">
                    <div class="alert alert-info">
                        ℹ️ El módulo de incapacidades estará disponible próximamente en la versión completa.
                    </div>
                </div>

            </form>
        </div>
        
        <div style="text-align: center; margin-top: 1rem;">
             <a href="ver_historia.php?id=<?= $id_historia ?>" class="btn btn-secondary">↩ Volver a Historia</a>
        </div>
    </div>

    <!-- Templates para JS -->
    <template id="tpl-medicamento">
        <div class="item-row">
            <button type="button" class="btn-remove" onclick="this.parentElement.remove()">×</button>
            <div class="grid grid-2">
                <div class="form-group">
                    <input type="text" name="medicamentos[{i}][nombre]" placeholder="Nombre del Medicamento *" required>
                </div>
                <div class="form-group">
                    <input type="text" name="medicamentos[{i}][presentacion]" placeholder="Presentación (Ej: Tab 500mg)">
                </div>
            </div>
            <div class="grid grid-3">
                <div class="form-group"><input type="text" name="medicamentos[{i}][dosis]" placeholder="Dosis (Ej: 1 tableta)"></div>
                <div class="form-group"><input type="text" name="medicamentos[{i}][frecuencia]" placeholder="Frecuencia (Ej: C/8h)"></div>
                <div class="form-group"><input type="text" name="medicamentos[{i}][via]" placeholder="Vía (Ej: Oral)"></div>
            </div>
            <div class="grid grid-2">
                <div class="form-group"><input type="text" name="medicamentos[{i}][duracion]" placeholder="Duración (Ej: 5 días)"></div>
                <div class="form-group"><input type="number" name="medicamentos[{i}][cantidad]" placeholder="Cantidad Total"></div>
            </div>
             <div class="form-group mb-0">
                <input type="text" name="medicamentos[{i}][obs]" placeholder="Observaciones adicionales">
            </div>
        </div>
    </template>

    <template id="tpl-procedimiento">
        <div class="item-row">
            <button type="button" class="btn-remove" onclick="this.parentElement.remove()">×</button>
            <div class="grid grid-3">
                <div class="form-group">
                    <input type="text" name="procedimientos[{i}][codigo]" placeholder="Código CUPS *" required>
                </div>
                <div class="form-group" style="grid-column: span 2;">
                    <input type="text" name="procedimientos[{i}][nombre]" placeholder="Nombre del Procedimiento *" required>
                </div>
            </div>
            <div class="form-group mb-0">
                 <input type="number" name="procedimientos[{i}][cantidad]" value="1" placeholder="Cantidad">
            </div>
        </div>
    </template>

    <script>
        let medIndex = 0;
        let procIndex = 0;

        function showTab(n) {
            const tabs = document.querySelectorAll('.tab-content');
            const btns = document.querySelectorAll('.tab-btn');
            tabs.forEach(t => t.classList.remove('active'));
            btns.forEach(b => b.classList.remove('active'));
            tabs[n].classList.add('active');
            btns[n].classList.add('active');
        }

        function agregarMedicamento() {
            const tpl = document.getElementById('tpl-medicamento').innerHTML;
            const html = tpl.replace(/{i}/g, medIndex++);
            document.getElementById('lista-medicamentos').insertAdjacentHTML('beforeend', html);
        }

        function agregarProcedimiento() {
            const tpl = document.getElementById('tpl-procedimiento').innerHTML;
            const html = tpl.replace(/{i}/g, procIndex++);
            document.getElementById('lista-procedimientos').insertAdjacentHTML('beforeend', html);
        }

        function guardarSeccion(nombre) {
            // Reset flags
            document.getElementById('input_guardar_plan').value = '0';
            document.getElementById('input_guardar_formula').value = '0';
            document.getElementById('input_guardar_procedimientos').value = '0';
            
            // Set active flag
            document.getElementById('input_guardar_' + nombre).value = '1';
            
            // Submit
            document.getElementById('ordenesForm').submit();
        }

        // Agregar uno inicial
        agregarMedicamento();
        agregarProcedimiento();
    </script>
</body>
</html>
