<?php
require_once '../vendor/autoload.php';

use App\SupabaseClient;
use Dotenv\Dotenv;

// Cargar variables de entorno
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Función para calcular edad
function calcularEdad($fecha_nacimiento) {
    if (!$fecha_nacimiento) return 'N/A';
    
    try {
        $fecha_nac = new DateTime($fecha_nacimiento);
        $hoy = new DateTime();
        $edad = $hoy->diff($fecha_nac)->y;
        return $edad . ' años';
    } catch (Exception $e) {
        return 'N/A';
    }
}

// --- CONFIGURACIÓN DE SUPABASE ---
$supabase = null;
$error_conexion = null;
$medicos_lista = [];

try {
    $supabase = new SupabaseClient($_ENV['SUPABASE_URL'], $_ENV['SUPABASE_KEY']);
    // Obtener lista de médicos
    $medicos_lista = $supabase->select('medicos', 'id,primer_nombre,primer_apellido', '');
} catch (Exception $e) {
    $error_conexion = "Error de conexión a Supabase: " . $e->getMessage();
}



// --- LÓGICA DE BÚSQUEDA ---
$paciente_encontrado = null;
$historial_clinico = [];
$doc_id_buscado = $_GET['doc_id'] ?? null;

if ($supabase && $doc_id_buscado) {
    try {
        // Búsqueda en Supabase
        $pacientes_data = $supabase->select('pacientes', '*', "documento_id=eq.$doc_id_buscado");
        
        if (!empty($pacientes_data)) {
            $paciente_encontrado = $pacientes_data[0];
            $id_paciente = $paciente_encontrado['id_paciente'];
            
            // Obtener nombre de EPS
            if ($paciente_encontrado['eps_id']) {
                $eps_data = $supabase->select('eps', 'nombre_eps', "id=eq.{$paciente_encontrado['eps_id']}");
                $paciente_encontrado['eps_nombre'] = !empty($eps_data) ? $eps_data[0]['nombre_eps'] : 'N/A';
            }
            
            // Obtener nombre de régimen
            if ($paciente_encontrado['regimen_id']) {
                $regimen_data = $supabase->select('regimen', 'regimen', "id=eq.{$paciente_encontrado['regimen_id']}");
                $paciente_encontrado['regimen_nombre'] = !empty($regimen_data) ? $regimen_data[0]['regimen'] : 'N/A';
            }

            // Buscar historias clínicas con datos relacionados
            $historias = $supabase->select('historias_clinicas', '*', "id_paciente=eq.$id_paciente");
            
            if (!empty($historias)) {
                // Para cada historia, obtener datos relacionados
                foreach ($historias as &$historia) {
                    $id_historia = $historia['id_historia'];
                    
                    // Obtener signos vitales
                    $signos = $supabase->select('signos_vitales', '*', "id_historia=eq.$id_historia");
                    $historia['signos_vitales'] = !empty($signos) ? $signos[0] : null;
                    
                    // Obtener consultas relacionadas
                    $consultas = $supabase->select('consultas', '*', "id_paciente=eq.$id_paciente");
                    $historia['consultas'] = $consultas;
                    
                    // Obtener examen físico
                    $examen = $supabase->select('examen_fisico_hallazgos', '*', "id_historia=eq.$id_historia");
                    $historia['examen_fisico'] = !empty($examen) ? $examen[0] : null;
                    
                    // Obtener revisión por sistemas
                    $revision = $supabase->select('revision_por_sistemas', '*', "id_historia=eq.$id_historia");
                    $historia['revision_sistemas'] = !empty($revision) ? $revision[0] : null;
                }
                $historial_clinico = $historias;
            }
        }
    } catch (Exception $e) {
        $error_conexion = "Error al buscar datos: " . $e->getMessage();
    }
}

// --- LÓGICA DE CREACIÓN ---
$mensaje_creacion = "";
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'crear_historia') {
    try {
        if ($supabase) {
            // Buscar paciente por documento
            $paciente_data = $supabase->select('pacientes', 'id_paciente', "documento_id=eq.{$_POST['documento_id']}");
            
            if (empty($paciente_data)) {
                $mensaje_creacion = "❌ No se encontró paciente con documento: {$_POST['documento_id']}";
            } else {
                $id_paciente = $paciente_data[0]['id_paciente'];
                
                // 1. Crear historia clínica
                $historia_data = [
                    'id_paciente' => $id_paciente,
                    'fecha_ingreso' => date('Y-m-d H:i:s'),
                    'analisis_plan' => $_POST['analisis_plan'] ?? ''
                ];
            
            $nueva_historia = $supabase->insert('historias_clinicas', $historia_data);
            $id_historia = $nueva_historia[0]['id_historia'];
            
                // 2. Crear consulta
                $consulta_data = [
                    'id_paciente' => $id_paciente,
                    'motivo_consulta' => $_POST['motivo_consulta'],
                    'enfermedad_actual' => $_POST['enfermedad_actual'],
                    'medico_id' => (int)$_POST['medico_id']
                ];
                
                $nueva_consulta = $supabase->insert('consultas', $consulta_data);
                $id_consulta = $nueva_consulta[0]['id_consulta'];
                
                // 3. Crear signos vitales si se proporcionan
                if (!empty($_POST['ta']) || !empty($_POST['pulso'])) {
                    $signos_data = [
                        'id_historia' => $id_historia,
                        'ta' => $_POST['ta'] ?? '',
                        'pulso' => (int)($_POST['pulso'] ?? 0),
                        'f_res' => (int)($_POST['f_res'] ?? 0),
                        'temperatura' => (float)($_POST['temperatura'] ?? 0),
                        'peso' => (float)($_POST['peso'] ?? 0),
                        'talla' => (float)($_POST['talla'] ?? 0)
                    ];
                    
                    $supabase->insert('signos_vitales', $signos_data);
                }
                
                // 4. Crear diagnóstico si se proporciona
                if (!empty($_POST['id_cie10_principal'])) {
                    $diagnostico_data = [
                        'id_consulta' => $id_consulta,
                        'tipo_dx' => $_POST['tipo_dx'] ?? 'Principal',
                        'id_cie10_principal' => (int)$_POST['id_cie10_principal']
                    ];
                    
                    $supabase->insert('diagnosticos', $diagnostico_data);
                }
                
                $mensaje_creacion = "✅ Historia clínica creada exitosamente. ID: $id_historia";
                
                // Recargar datos para mostrar la nueva historia
                header("Location: index.php?doc_id={$_POST['documento_id']}&success=1");
                exit;
            }
        }
    } catch (Exception $e) {
        $mensaje_creacion = "❌ Error al crear historia clínica: " . $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Historia Clínica</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        
        <div class="encabezado">
            <h2>🏥 Sistema de Historia Clínica</h2>
        </div>
        
        <div class="content-area">

            <!-- Módulo de Consulta -->
            <div class="modulo-acordeon">
                <div class="modulo-header" onclick="toggleModulo('consulta')">
                    <h2>🔍 Consultar Paciente</h2>
                    <span class="toggle-icon">▼</span>
                </div>
                <div class="modulo-content" id="modulo-consulta">
                    <div class="search-section">
                        <form action="index.php" method="GET" class="search-form">
                            <input type="text" name="doc_id" placeholder="Ingrese Documento ID del Paciente (ej: 1000000246)" required 
                                   value="<?php echo htmlspecialchars($doc_id_buscado ?? ''); ?>">
                            <button type="submit">🔍 Buscar Historia</button>
                        </form>
                    </div>
                    
                    <?php if ($error_conexion): ?>
                        <div class="error">❌ <?php echo $error_conexion; ?></div>
                    <?php elseif ($doc_id_buscado && !$paciente_encontrado): ?>
                        <div class="error">
                            ❌ No se encontró ningún paciente con Documento ID: <strong><?php echo htmlspecialchars($doc_id_buscado); ?></strong>
                        </div>
                    <?php endif; ?>

            <?php if ($paciente_encontrado): ?>
                <div class="patient-info">
                    <h1>📋 Historia Clínica de <?php echo htmlspecialchars($paciente_encontrado['primer_nombre'] . ' ' . $paciente_encontrado['primer_apellido']); ?></h1>
                </div>
                
                <div class="resultados">
                
                    <h2>👤 Información del Paciente</h2>
                    <div class="doblecolumna">
                        <div class="columna">
                            <p>👤 Nombre Completo: <span><?php echo htmlspecialchars($paciente_encontrado['primer_nombre'] . ' ' . ($paciente_encontrado['segundo_nombre'] ?? '') . ' ' . $paciente_encontrado['primer_apellido'] . ' ' . ($paciente_encontrado['segundo_apellido'] ?? '')); ?></span></p>
                            <p>🎂 Fecha Nacimiento: <span><?php echo htmlspecialchars($paciente_encontrado['fecha_nacimiento'] ?? 'N/A'); ?></span></p>
                            <p>📅 Edad: <span><?php echo calcularEdad($paciente_encontrado['fecha_nacimiento']); ?></span></p>
                            <p>📞 Teléfono: <span><?php echo htmlspecialchars($paciente_encontrado['telefono'] ?? 'N/A'); ?></span></p>
                        </div>
                        <div class="columna">
                            <p>🆔 Documento: <span><?php echo htmlspecialchars($paciente_encontrado['documento_id']); ?></span></p>
                            <p>📍 Dirección: <span><?php echo htmlspecialchars($paciente_encontrado['direccion'] ?? 'N/A'); ?></span></p>
                            <p>🏥 EPS: <span><?php echo htmlspecialchars($paciente_encontrado['eps_nombre'] ?? 'N/A'); ?></span></p>
                            <p>⚕️ Régimen: <span><?php echo htmlspecialchars($paciente_encontrado['regimen_nombre'] ?? 'N/A'); ?></span></p>
                        </div>
                    </div>

                    <h2>📊 Historial de Ingresos Clínicos</h2>
                <?php if (!empty($historial_clinico)): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID Historia</th>
                                <th>Fecha Ingreso</th>
                                <th>Fecha Egreso</th>
                                <th>Estado</th>
                                <th>Análisis/Plan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($historial_clinico as $index => $historia): ?>
                                <?php 
                                    $estado = $historia['fecha_egreso'] ? 'Cerrada' : 'Abierta';
                                ?>
                                <tr class="historia-row" onclick="toggleHistoria(<?php echo $index; ?>)" style="cursor: pointer;">
                                    <td><?php echo htmlspecialchars($historia['id_historia']); ?></td>
                                    <td><?php echo htmlspecialchars($historia['fecha_ingreso']); ?></td>
                                    <td><?php echo htmlspecialchars($historia['fecha_egreso'] ?? 'N/A'); ?></td>
                                    <td><strong style="color: <?php echo $estado === 'Abierta' ? 'green' : 'blue'; ?>"><?php echo $estado; ?></strong></td>
                                    <td><?php echo htmlspecialchars(substr($historia['analisis_plan'] ?? '', 0, 80) . '...'); ?> <small style="color: #007bff;">👁️ Ver detalles</small></td>
                                </tr>
                                <tr class="historia-detalle-row" id="detalle-<?php echo $index; ?>" style="display: none;">
                                    <td colspan="5">
                                        <div class="historia-detalle-content">
                                            <div class="doblecolumna">
                                                <div class="columna">
                                                    <h4>📅 Información General</h4>
                                                    <p><strong>Fecha Ingreso:</strong> <?php echo htmlspecialchars($historia['fecha_ingreso']); ?></p>
                                                    <p><strong>Fecha Egreso:</strong> <?php echo htmlspecialchars($historia['fecha_egreso'] ?? 'Historia Abierta'); ?></p>
                                                    <p><strong>Estado:</strong> <span style="color: <?php echo $historia['fecha_egreso'] ? 'blue' : 'green'; ?>"><?php echo $historia['fecha_egreso'] ? 'Cerrada' : 'Abierta'; ?></span></p>
                                                    
                                                    <?php if (!empty($historia['consultas']) && ($historia['consultas'][0]['motivo_consulta'] || $historia['consultas'][0]['enfermedad_actual'])): ?>
                                                        <?php if ($historia['consultas'][0]['motivo_consulta']): ?>
                                                            <h4>🩺 Motivo de Consulta</h4>
                                                            <p><?php echo htmlspecialchars($historia['consultas'][0]['motivo_consulta']); ?></p>
                                                        <?php endif; ?>
                                                        
                                                        <?php if ($historia['consultas'][0]['enfermedad_actual']): ?>
                                                            <h4>📝 Enfermedad Actual</h4>
                                                            <p><?php echo htmlspecialchars($historia['consultas'][0]['enfermedad_actual']); ?></p>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($historia['revision_sistemas'] && ($historia['revision_sistemas']['respiratorio'] || $historia['revision_sistemas']['cardiovascular'] || $historia['revision_sistemas']['neurologico'])): ?>
                                                        <h4>🔍 Revisión por Sistemas</h4>
                                                        <?php if ($historia['revision_sistemas']['respiratorio']): ?><p><strong>Respiratorio:</strong> <?php echo htmlspecialchars($historia['revision_sistemas']['respiratorio']); ?></p><?php endif; ?>
                                                        <?php if ($historia['revision_sistemas']['cardiovascular']): ?><p><strong>Cardiovascular:</strong> <?php echo htmlspecialchars($historia['revision_sistemas']['cardiovascular']); ?></p><?php endif; ?>
                                                        <?php if ($historia['revision_sistemas']['neurologico']): ?><p><strong>Neurológico:</strong> <?php echo htmlspecialchars($historia['revision_sistemas']['neurologico']); ?></p><?php endif; ?>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <div class="columna">
                                                    <?php if ($historia['signos_vitales'] && ($historia['signos_vitales']['ta'] || $historia['signos_vitales']['pulso'] || $historia['signos_vitales']['temperatura'])): ?>
                                                        <h4>🩺 Signos Vitales</h4>
                                                        <?php if ($historia['signos_vitales']['ta']): ?><p><strong>Tensión Arterial:</strong> <?php echo htmlspecialchars($historia['signos_vitales']['ta']); ?></p><?php endif; ?>
                                                        <?php if ($historia['signos_vitales']['pulso']): ?><p><strong>Pulso:</strong> <?php echo htmlspecialchars($historia['signos_vitales']['pulso']); ?> lpm</p><?php endif; ?>
                                                        <?php if ($historia['signos_vitales']['f_res']): ?><p><strong>Frecuencia Respiratoria:</strong> <?php echo htmlspecialchars($historia['signos_vitales']['f_res']); ?> rpm</p><?php endif; ?>
                                                        <?php if ($historia['signos_vitales']['temperatura']): ?><p><strong>Temperatura:</strong> <?php echo htmlspecialchars($historia['signos_vitales']['temperatura']); ?>°C</p><?php endif; ?>
                                                        <?php if ($historia['signos_vitales']['peso']): ?><p><strong>Peso:</strong> <?php echo htmlspecialchars($historia['signos_vitales']['peso']); ?> kg</p><?php endif; ?>
                                                        <?php if ($historia['signos_vitales']['talla']): ?><p><strong>Talla:</strong> <?php echo htmlspecialchars($historia['signos_vitales']['talla']); ?> cm</p><?php endif; ?>
                                                        <?php if ($historia['signos_vitales']['sp02']): ?><p><strong>SpO2:</strong> <?php echo htmlspecialchars($historia['signos_vitales']['sp02']); ?>%</p><?php endif; ?>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($historia['examen_fisico'] && ($historia['examen_fisico']['cabeza'] || $historia['examen_fisico']['corazon'] || $historia['examen_fisico']['pulmon'] || $historia['examen_fisico']['abdomen'])): ?>
                                                        <h4>🔍 Examen Físico</h4>
                                                        <?php if ($historia['examen_fisico']['cabeza']): ?><p><strong>Cabeza:</strong> <?php echo htmlspecialchars($historia['examen_fisico']['cabeza']); ?></p><?php endif; ?>
                                                        <?php if ($historia['examen_fisico']['corazon']): ?><p><strong>Corazón:</strong> <?php echo htmlspecialchars($historia['examen_fisico']['corazon']); ?></p><?php endif; ?>
                                                        <?php if ($historia['examen_fisico']['pulmon']): ?><p><strong>Pulmón:</strong> <?php echo htmlspecialchars($historia['examen_fisico']['pulmon']); ?></p><?php endif; ?>
                                                        <?php if ($historia['examen_fisico']['abdomen']): ?><p><strong>Abdomen:</strong> <?php echo htmlspecialchars($historia['examen_fisico']['abdomen']); ?></p><?php endif; ?>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            
                                            <?php if ($historia['analisis_plan']): ?>
                                                <div style="margin-top: 15px;">
                                                    <h4>📝 Análisis y Plan</h4>
                                                    <p><?php echo htmlspecialchars($historia['analisis_plan']); ?></p>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>Este paciente no tiene historias clínicas registradas.</p>
                <?php endif; ?>

                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Módulo de Creación -->
            <div class="modulo-acordeon">
                <div class="modulo-header" onclick="toggleModulo('creacion')">
                    <h2>➕ Crear Nueva Historia Clínica</h2>
                    <span class="toggle-icon">▼</span>
                </div>
                <div class="modulo-content" id="modulo-creacion" style="display: none;">
                    <div class="form-section">
                <h2>➕ Crear Nueva Historia Clínica / Consulta</h2>
                <?php if ($mensaje_creacion): ?>
                    <div class="<?php echo strpos($mensaje_creacion, '❌') !== false ? 'error' : 'success'; ?>"><?php echo $mensaje_creacion; ?></div>
                <?php endif; ?>
                
                <form action="index.php" method="POST">
                    <input type="hidden" name="action" value="crear_historia">
                    
                    <div class="doblecolumna">
                        <div class="form-group">
                            <label for="documento_id">🆔 Número de Documento (*):</label>
                            <input type="text" id="documento_id" name="documento_id" placeholder="Ej: 1000000246" required 
                                   value="<?php echo $paciente_encontrado ? $paciente_encontrado['documento_id'] : ''; ?>">
                            <small>Número de documento del paciente</small>
                        </div>
                        <div class="form-group">
                            <label for="medico_id">👨⚕️ Médico (*):</label>
                            <select id="medico_id" name="medico_id" required>
                                <option value="">Seleccione un médico</option>
                                <?php foreach ($medicos_lista as $medico): ?>
                                    <option value="<?php echo $medico['id']; ?>">
                                        <?php echo htmlspecialchars($medico['primer_nombre'] . ' ' . $medico['primer_apellido']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <h3>📝 Datos de la Consulta</h3>
                    
                    <div class="form-group">
                        <label for="motivo_consulta">🔍 Motivo de Consulta (*):</label>
                        <textarea id="motivo_consulta" name="motivo_consulta" placeholder="Describa el motivo principal de la consulta..." required></textarea>
                    </div>

                    <div class="form-group">
                        <label for="enfermedad_actual">🩺 Enfermedad Actual (*):</label>
                        <textarea id="enfermedad_actual" name="enfermedad_actual" placeholder="Describa la enfermedad actual del paciente..." required></textarea>
                    </div>

                    <div class="form-group">
                        <label for="analisis_plan">📝 Análisis y Plan:</label>
                        <textarea id="analisis_plan" name="analisis_plan" placeholder="Análisis clínico y plan de manejo..."></textarea>
                    </div>
                    
                    <h3>🩺 Signos Vitales (Opcional)</h3>
                    
                    <div class="doblecolumna">
                        <div class="columna">
                            <div class="form-group">
                                <label for="ta">Tensión Arterial:</label>
                                <input type="text" id="ta" name="ta" placeholder="Ej: 120/80">
                            </div>
                            <div class="form-group">
                                <label for="pulso">Pulso (lpm):</label>
                                <input type="number" id="pulso" name="pulso" placeholder="Ej: 72">
                            </div>
                            <div class="form-group">
                                <label for="f_res">Frecuencia Respiratoria (rpm):</label>
                                <input type="number" id="f_res" name="f_res" placeholder="Ej: 16">
                            </div>
                        </div>
                        <div class="columna">
                            <div class="form-group">
                                <label for="temperatura">Temperatura (°C):</label>
                                <input type="number" step="0.1" id="temperatura" name="temperatura" placeholder="Ej: 36.5">
                            </div>
                            <div class="form-group">
                                <label for="peso">Peso (kg):</label>
                                <input type="number" step="0.1" id="peso" name="peso" placeholder="Ej: 70.5">
                            </div>
                            <div class="form-group">
                                <label for="talla">Talla (cm):</label>
                                <input type="number" step="0.1" id="talla" name="talla" placeholder="Ej: 170">
                            </div>
                        </div>
                    </div>
                    
                    <h3>📋 Diagnóstico (Opcional)</h3>
                    
                    <div class="doblecolumna">
                        <div class="form-group">
                            <label for="tipo_dx">Tipo de Diagnóstico:</label>
                            <select id="tipo_dx" name="tipo_dx">
                                <option value="Principal">Principal</option>
                                <option value="Secundario">Secundario</option>
                                <option value="Relacionado">Relacionado</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="id_cie10_principal">ID CIE-10:</label>
                            <input type="number" id="id_cie10_principal" name="id_cie10_principal" placeholder="ID del código CIE-10">
                            <small>Debe existir en la tabla cie10</small>
                        </div>
                    </div>
                    
                        <button type="submit" class="btn-primary">💾 Crear Historia Clínica Completa</button>
                    </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
    function toggleHistoria(index) {
        const detalle = document.getElementById('detalle-' + index);
        const isVisible = detalle.style.display !== 'none';
        
        // Cerrar todos los detalles abiertos
        document.querySelectorAll('.historia-detalle-row').forEach(row => {
            row.style.display = 'none';
        });
        
        // Si no estaba visible, mostrarlo
        if (!isVisible) {
            detalle.style.display = 'table-row';
        }
    }
    
    function toggleModulo(modulo) {
        const content = document.getElementById('modulo-' + modulo);
        const icon = event.target.closest('.modulo-header').querySelector('.toggle-icon');
        const isVisible = content.style.display !== 'none';
        
        if (isVisible) {
            content.style.display = 'none';
            icon.textContent = '▼';
        } else {
            content.style.display = 'block';
            icon.textContent = '▲';
        }
    }
    </script>
</body>
</html>