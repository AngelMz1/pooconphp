<?php
require_once '../vendor/autoload.php';

use App\SupabaseClient;
use App\Paciente;
use App\ReferenceData;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$supabase = new SupabaseClient($_ENV['SUPABASE_URL'], $_ENV['SUPABASE_KEY']);
$pacienteModel = new Paciente($supabase);
$refData = new ReferenceData($supabase);

$mensaje = '';
$error = '';
$paciente = null;
$isEdit = false;

// Cargar datos de referencia
$formData = $refData->getAllForPatientForm();

// Determinar si es edición
if (isset($_GET['id'])) {
    $isEdit = true;
    try {
        $paciente = $pacienteModel->obtenerPorId($_GET['id']);
        if (!$paciente) {
            $error = "Paciente no encontrado";
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Procesar formulario
if ($_POST) {
    try {
        // Preparar TODOS los datos del paciente
        $datos = [
            // Identificación
            'tipo_documento_id' => $_POST['tipo_documento_id'] ?? 1,
            'documento_id' => $_POST['documento_id'],
            
            // Nombres completos (todos requeridos según schema)
            'primer_nombre' => $_POST['primer_nombre'],
            'segundo_nombre' => $_POST['segundo_nombre'] ?? '',
            'primer_apellido' => $_POST['primer_apellido'],
            'segundo_apellido' => $_POST['segundo_apellido'] ?? '',
            
            // Datos básicos
            'fecha_nacimiento' => $_POST['fecha_nacimiento'] ?? null,
            'sexo_id' => $_POST['sexo_id'] ?? null,
            
            // Ubicación
            'direccion' => $_POST['direccion'] ?? null,
            'telefono' => $_POST['telefono'] ?? null,
            'ciudad_id' => (int)$_POST['ciudad_id'],
            'lugar_nacimiento' => (int)$_POST['lugar_nacimiento'],
            'barrio_id' => !empty($_POST['barrio_id']) ? (int)$_POST['barrio_id'] : null,
            
            // Salud y aseguramiento
            'eps_id' => (int)$_POST['eps_id'],
            'regimen_id' => !empty($_POST['regimen_id']) ? (int)$_POST['regimen_id'] : null,
            'gs_rh_id' => (int)$_POST['gs_rh_id'],
            
            // Datos sociodemográficos
            'estrato' => (int)$_POST['estrato'],
            'estado_civil_id' => !empty($_POST['estado_civil_id']) ? (int)$_POST['estado_civil_id'] : null,
            'ocupacion' => $_POST['ocupacion'] ?? null,
            'escolaridad_id' => !empty($_POST['escolaridad_id']) ? (int)$_POST['escolaridad_id'] : null,
            
            // Diversidad
            'etnia_id' => !empty($_POST['etnia_id']) ? (int)$_POST['etnia_id'] : null,
            'orien_sexual_id' => !empty($_POST['orien_sexual_id']) ? (int)$_POST['orien_sexual_id'] : null,
            
            // Vulnerabilidad social
            'g_poblacion' => $_POST['g_poblacion'] ?? null,
            'prog_social' => $_POST['prog_social'] ?? null,
            'discapacidad' => $_POST['discapacidad'] ?? null,
            'cond_vulnerabilidad' => $_POST['cond_vulnerabilidad'] ?? null,
            'hech_victimizantes' => $_POST['hech_victimizantes'] ?? null,
            
            // Acudiente
            'acudiente_id' => !empty($_POST['acudiente_id']) ? (int)$_POST['acudiente_id'] : null
        ];

        if ($isEdit && isset($_POST['id_paciente'])) {
            // Actualizar
            $resultado = $pacienteModel->actualizar($_POST['id_paciente'], $datos);
            $mensaje = "✅ Paciente actualizado exitosamente";
            $paciente = $pacienteModel->obtenerPorId($_POST['id_paciente']);
        } else {
            // Crear
            $resultado = $pacienteModel->crear($datos);
            $mensaje = "✅ Paciente creado exitosamente con ID: " . $resultado[0]['id_paciente'];
            header("Location: gestionar_pacientes_completo.php?id=" . $resultado[0]['id_paciente'] . "&success=1");
            exit;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

if (isset($_GET['success']) && !$error) {
    $mensaje = "✅ Paciente creado exitosamente";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isEdit ? 'Editar Paciente' : 'Nuevo Paciente' ?> (Completo) - Sistema Médico</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
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
        .field-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        .help-text {
            font-size: 0.75rem;
            color: var(--gray-500);
            margin-top: 0.25rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card card-gradient text-center mb-4">
            <h1><?= $isEdit ? '✏️ Editar Paciente' : '➕ Nuevo Paciente' ?> (Completo)</h1>
            <p style="margin-bottom: 0;">Formulario completo con todos los campos del sistema</p>
        </div>

        <?php if ($mensaje): ?>
            <div class="alert alert-success"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="card">
            <form method="POST" id="patientForm">
                <?php if ($isEdit && $paciente): ?>
                    <input type="hidden" name="id_paciente" value="<?= $paciente['id_paciente'] ?>">
                <?php endif; ?>

                <!-- Tabs de Navegación -->
                <div class="tabs">
                    <button type="button" class="tab-btn active" onclick="showTab(0)">🆔 Identificación</button>
                    <button type="button" class="tab-btn" onclick="showTab(1)">👤 Datos Personales</button>
                    <button type="button" class="tab-btn" onclick="showTab(2)">📍 Ubicación</button>
                    <button type="button" class="tab-btn" onclick="showTab(3)">🏥 Salud y EPS</button>
                    <button type="button" class="tab-btn" onclick="showTab(4)">📊 Sociodemográficos</button>
                    <button type="button" class="tab-btn" onclick="showTab(5)">🌈 Diversidad</button>
                    <button type="button" class="tab-btn" onclick="showTab(6)">⚠️ Vulnerabilidad</button>
                    <button type="button" class="tab-btn" onclick="showTab(7)">👨‍👩‍👧 Acudiente</button>
                </div>

                <!-- Tab 1: Identificación -->
                <div class="tab-content active" id="tab-0">
                    <h3>🆔 Identificación del Paciente</h3>
                    
                    <div class="field-grid">
                        <div class="form-group">
                            <label for="tipo_documento_id">Tipo de Documento <span class="required-indicator">*</span></label>
                            <select name="tipo_documento_id" id="tipo_documento_id" required>
                                <?php foreach ($formData['tipos_documento'] as $tipo): ?>
                                    <option value="<?= $tipo['id'] ?>" 
                                        <?= ($isEdit && $paciente['tipo_documento_id'] == $tipo['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($tipo['codigo']) ?> - <?= htmlspecialchars($tipo['descripcion']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="documento_id">Número de Documento <span class="required-indicator">*</span></label>
                            <input type="text" name="documento_id" id="documento_id" required
                                   value="<?= $isEdit ? htmlspecialchars($paciente['documento_id']) : '' ?>"
                                   pattern="[0-9]{8,10}"
                                   <?= $isEdit ? 'readonly style="background: var(--gray-200);"' : '' ?>>
                            <small class="help-text">Entre 8 y 10 dígitos numéricos <?= $isEdit ? '(no modificable)' : '' ?></small>
                        </div>
                    </div>

                    <h4 class="mt-3">📝 Nombre Completo</h4>
                    <div class="field-grid">
                        <div class="form-group">
                            <label for="primer_nombre">Primer Nombre <span class="required-indicator">*</span></label>
                            <input type="text" name="primer_nombre" id="primer_nombre" required
                                   value="<?= $isEdit ? htmlspecialchars($paciente['primer_nombre']) : '' ?>">
                        </div>

                        <div class="form-group">
                            <label for="segundo_nombre">Segundo Nombre</label>
                            <input type="text" name="segundo_nombre" id="segundo_nombre"
                                   value="<?= $isEdit ? htmlspecialchars($paciente['segundo_nombre'] ?? '') : '' ?>">
                            <small class="help-text">Opcional</small>
                        </div>

                        <div class="form-group">
                            <label for="primer_apellido">Primer Apellido <span class="required-indicator">*</span></label>
                            <input type="text" name="primer_apellido" id="primer_apellido" required
                                   value="<?= $isEdit ? htmlspecialchars($paciente['primer_apellido']) : '' ?>">
                        </div>

                        <div class="form-group">
                            <label for="segundo_apellido">Segundo Apellido</label>
                            <input type="text" name="segundo_apellido" id="segundo_apellido"
                                   value="<?= $isEdit ? htmlspecialchars($paciente['segundo_apellido'] ?? '') : '' ?>">
                            <small class="help-text">Opcional</small>
                        </div>
                    </div>
                </div>

                <!-- Tab 2: Datos Personales -->
                <div class="tab-content" id="tab-1">
                    <h3>👤 Datos Personales</h3>
                    
                    <div class="field-grid">
                        <div class="form-group">
                            <label for="fecha_nacimiento">Fecha de Nacimiento</label>
                            <input type="date" name="fecha_nacimiento" id="fecha_nacimiento"
                                   value="<?= $isEdit ? htmlspecialchars($paciente['fecha_nacimiento'] ?? '') : '' ?>">
                        </div>

                        <div class="form-group">
                            <label for="sexo_id">Sexo</label>
                            <select name="sexo_id" id="sexo_id">
                                <option value="">Seleccionar...</option>
                                <?php foreach ($formData['sexos'] as $sexo): ?>
                                    <option value="<?= $sexo['id'] ?>"
                                        <?= ($isEdit && $paciente['sexo_id'] == $sexo['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($sexo['sexo']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="estado_civil_id">Estado Civil</label>
                            <select name="estado_civil_id" id="estado_civil_id">
                                <option value="">Seleccionar...</option>
                                <?php foreach ($formData['estados_civiles'] as $ec): ?>
                                    <option value="<?= $ec['id'] ?>"
                                        <?= ($isEdit && $paciente['estado_civil_id'] == $ec['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($ec['estado_civil']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="ocupacion">Ocupación</label>
                            <input type="text" name="ocupacion" id="ocupacion" maxlength="1"
                                   value="<?= $isEdit ? htmlspecialchars($paciente['ocupacion'] ?? '') : '' ?>">
                            <small class="help-text">Un carácter</small>
                        </div>
                    </div>

                    <div class="field-grid">
                        <div class="form-group">
                            <label for="telefono">Teléfono</label>
                            <input type="tel" name="telefono" id="telefono"
                                   value="<?= $isEdit ? htmlspecialchars($paciente['telefono'] ?? '') : '' ?>">
                        </div>
                    </div>
                </div>

                <!-- Tab 3: Ubicación -->
                <div class="tab-content" id="tab-2">
                    <h3>📍 Ubicación</h3>
                    
                    <div class="field-grid">
                        <div class="form-group">
                            <label for="ciudad_id">Ciudad de Residencia <span class="required-indicator">*</span></label>
                            <select name="ciudad_id" id="ciudad_id" required onchange="loadBarrios(this.value)">
                                <option value="">Seleccionar...</option>
                                <?php foreach ($formData['ciudades'] as $ciudad): ?>
                                    <option value="<?= $ciudad['id'] ?>"
                                        <?= ($isEdit && $paciente['ciudad_id'] == $ciudad['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($ciudad['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="barrio_id">Barrio</label>
                            <select name="barrio_id" id="barrio_id">
                                <option value="">Seleccionar primero una ciudad...</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="lugar_nacimiento">Lugar de Nacimiento <span class="required-indicator">*</span></label>
                            <select name="lugar_nacimiento" id="lugar_nacimiento" required>
                                <option value="">Seleccionar...</option>
                                <?php foreach ($formData['ciudades'] as $ciudad): ?>
                                    <option value="<?= $ciudad['id'] ?>"
                                        <?= ($isEdit && $paciente['lugar_nacimiento'] == $ciudad['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($ciudad['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="direccion">Dirección Completa</label>
                        <textarea name="direccion" id="direccion" rows="2"><?= $isEdit ? htmlspecialchars($paciente['direccion'] ?? '') : '' ?></textarea>
                    </div>
                </div>

                <!-- Tab 4: Salud y EPS -->
                <div class="tab-content" id="tab-3">
                    <h3>🏥 Salud y Aseguramiento</h3>
                    
                    <div class="field-grid">
                        <div class="form-group">
                            <label for="eps_id">EPS <span class="required-indicator">*</span></label>
                            <select name="eps_id" id="eps_id" required>
                                <option value="">Seleccionar...</option>
                                <?php foreach ($formData['eps'] as $eps): ?>
                                    <option value="<?= $eps['id'] ?>"
                                        <?= ($isEdit && $paciente['eps_id'] == $eps['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($eps['nombre_eps']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="regimen_id">Régimen</label>
                            <select name="regimen_id" id="regimen_id">
                                <option value="">Seleccionar...</option>
                                <?php foreach ($formData['regimenes'] as $regimen): ?>
                                    <option value="<?= $regimen['id'] ?>"
                                        <?= ($isEdit && $paciente['regimen_id'] == $regimen['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($regimen['regimen']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="gs_rh_id">Grupo Sanguíneo y RH <span class="required-indicator">*</span></label>
                            <select name="gs_rh_id" id="gs_rh_id" required>
                                <option value="">Seleccionar...</option>
                                <?php foreach ($formData['grupos_sanguineos'] as $gs): ?>
                                    <option value="<?= $gs['id'] ?>"
                                        <?= ($isEdit && $paciente['gs_rh_id'] == $gs['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($gs['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Tab 5: Sociodemográficos -->
                <div class="tab-content" id="tab-4">
                    <h3>📊 Datos Sociodemográficos</h3>
                    
                    <div class="field-grid">
                        <div class="form-group">
                            <label for="estrato">Estrato Socioeconómico <span class="required-indicator">*</span></label>
                            <select name="estrato" id="estrato" required>
                                <option value="">Seleccionar...</option>
                                <?php for ($i = 1; $i <= 6; $i++): ?>
                                    <option value="<?= $i ?>" <?= ($isEdit && $paciente['estrato'] == $i) ? 'selected' : '' ?>>
                                        Estrato <?= $i ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="escolaridad_id">Escolaridad</label>
                            <select name="escolaridad_id" id="escolaridad_id">
                                <option value="">Seleccionar...</option>
                                <?php foreach ($formData['escolaridades'] as $esc): ?>
                                    <option value="<?= $esc['id'] ?>"
                                        <?= ($isEdit && $paciente['escolaridad_id'] == $esc['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($esc['escolaridad']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="g_poblacion">Grupo Poblacional</label>
                            <input type="text" name="g_poblacion" id="g_poblacion"
                                   value="<?= $isEdit ? htmlspecialchars($paciente['g_poblacion'] ?? '') : '' ?>">
                            <small class="help-text">Ej: Indígena, Afrodescendiente, ROM, etc.</small>
                        </div>

                        <div class="form-group">
                            <label for="prog_social">Programas Sociales</label>
                            <input type="text" name="prog_social" id="prog_social"
                                   value="<?= $isEdit ? htmlspecialchars($paciente['prog_social'] ?? '') : '' ?>">
                            <small class="help-text">Ej: Familias en Acción, etc.</small>
                        </div>
                    </div>
                </div>

                <!-- Tab 6: Diversidad -->
                <div class="tab-content" id="tab-5">
                    <h3>🌈 Diversidad</h3>
                    
                    <div class="field-grid">
                        <div class="form-group">
                            <label for="etnia_id">Etnia</label>
                            <select name="etnia_id" id="etnia_id">
                                <option value="">Seleccionar...</option>
                                <?php foreach ($formData['etnias'] as $etnia): ?>
                                    <option value="<?= $etnia['id'] ?>"
                                        <?= ($isEdit && $paciente['etnia_id'] == $etnia['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($etnia['etnia']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="orien_sexual_id">Orientación Sexual</label>
                            <select name="orien_sexual_id" id="orien_sexual_id">
                                <option value="">Seleccionar...</option>
                                <?php foreach ($formData['orientaciones_sexuales'] as $os): ?>
                                    <option value="<?= $os['id'] ?>"
                                        <?= ($isEdit && $paciente['orien_sexual_id'] == $os['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($os['orientacion']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Tab 7: Vulnerabilidad -->
                <div class="tab-content" id="tab-6">
                    <h3>⚠️ Vulnerabilidad Social</h3>
                    
                    <div class="form-group">
                        <label for="discapacidad">Discapacidad</label>
                        <textarea name="discapacidad" id="discapacidad" rows="2"><?= $isEdit ? htmlspecialchars($paciente['discapacidad'] ?? '') : '' ?></textarea>
                        <small class="help-text">Describir tipo de discapacidad si aplica</small>
                    </div>

                    <div class="form-group">
                        <label for="cond_vulnerabilidad">Condición de Vulnerabilidad</label>
                        <textarea name="cond_vulnerabilidad" id="cond_vulnerabilidad" rows="2"><?= $isEdit ? htmlspecialchars($paciente['cond_vulnerabilidad'] ?? '') : '' ?></textarea>
                        <small class="help-text">Ej: Desplazamiento, pobreza extrema, etc.</small>
                    </div>

                    <div class="form-group">
                        <label for="hech_victimizantes">Hechos Victimizantes</label>
                        <textarea name="hech_victimizantes" id="hech_victimizantes" rows="2"><?= $isEdit ? htmlspecialchars($paciente['hech_victimizantes'] ?? '') : '' ?></textarea>
                        <small class="help-text">Violencia, desplazamiento forzado, etc.</small>
                    </div>
                </div>

                <!-- Tab 8: Acudiente -->
                <div class="tab-content" id="tab-7">
                    <h3>👨‍👩‍👧 Acudiente / Responsable</h3>
                    
                    <div class="form-group">
                        <label for="acudiente_id">Seleccionar Acudiente</label>
                        <select name="acudiente_id" id="acudiente_id">
                            <option value="">Sin acudiente / No aplica</option>
                            <?php foreach ($formData['acudientes'] as $acudiente): ?>
                                <option value="<?= $acudiente['id'] ?>"
                                    <?= ($isEdit && $paciente['acudiente_id'] == $acudiente['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($acudiente['nombre']) ?> 
                                    <?= $acudiente['parentesco'] ? '(' . htmlspecialchars($acudiente['parentesco']) . ')' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="help-text">Para menores de edad o personas con tutela</small>
                    </div>
                </div>

                <!-- Botones de Navegación -->
                <div class="card-footer" style="margin-top: 2rem; padding-top: 2rem; border-top: 2px solid var(--gray-200);">
                    <div style="display: flex; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
                        <button type="button" class="btn btn-secondary" id="prevBtn" onclick="navigateTab(-1)" style="display: none;">
                            ← Anterior
                        </button>
                        <button type="button" class="btn btn-primary" id="nextBtn" onclick="navigateTab(1)">
                            Siguiente →
                        </button>
                        <button type="submit" class="btn btn-success btn-lg" id="submitBtn" style="display: none;">
                            💾 <?= $isEdit ? 'Actualizar' : 'Crear' ?> Paciente
                        </button>
                    </div>
                    <div style="margin-top: 1rem;">
                        <a href="listar_pacientes.php" class="btn btn-outline">← Volver a la Lista</a>
                        <?php if ($isEdit): ?>
                            <a href="ver_paciente.php?id=<?= $paciente['id_paciente'] ?>" class="btn btn-outline">👁️ Ver Detalles</a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/js/app.js"></script>
    <script>
        let currentTab = 0;
        const totalTabs = 8;

        function showTab(n) {
            const tabs = document.querySelectorAll('.tab-content');
            const tabBtns = document.querySelectorAll('.tab-btn');
            
            tabs.forEach(tab => tab.classList.remove('active'));
            tabBtns.forEach(btn => btn.classList.remove('active'));
            
            tabs[n].classList.add('active');
            tabBtns[n].classList.add('active');
            
            currentTab = n;
            updateButtons();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function navigateTab(direction) {
            const newTab = currentTab + direction;
            if (newTab >= 0 && newTab < totalTabs) {
                showTab(newTab);
            }
        }

        function updateButtons() {
            document.getElementById('prevBtn').style.display = currentTab === 0 ? 'none' : 'inline-flex';
            document.getElementById('nextBtn').style.display = currentTab === totalTabs - 1 ? 'none' : 'inline-flex';
            document.getElementById('submitBtn').style.display = currentTab === totalTabs - 1 ? 'inline-flex' : 'none';
        }

        // Cargar barrios cuando se selecciona ciudad
        function loadBarrios(ciudadId) {
            // Por ahora, esto se maneja en el servidor
            // Podrías implementar AJAX aquí para cargar barrios dinámicamente
        }

        // Validación del formulario
        document.getElementById('patientForm').addEventListener('submit', function(e) {
            const documento = document.getElementById('documento_id').value;
            
            <?php if (!$isEdit): ?>
            if (!FormValidator.validateDocumento(documento)) {
                e.preventDefault();
                alert('El documento debe tener entre 8 y 10 dígitos');
                showTab(0);
                return false;
            }
            <?php endif; ?>

            // Validar campos requeridos del tab actual
            const requiredFields = document.querySelectorAll('.tab-content.active [required]');
            for (let field of requiredFields) {
                if (!field.value) {
                    e.preventDefault();
                    alert('Por favor complete todos los campos requeridos (*)');
                    field.focus();
                    return false;
                }
            }
        });

        // Inicializar
        updateButtons();
    </script>
</body>
</html>
