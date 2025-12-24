<?php
require_once '../vendor/autoload.php';

use App\SupabaseClient;
use App\Consulta;
use App\Medico;
use App\Paciente;
use App\Diagnostico;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$supabase = new SupabaseClient($_ENV['SUPABASE_URL'], $_ENV['SUPABASE_KEY']);
$consultaModel = new Consulta($supabase);
$medicoModel = new Medico($supabase);
$pacienteModel = new Paciente($supabase);
$diagnosticoModel = new Diagnostico($supabase);

$mensaje = '';
$error = '';

// Cargar datos necesarios
try {
    $pacientes = $pacienteModel->obtenerTodos();
    $medicos = $medicoModel->obtenerTodos();
} catch (Exception $e) {
    $error = $e->getMessage();
}

// Procesar formulario
if ($_POST) {
    try {
        // Crear consulta
        $datosConsulta = [
            'id_paciente' => $_POST['id_paciente'],
            'medico_id' => $_POST['medico_id'],
            'motivo_consulta' => $_POST['motivo_consulta'],
            'enfermedad_actual' => $_POST['enfermedad_actual']
        ];
        
        $resultadoConsulta = $consultaModel->crear($datosConsulta);
        $id_consulta = $resultadoConsulta[0]['id_consulta'];
        
        // Si hay diagnóstico CIE-10, crearlo
        if (!empty($_POST['id_cie10_principal'])) {
            $datosDiagnostico = [
                'id_consulta' => $id_consulta,
                'tipo_dx' => $_POST['tipo_dx'] ?? 'Principal',
                'id_cie10_principal' => $_POST['id_cie10_principal']
            ];
            
            if (!empty($_POST['id_cie10_relacionado'])) {
                $datosDiagnostico['id_cie10_relacionado'] = $_POST['id_cie10_relacionado'];
            }
            
            $diagnosticoModel->crear($datosDiagnostico);
        }
        
        $mensaje = "✅ Consulta médica registrada exitosamente (ID: $id_consulta)";
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Consulta Médica - Sistema Médico</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <div class="dashboard-container">
        <?php include '../includes/sidebar.php'; ?>
        <?php include '../includes/header.php'; ?>
        
        <main class="main-content">
    <div class="container-sm">
        <div class="card card-gradient text-center mb-4">
            <h1>🩺 Nueva Consulta Médica</h1>
            <p style="margin-bottom: 0;">Registro de consulta con diagnóstico CIE-10</p>
        </div>

        <?php if ($mensaje): ?>
            <div class="alert alert-success"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="card">
            <form method="POST" id="consultaForm">
                
                <!-- Datos básicos -->
                <div class="form-section">
                    <h3>👥 Paciente y Médico</h3>
                    
                    <div class="form-group">
                        <label for="id_paciente">Paciente <span class="required-indicator">*</span></label>
                        <select name="id_paciente" id="id_paciente" required>
                            <option value="">Seleccionar paciente...</option>
                            <?php foreach ($pacientes as $p): ?>
                                <option value="<?= $p['id_paciente'] ?>">
                                    <?= htmlspecialchars($p['documento_id']) ?> - 
                                    <?= htmlspecialchars($p['primer_nombre']) ?> 
                                    <?= htmlspecialchars($p['primer_apellido']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="medico_id">Médico Tratante <span class="required-indicator">*</span></label>
                        <select name="medico_id" id="medico_id" required>
                            <option value="">Seleccionar médico...</option>
                            <?php foreach ($medicos as $m): ?>
                                <option value="<?= $m['id'] ?>">
                                    Dr(a). <?= htmlspecialchars($m['primer_nombre']) ?> 
                                    <?= htmlspecialchars($m['primer_apellido']) ?>
                                    <?php if (!empty($m['num_registro'])): ?>
                                        - Reg: <?= htmlspecialchars($m['num_registro']) ?>
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Motivo y enfermedad -->
                <div class="form-section">
                    <h3>📋 Motivo de Consulta</h3>
                    
                    <div class="form-group">
                        <label for="motivo_consulta">Motivo de Consulta <span class="required-indicator">*</span></label>
                        <textarea name="motivo_consulta" id="motivo_consulta" required rows="3"
                                  placeholder="Razón principal por la que el paciente acude a consulta..."></textarea>
                    </div>

                    <div class="form-group">
                        <label for="enfermedad_actual">Enfermedad Actual <span class="required-indicator">*</span></label>
                        <textarea name="enfermedad_actual" id="enfermedad_actual" required rows="4"
                                  placeholder="Descripción detallada del padecimiento actual del paciente..."></textarea>
                    </div>
                </div>

                <!-- Diagnóstico CIE-10 con Autocompletado -->
                <div class="form-section">
                    <h3>🏥 Diagnóstico CIE-10</h3>
                    
                    <div class="form-group">
                        <label for="tipo_dx">Tipo de Diagnóstico</label>
                        <select name="tipo_dx" id="tipo_dx">
                            <option value="Principal">Principal</option>
                            <option value="Secundario">Secundario</option>
                            <option value="Provisional">Provisional</option>
                            <option value="Definitivo">Definitivo</option>
                        </select>
                    </div>

                    <!-- Diagnóstico Principal -->
                    <div class="form-group" style="position: relative;">
                        <label for="cie10_principal_search">Diagnóstico Principal (CIE-10)</label>
                        <input type="text" id="cie10_principal_search" 
                               placeholder="Escriba código o nombre (ej: J18, Neumonía)..."
                               autocomplete="off"
                               onkeyup="buscarCIE10(this.value, 'principal')">
                        <input type="hidden" name="id_cie10_principal" id="id_cie10_principal">
                        
                        <!-- Lista desplegable de resultados -->
                        <div id="lista_resultados_principal" class="autocomplete-items"></div>
                        
                        <div id="seleccion_principal" class="seleccion-badge" style="display: none;">
                            <span id="texto_seleccion_principal"></span>
                            <button type="button" onclick="limpiarSeleccion('principal')">×</button>
                        </div>
                    </div>

                    <!-- Diagnóstico Relacionado -->
                    <div class="form-group" style="position: relative;">
                        <label for="cie10_relacionado_search">Diagnóstico Relacionado (Opcional)</label>
                        <input type="text" id="cie10_relacionado_search" 
                               placeholder="Buscar diagnóstico relacionado..."
                               autocomplete="off"
                               onkeyup="buscarCIE10(this.value, 'relacionado')">
                        <input type="hidden" name="id_cie10_relacionado" id="id_cie10_relacionado">
                        
                        <!-- Lista desplegable de resultados -->
                        <div id="lista_resultados_relacionado" class="autocomplete-items"></div>

                        <div id="seleccion_relacionado" class="seleccion-badge" style="display: none;">
                            <span id="texto_seleccion_relacionado"></span>
                            <button type="button" onclick="limpiarSeleccion('relacionado')">×</button>
                        </div>
                    </div>
                </div>

                <!-- Botones -->
                <div style="text-align: center; margin-top: 2rem;">
                    <button type="submit" class="btn btn-success btn-lg">
                        💾 Registrar Consulta
                    </button>
                    <a href="../index.php" class="btn btn-secondary btn-lg">
                        Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <style>
        .autocomplete-items {
            position: absolute;
            border: 1px solid #d4d4d4;
            border-bottom: none;
            border-top: none;
            z-index: 99;
            top: 100%;
            left: 0;
            right: 0;
            background-color: white;
            max-height: 200px;
            overflow-y: auto;
            border-radius: 0 0 5px 5px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .autocomplete-item {
            padding: 10px;
            cursor: pointer;
            border-bottom: 1px solid #d4d4d4;
        }
        .autocomplete-item:hover {
            background-color: #e9e9e9;
        }
        .autocomplete-item strong {
            color: var(--primary);
        }
        .seleccion-badge {
            margin-top: 5px;
            background-color: #e3f2fd;
            color: #0d47a1;
            padding: 8px 12px;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
        }
        .seleccion-badge button {
            background: none;
            border: none;
            color: #0d47a1;
            font-size: 18px;
            cursor: pointer;
            padding: 0 5px;
        }
        .seleccion-badge button:hover {
            color: red;
        }
    </style>

    <script>
        let timeoutBusqueda = null;

        function buscarCIE10(termino, tipo) {
            const lista = document.getElementById('lista_resultados_' + tipo);
            
            // Limpiar resultados anteriores si el término es corto
            if (termino.length < 2) {
                lista.innerHTML = '';
                return;
            }

            // Debounce para no saturar
            clearTimeout(timeoutBusqueda);
            timeoutBusqueda = setTimeout(() => {
                fetch(`../api/api_cie10.php?q=${encodeURIComponent(termino)}`)
                    .then(response => response.json())
                    .then(data => {
                        lista.innerHTML = '';
                        
                        if (data.length === 0) {
                            const item = document.createElement('div');
                            item.className = 'autocomplete-item';
                            item.innerHTML = '<em>No se encontraron resultados</em>';
                            lista.appendChild(item);
                            return;
                        }

                        data.forEach(cie => {
                            const item = document.createElement('div');
                            item.className = 'autocomplete-item';
                            item.innerHTML = `<strong>${cie.codigo}</strong> - ${cie.descripcion}`;
                            item.onclick = function() {
                                seleccionarCIE10(cie, tipo);
                            };
                            lista.appendChild(item);
                        });
                    })
                    .catch(console.error);
            }, 300);
        }

        function seleccionarCIE10(cie, tipo) {
            // Guardar ID
            document.getElementById('id_cie10_' + tipo).value = cie.id;
            
            // Mostrar selección visual
            const badge = document.getElementById('seleccion_' + tipo);
            const texto = document.getElementById('texto_seleccion_' + tipo);
            const input = document.getElementById('cie10_' + tipo + '_search');
            const lista = document.getElementById('lista_resultados_' + tipo);

            texto.textContent = `${cie.codigo} - ${cie.descripcion}`;
            badge.style.display = 'inline-flex';
            
            // Ocultar input y lista
            input.style.display = 'none';
            input.value = '';
            lista.innerHTML = '';
        }

        function limpiarSeleccion(tipo) {
            // Limpiar ID
            document.getElementById('id_cie10_' + tipo).value = '';
            
            // Ocultar badge
            document.getElementById('seleccion_' + tipo).style.display = 'none';
            
            // Mostrar input
            const input = document.getElementById('cie10_' + tipo + '_search');
            input.style.display = 'block';
            input.focus();
        }
        
        // Cerrar listas al hacer clic fuera
        document.addEventListener('click', function(e) {
            if (!e.target.matches('input[type="text"]')) {
                document.querySelectorAll('.autocomplete-items').forEach(el => el.innerHTML = '');
            }
        });
    </script>
        </main>
    </div>
</body>
</html>
