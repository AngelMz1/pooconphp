<?php
require_once '../vendor/autoload.php';
require_once '../includes/auth_helper.php';

// Solo médicos
requireRole('medico');

use App\SupabaseClient;
use App\Consulta;
use App\HistoriaClinica; 
use App\FormulaMedica;
use App\Procedimiento;
use App\SignosVitales;
use App\ReferenceData;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$supabase = new SupabaseClient($_ENV['SUPABASE_URL'], $_ENV['SUPABASE_KEY']);
$consultaModel = new Consulta($supabase);
$historiaModel = new HistoriaClinica($supabase);
$formulaModel = new FormulaMedica($supabase);
$procModel = new Procedimiento($supabase);
$signosModel = new SignosVitales($supabase);
$refData = new ReferenceData($supabase);

// Obtener lista de medicamentos para el select
$medicamentosList = $refData->getMedicamentos();
$medicamentosOptions = '<option value="">Seleccione Medicamento</option>';
foreach ($medicamentosList as $med) {
    $medicamentosOptions .= '<option value="' . htmlspecialchars($med['id']) . '">' . htmlspecialchars($med['nombre']) . '</option>';
}

$id_consulta = $_GET['id'] ?? null;
$mensaje = '';
$error = '';

if (!$id_consulta) {
    header("Location: ../index.php");
    exit;
}

try {
    // Obtener datos de la consulta
    $consulta = $consultaModel->obtenerPorId($id_consulta);
    
    if (!$consulta) {
        throw new Exception("Consulta no encontrada");
    }

    // Obtener info paciente
    $pacienteData = $supabase->select('pacientes', '*', "id_paciente=eq." . $consulta['id_paciente']);
    $paciente = $pacienteData[0] ?? null;

} catch (Exception $e) {
    $error = $e->getMessage();
}

// Procesar Formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // 1. Crear Historia Clínica
        $datosHistoria = [
            'id_paciente' => $consulta['id_paciente'],
            'id_consulta' => $id_consulta,
            'motivo_consulta' => $consulta['motivo_consulta'],
            'enfermedad_actual' => $consulta['enfermedad_actual'],
            'analisis_plan' => $_POST['analisis_plan'],
            'diagnostico' => $_POST['diagnostico_texto'], 
            'tratamiento' => $_POST['tratamiento'],
            'observaciones' => $_POST['observaciones']
        ];
        
        $resHistoria = $historiaModel->crear($datosHistoria);
        // Supabase returns array of inserted rows
        $id_historia = $resHistoria[0]['id_historia'];

        // 2. Guardar Fórmula Médica (si hay medicamentos)
        if (!empty($_POST['medicamentos']) && is_array($_POST['medicamentos'])) {
             // Filtrar vacíos
             $medicamentosValidos = array_filter($_POST['medicamentos'], function($m) {
                 return !empty($m['id_medicamento']);
             });

             if (!empty($medicamentosValidos)) {
                 $datosFormula = [
                     'id_historia' => $id_historia,
                     'tipo_formula' => $_POST['tipo_formula'] ?? 'Ambulatoria',
                     'vigencia_dias' => $_POST['vigencia_dias'] ?? 30,
                     'recomendaciones' => $_POST['recomendaciones_formula'] ?? ''
                 ];
                 
                 $resFormula = $formulaModel->crear($datosFormula);
                 $id_formula = $resFormula[0]['id_formula'];

                 foreach ($medicamentosValidos as $med) {
                     $formulaModel->agregarMedicamento([
                        'id_formula' => $id_formula,
                        'id_historia' => $id_historia,
                        'medicamento_id' => $med['id_medicamento'],
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

        // 3. Guardar Procedimientos (si hay)
        if (!empty($_POST['procedimientos']) && is_array($_POST['procedimientos'])) {
            $procsValidos = array_filter($_POST['procedimientos'], function($p) {
                return !empty($p['codigo']) && !empty($p['nombre']);
            });

            foreach ($procsValidos as $proc) {
                $procModel->crear([
                    'id_historia' => $id_historia,
                    'codigo_cups' => $proc['codigo'],
                    'nombre_procedimiento' => $proc['nombre'],
                    'cantidad' => $proc['cantidad'] ?? 1,
                    'justificacion' => $_POST['justificacion_proc'] ?? ''
                ]);
            }
        }
        
        if (!empty($_POST['signos_ta']) && !empty($_POST['signos_pulso'])) {
            $signosModel->crear([
                'id_historia' => $id_historia,
                'ta' => $_POST['signos_ta'],
                'pulso' => $_POST['signos_pulso'],
                'f_res' => $_POST['signos_fr'] ?? 0,
                'temperatura' => $_POST['signos_temp'] ?? 37,
                'peso' => $_POST['signos_peso'] ?? 0,
                'talla' => $_POST['signos_talla'] ?? 0,
                'sp02' => $_POST['signos_spo2'] ?? 0
            ]);
        }
        
        // 5. Cerrar Consulta
        $consultaModel->cambiarEstado($id_consulta, 'finalizada');
        
        // 6. Redirigir
        header("Location: ../index.php?msg=Consulta Finalizada Exitosamente");
        exit;
        
    } catch (Exception $e) {
        $error = "Error al guardar historia: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Atender Consulta - Sistema Médico</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .tabs { display: flex; gap: 0.5rem; border-bottom: 2px solid var(--gray-200); margin-bottom: 1.5rem; }
        .tab-btn { padding: 0.75rem 1.5rem; background: none; border: none; border-bottom: 3px solid transparent; cursor: pointer; font-weight: 500; }
        .tab-btn.active { color: var(--primary); border-bottom-color: var(--primary); font-weight: 600; }
        .tab-content { display: none; }
        .tab-content.active { display: block; animation: fadeIn 0.3s ease; }
        
        .item-row { background: var(--gray-50); padding: 1rem; margin-bottom: 0.5rem; border-radius: 4px; border: 1px solid var(--gray-200); position: relative; }
        .btn-remove { position: absolute; top: 5px; right: 5px; background: #dc3545; color: white; border: none; border-radius: 50%; width: 20px; height: 20px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 12px; }
    </style>
</head>
<body class="dashboard-container">
    <?php include '../includes/sidebar.php'; ?>
    <?php include '../includes/header.php'; ?>
    
    <main class="main-content">
        <div class="container">
            <div class="card mb-4">
                <div style="display:flex; justify-content:space-between;">
                    <h1>🩺 Atender Consulta</h1>
                    <span class="badge badge-warning">En Proceso</span>
                </div>
                
                <?php if($paciente): ?>
                    <div class="grid grid-2" style="background: var(--bg-secondary); padding: 1rem; border-radius: 8px;">
                        <div>
                            <h3>Paciente</h3>
                            <p class="big-text"><?= htmlspecialchars($paciente['primer_nombre'] . ' ' . $paciente['primer_apellido']) ?></p>
                            <p>🆔 <?= htmlspecialchars($paciente['documento_id']) ?></p>
                            <p>🎂 <?= htmlspecialchars($paciente['fecha_nacimiento'] ?? 'N/A') ?> (<?= isset($paciente['fecha_nacimiento']) ? date_diff(date_create($paciente['fecha_nacimiento']), date_create('today'))->y . ' años' : '' ?>)</p>
                        </div>
                        <div>
                            <h3>Motivo de Consulta</h3>
                            <p><strong>Motivo:</strong> <?= htmlspecialchars($consulta['motivo_consulta']) ?></p>
                            <p><strong>Enfermedad Actual:</strong> <?= htmlspecialchars($consulta['enfermedad_actual']) ?></p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <?php if($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" id="consultaForm">
                <div class="card">
                    <div class="tabs">
                        <button type="button" class="tab-btn active" onclick="showTab('tab-historia')">1. Historia Clínica</button>
                        <button type="button" class="tab-btn" onclick="showTab('tab-signos')">2. Signos Vitales</button>
                        <button type="button" class="tab-btn" onclick="showTab('tab-formula')">3. Fórmula Médica</button>
                        <button type="button" class="tab-btn" onclick="showTab('tab-procedimientos')">4. Procedimientos</button>
                    </div>

                    <!-- TAB 1: HISTORIA -->
                    <div class="tab-content active" id="tab-historia">
                        <h2>📝 Historia Clínica</h2>
                        
                        <div class="form-group">
                            <label>Análisis y Plan de Manejo *</label>
                            <textarea name="analisis_plan" rows="5" required class="form-control" placeholder="Describa el análisis del caso y el plan a seguir..."></textarea>
                        </div>

                        <div class="grid grid-2">
                            <div class="form-group">
                                <label>Diagnóstico (Texto) *</label>
                                <textarea name="diagnostico_texto" rows="3" required class="form-control" placeholder="Diagnóstico principal..."></textarea>
                            </div>
                            <div class="form-group">
                                <label>Tratamiento / Recomendaciones Generales *</label>
                                <textarea name="tratamiento" rows="3" required class="form-control" placeholder="Recomendaciones generales..."></textarea>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Observaciones Adicionales</label>
                            <textarea name="observaciones" rows="2" class="form-control"></textarea>
                        </div>
                        
                        <div class="mt-4 text-right">
                             <button type="button" class="btn btn-primary" onclick="showTab('tab-signos')">Siguiente: Signos Vitales ➡️</button>
                        </div>
                    </div>

                    <!-- TAB 2: SIGNOS VITALES -->
                    <div class="tab-content" id="tab-signos">
                        <h2>💓 Signos Vitales</h2>
                        <div class="grid grid-3">
                            <div class="form-group">
                                <label>Tensión Arterial (mmHg) *</label>
                                <input type="text" name="signos_ta" placeholder="120/80">
                            </div>
                            <div class="form-group">
                                <label>Pulso (lpm) *</label>
                                <input type="number" name="signos_pulso" placeholder="80">
                            </div>
                            <div class="form-group">
                                <label>Frecuencia Resp. (rpm)</label>
                                <input type="number" name="signos_fr" placeholder="18">
                            </div>
                        </div>
                        <div class="grid grid-3">
                            <div class="form-group">
                                <label>Temperatura (°C)</label>
                                <input type="number" step="0.1" name="signos_temp" placeholder="36.5">
                            </div>
                            <div class="form-group">
                                <label>Peso (kg)</label>
                                <input type="number" step="0.01" name="signos_peso" placeholder="70.5">
                            </div>
                            <div class="form-group">
                                <label>Talla (cm)</label>
                                <input type="number" step="1" name="signos_talla" placeholder="175">
                            </div>
                        </div>
                         <div class="grid grid-3">
                            <div class="form-group">
                                <label>Saturación O2 (%)</label>
                                <input type="number" name="signos_spo2" placeholder="98">
                            </div>
                        </div>

                         <div class="mt-4 text-right">
                             <button type="button" class="btn btn-secondary" onclick="showTab('tab-historia')">⬅️ Anterior</button>
                             <button type="button" class="btn btn-primary" onclick="showTab('tab-formula')">Siguiente: Fórmulas ➡️</button>
                        </div>
                    </div>

                    <!-- TAB 3: FORMULA -->
                    <div class="tab-content" id="tab-formula">
                        <h2>💊 Fórmula Médica</h2>
                        
                        <div class="grid grid-2">
                            <div class="form-group">
                                <label>Tipo de Fórmula</label>
                                <select name="tipo_formula">
                                    <option value="Ambulatoria">Ambulatoria</option>
                                    <option value="Control">Control Especial</option>
                                    <option value="Urgencias">Urgencias</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Vigencia (Días)</label>
                                <input type="number" name="vigencia_dias" value="30">
                            </div>
                        </div>

                        <label>Medicamentos</label>
                        <div id="lista-medicamentos"></div>

                        <button type="button" class="btn btn-outline" onclick="agregarMedicamento()" style="margin-top: 10px;">
                            ➕ Agregar Medicamento
                        </button>

                        <div class="form-group mt-4">
                            <label>Recomendaciones Farmacológicas</label>
                            <textarea name="recomendaciones_formula" rows="2" placeholder="Tomar con alimentos, etc..."></textarea>
                        </div>
                        
                        <div class="mt-4 text-right">
                             <button type="button" class="btn btn-secondary" onclick="showTab('tab-signos')">⬅️ Anterior</button>
                             <button type="button" class="btn btn-primary" onclick="showTab('tab-procedimientos')">Siguiente: Procedimientos ➡️</button>
                        </div>
                    </div>

                    <!-- TAB 4: PROCEDIMIENTOS -->
                    <div class="tab-content" id="tab-procedimientos">
                        <h2>🔬 Solicitud de Procedimientos / Exámenes</h2>
                        
                        <div id="lista-procedimientos"></div>

                        <button type="button" class="btn btn-outline" onclick="agregarProcedimiento()" style="margin-top: 10px;">
                            ➕ Agregar Procedimiento
                        </button>
                        
                        <div class="form-group mt-4">
                            <label>Justificación Clínica para Solicitudes</label>
                            <textarea name="justificacion_proc" rows="2" placeholder="Se solicita para descartar..."></textarea>
                        </div>

                        <div style="margin-top: 3rem; text-align: right; border-top: 1px solid #eee; padding-top: 1rem;">
                             <button type="button" class="btn btn-secondary" onclick="showTab('tab-formula')">⬅️ Anterior</button>
                             <button type="submit" class="btn btn-success btn-lg">Finalizar Consulta y Guardar Todo ✅</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </main>

    <!-- TEMPLATES -->
    <template id="tpl-medicamento">
        <div class="item-row fade-in">
            <button type="button" class="btn-remove" onclick="this.parentElement.remove()">×</button>
            <div class="grid grid-2">
                <div class="form-group">
                    <select name="medicamentos[{i}][id_medicamento]" required style="width: 100%;">
                        <?= $medicamentosOptions ?>
                    </select>
                </div>
                <div class="form-group">
                    <input type="text" name="medicamentos[{i}][dosis]" placeholder="Dosis (Ej: 500mg)" required>
                </div>
            </div>
            <div class="grid grid-3">
                <div class="form-group"><input type="text" name="medicamentos[{i}][frecuencia]" placeholder="Frecuencia (Ej: C/8h)"></div>
                <div class="form-group"><input type="text" name="medicamentos[{i}][via]" placeholder="Vía (Ej: Oral)"></div>
                <div class="form-group"><input type="text" name="medicamentos[{i}][duracion]" placeholder="Duración (Ej: 5 días)"></div>
            </div>
            <div class="grid grid-2">
                <div class="form-group"><input type="number" name="medicamentos[{i}][cantidad]" placeholder="Cant. Total (Ej: 15)"></div>
                <div class="form-group"><input type="text" name="medicamentos[{i}][obs]" placeholder="Obs/Presentación"></div>
            </div>
        </div>
    </template>

    <template id="tpl-procedimiento">
        <div class="item-row fade-in">
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

        function showTab(tabId) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(t => {
                t.style.display = 'none';
                t.classList.remove('active');
            });
            // Deactivate all buttons
            document.querySelectorAll('.tab-btn').forEach(b => {
                b.classList.remove('active');
            });

            // Show target tab
            const target = document.getElementById(tabId);
            if (target) {
                target.style.display = 'block';
                target.classList.add('active');
                
                // Active button finding
                const btns = document.querySelectorAll(`button[onclick="showTab('${tabId}')"]`);
                if (btns.length > 0) {
                     // The top tab bar button
                     btns[0].classList.add('active');
                }
            }
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
    </script>
</body>
</html>
