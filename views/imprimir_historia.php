<?php
/**
 * Vista de Impresión - Historia Clínica Ambulatoria
 * Formato optimizado para impresión en papel carta
 */
require_once '../vendor/autoload.php';
require_once '../includes/auth_helper.php';

session_start();
requireLogin();

use App\SupabaseClient;
use App\HistoriaClinica;
use App\Paciente;
use App\Consulta;
use App\SignosVitales;
use App\ExamenFisico;
use App\RevisionSistemas;
use App\Diagnostico;
use App\FormulaMedica;
use App\Medico;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$supabase = new SupabaseClient($_ENV['SUPABASE_URL'], $_ENV['SUPABASE_KEY']);

// Instanciar modelos
$historiaModel = new HistoriaClinica($supabase);
$pacienteModel = new Paciente($supabase);
$consultaModel = new Consulta($supabase);
$signosModel = new SignosVitales($supabase);
$examenModel = new ExamenFisico($supabase);
$revisionModel = new RevisionSistemas($supabase);
$diagnosticoModel = new Diagnostico($supabase);
$formulaModel = new FormulaMedica($supabase);
$medicoModel = new Medico($supabase);

// Verificar ID
if (!isset($_GET['id'])) {
    die('Error: ID de historia no proporcionado');
}

$id = (int)$_GET['id'];
$error = '';

// Cargar todos los datos
try {
    // Historia clínica base
    $historia = $historiaModel->obtenerPorId($id);
    if (!$historia) {
        throw new Exception("Historia clínica no encontrada");
    }
    
    // Paciente
    $paciente = $pacienteModel->obtenerPorId($historia['id_paciente']);
    
    // Datos relacionados con catálogos
    $datosPaciente = cargarDatosExtendidosPaciente($supabase, $paciente);
    
    // Consulta (motivo y enfermedad actual)
    $consulta = obtenerConsultaDeHistoria($supabase, $id, $historia['id_paciente']);
    
    // Signos vitales
    $signos = $signosModel->obtenerPorHistoria($id);
    
    // Revisión por sistemas
    $revision = $revisionModel->obtenerPorHistoria($id);
    
    // Examen físico
    $examen = $examenModel->obtenerPorHistoria($id);
    
    // Antecedentes
    $antecedentes = cargarAntecedentes($supabase, $historia['id_paciente']);
    
    // Diagnósticos
    $diagnosticos = cargarDiagnosticos($supabase, $consulta['id_consulta'] ?? null);
    
    // Solicitudes
    $solicitudes = cargarSolicitudes($supabase, $id);
    
    // Fórmulas médicas
    $formulas = $formulaModel->obtenerPorHistoria($id);
    
    // Médico
    $medico = cargarMedico($supabase, $consulta['medico_id'] ?? null);
    
} catch (Exception $e) {
    $error = $e->getMessage();
}

// =====================================================
// FUNCIONES AUXILIARES
// =====================================================

function cargarDatosExtendidosPaciente($supabase, $paciente) {
    $datos = [];
    
    if (!$paciente || !is_array($paciente)) {
        return $datos;
    }
    
    // Función auxiliar para obtener valor seguro de un catálogo
    $obtenerValorCatalogo = function($supabase, $tabla, $id) {
        if (empty($id)) return '';
        try {
            $resultado = $supabase->select($tabla, '*', "id=eq.$id");
            if (!empty($resultado) && is_array($resultado[0])) {
                // Intentar obtener 'nombre', si no existe, buscar 'descripcion' o primera columna de texto
                return $resultado[0]['nombre'] ?? $resultado[0]['descripcion'] ?? '';
            }
        } catch (Exception $e) {
            // Silenciar errores de catálogos no encontrados
        }
        return '';
    };
    
    // Cargar cada catálogo de forma segura
    $datos['ciudad'] = $obtenerValorCatalogo($supabase, 'ciudades', $paciente['ciudad_id'] ?? null);
    $datos['barrio'] = $obtenerValorCatalogo($supabase, 'barrio', $paciente['barrio_id'] ?? null);
    $datos['estado_civil'] = $obtenerValorCatalogo($supabase, 'estado_civil', $paciente['estado_civil_id'] ?? null);
    $datos['sexo'] = $obtenerValorCatalogo($supabase, 'sexo', $paciente['sexo_id'] ?? null);
    $datos['etnia'] = $obtenerValorCatalogo($supabase, 'etnia', $paciente['etnia_id'] ?? null);
    $datos['eps'] = $obtenerValorCatalogo($supabase, 'eps', $paciente['eps_id'] ?? null);
    $datos['regimen'] = $obtenerValorCatalogo($supabase, 'regimen', $paciente['regimen_id'] ?? null);
    $datos['escolaridad'] = $obtenerValorCatalogo($supabase, 'escolaridad', $paciente['escolaridad_id'] ?? null);
    $datos['gs_rh'] = $obtenerValorCatalogo($supabase, 'gs_rh', $paciente['gs_rh_id'] ?? null);
    
    // Acudiente - manejo especial
    if (!empty($paciente['acudiente_id'])) {
        try {
            $acudiente = $supabase->select('acudientes', '*', "id=eq." . $paciente['acudiente_id']);
            if (!empty($acudiente) && is_array($acudiente[0])) {
                $datos['acudiente_nombre'] = $acudiente[0]['nombre'] ?? '';
                $datos['acudiente_telefono'] = $acudiente[0]['telefono'] ?? '';
                $datos['acudiente_direccion'] = $acudiente[0]['direccion'] ?? '';
                $datos['acudiente_parentesco'] = $acudiente[0]['parentesco'] ?? '';
            }
        } catch (Exception $e) {
            // Silenciar errores
        }
    }
    
    return $datos;
}

function obtenerConsultaDeHistoria($supabase, $idHistoria, $idPaciente) {
    // Buscar consulta asociada a esta historia o al paciente
    $consultas = $supabase->select('consultas', '*', "id_paciente=eq.$idPaciente", 'id_consulta.desc');
    return !empty($consultas) ? $consultas[0] : [];
}

function cargarAntecedentes($supabase, $idPaciente) {
    try {
        $antecedentes = $supabase->select('antecedentes', '*', "id_paciente=eq.$idPaciente");
        return $antecedentes ?? [];
    } catch (Exception $e) {
        return [];
    }
}

function cargarDiagnosticos($supabase, $idConsulta) {
    if (!$idConsulta) return [];
    
    try {
        $diagnosticos = $supabase->select('diagnosticos', '*', "id_consulta=eq.$idConsulta");
        
        // Cargar información CIE-10 para cada diagnóstico
        foreach ($diagnosticos as &$dx) {
            if (!empty($dx['id_cie10_principal'])) {
                $cie10 = $supabase->select('cie10', '*', "id=eq." . $dx['id_cie10_principal']);
                $dx['cie10_principal'] = !empty($cie10) ? $cie10[0] : null;
            }
            if (!empty($dx['id_cie10_relacionado'])) {
                $cie10 = $supabase->select('cie10', '*', "id=eq." . $dx['id_cie10_relacionado']);
                $dx['cie10_relacionado'] = !empty($cie10) ? $cie10[0] : null;
            }
        }
        
        return $diagnosticos;
    } catch (Exception $e) {
        return [];
    }
}

function cargarSolicitudes($supabase, $idHistoria) {
    try {
        $solicitudes = $supabase->select('solicitudes', '*', "id_historia=eq.$idHistoria");
        return $solicitudes ?? [];
    } catch (Exception $e) {
        return [];
    }
}

function cargarMedico($supabase, $medicoId) {
    if (!$medicoId) return null;
    
    try {
        $medico = $supabase->select('medicos', '*', "id=eq.$medicoId");
        if (!empty($medico)) {
            $medico = $medico[0];
            
            // Cargar especialidad
            if (!empty($medico['especialidad_id'])) {
                $especialidad = $supabase->select('especialidades', '*', "id=eq." . $medico['especialidad_id']);
                $medico['especialidad'] = !empty($especialidad) ? $especialidad[0]['nombre'] : '';
            }
            
            return $medico;
        }
    } catch (Exception $e) {
        return null;
    }
    
    return null;
}

function calcularEdad($fechaNacimiento) {
    if (empty($fechaNacimiento)) return '';
    
    $nacimiento = new DateTime($fechaNacimiento);
    $hoy = new DateTime();
    $diff = $hoy->diff($nacimiento);
    return $diff->y . ' Años';
}

function calcularIMC($peso, $talla) {
    if (empty($peso) || empty($talla) || $talla == 0 || $peso == 0) return '';
    
    $peso = floatval($peso);
    $talla = floatval($talla);
    
    // Detectar si la talla está en metros o centímetros
    // Si talla > 3, asumimos centímetros; si <= 3, asumimos metros
    if ($talla > 3) {
        $tallaM = $talla / 100; // convertir cm a metros
    } else {
        $tallaM = $talla; // ya está en metros
    }
    
    if ($tallaM == 0) return '';
    
    $imc = $peso / ($tallaM * $tallaM);
    
    // Validar que el IMC sea razonable (entre 10 y 60)
    if ($imc < 10 || $imc > 60) {
        return ''; // Valor fuera de rango, probablemente error en datos
    }
    
    return number_format($imc, 2);
}

function getNombreCompleto($paciente) {
    $nombre = $paciente['primer_nombre'] ?? '';
    if (!empty($paciente['segundo_nombre'])) $nombre .= ' ' . $paciente['segundo_nombre'];
    $nombre .= ' ' . ($paciente['primer_apellido'] ?? '');
    if (!empty($paciente['segundo_apellido'])) $nombre .= ' ' . $paciente['segundo_apellido'];
    return strtoupper(trim($nombre));
}

function getNombreCompletoMedico($medico) {
    if (!$medico) return '';
    $nombre = $medico['primer_nombre'] ?? '';
    if (!empty($medico['segundo_nombre'])) $nombre .= ' ' . $medico['segundo_nombre'];
    $nombre .= ' ' . ($medico['primer_apellido'] ?? '');
    if (!empty($medico['segundo_apellido'])) $nombre .= ' ' . $medico['segundo_apellido'];
    return strtoupper(trim($nombre));
}

function e($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function formatFecha($fecha) {
    if (empty($fecha)) return '';
    return date('d/m/Y H:i', strtotime($fecha));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historia Clínica #<?= $id ?> - Impresión</title>
    <link rel="stylesheet" href="../assets/css/print.css">
</head>
<body>
    <?php if ($error): ?>
        <div style="color: red; padding: 20px; text-align: center;">
            <h2>❌ Error</h2>
            <p><?= e($error) ?></p>
            <a href="listar_historias.php" style="color: blue;">← Volver a historias</a>
        </div>
    <?php else: ?>
    
    <!-- Barra de acciones (no se imprime) -->
    <div class="actions-bar no-print">
        <button onclick="window.print()" class="btn btn-print">
            🖨️ Imprimir Historia
        </button>
        <a href="ver_historia.php?id=<?= $id ?>" class="btn btn-back">
            ← Volver
        </a>
        <a href="listar_historias.php" class="btn btn-back">
            📋 Lista de Historias
        </a>
    </div>

    <!-- Documento para impresión -->
    <div class="print-document">
        
        <!-- ENCABEZADO INSTITUCIONAL -->
        <div class="print-header">
            <div class="print-header-content">
                <!-- Logo placeholder - reemplazar con logo real -->
                <div style="width: 80px; height: 60px; border: 1px solid #ccc; display: flex; align-items: center; justify-content: center; font-size: 8pt; color: #666;">
                    LOGO
                </div>
                <div class="print-institution">
                    <h1>INSTITUCIÓN DE SALUD</h1>
                    <p>Dirección de la Institución - Teléfono</p>
                    <p>NIT: 000.000.000-0</p>
                </div>
            </div>
            <div class="print-title">HISTORIA CLÍNICA AMBULATORIA</div>
        </div>

        <!-- INFORMACIÓN DE LA HISTORIA -->
        <div class="print-info-row">
            <div style="flex: 1;"><strong>No. H.C.</strong> <?= e($historia['id_historia']) ?> - <?= e($paciente['documento_id']) ?></div>
            <div style="flex: 1;"><strong>Fecha Ingreso</strong> <?= formatFecha($historia['fecha_ingreso']) ?></div>
            <div style="flex: 1;"><strong>Fecha Egreso</strong> <?= formatFecha($historia['fecha_egreso']) ?></div>
        </div>

        <!-- IDENTIFICACIÓN DEL PACIENTE -->
        <table class="print-table">
            <tr><th colspan="4" class="print-section-header">IDENTIFICACIÓN DEL PACIENTE</th></tr>
        </table>
        <table class="print-patient-info">
            <tr>
                <td class="label">PACIENTE</td>
                <td class="value"><?= getNombreCompleto($paciente) ?></td>
                <td class="label">DOC. ID.</td>
                <td class="value"><?= e($paciente['documento_id']) ?></td>
            </tr>
            <tr>
                <td class="label">LUGAR NAC.</td>
                <td class="value"><?= e($datosPaciente['ciudad'] ?? '') ?></td>
                <td class="label">FEC. NAC.</td>
                <td class="value"><?= !empty($paciente['fecha_nacimiento']) ? date('d/m/Y', strtotime($paciente['fecha_nacimiento'])) : '' ?></td>
            </tr>
            <tr>
                <td class="label">E. CIVIL</td>
                <td class="value"><?= e($datosPaciente['estado_civil'] ?? '') ?></td>
                <td class="label">EDAD</td>
                <td class="value"><?= calcularEdad($paciente['fecha_nacimiento']) ?></td>
            </tr>
            <tr>
                <td class="label">OCUPACIÓN</td>
                <td class="value"><?= e($paciente['ocupacion'] ?? '') ?></td>
                <td class="label">SEXO</td>
                <td class="value"><?= e($datosPaciente['sexo'] ?? '') ?></td>
            </tr>
            <tr>
                <td class="label">CIUDAD</td>
                <td class="value"><?= e($datosPaciente['ciudad'] ?? '') ?></td>
                <td class="label">BARRIO</td>
                <td class="value"><?= e($datosPaciente['barrio'] ?? '') ?></td>
            </tr>
            <tr>
                <td class="label">DIRECCIÓN RESIDENCIA</td>
                <td class="value"><?= e($paciente['direccion'] ?? '') ?></td>
                <td class="label">TELÉFONO</td>
                <td class="value"><?= e($paciente['telefono'] ?? '') ?></td>
            </tr>
            <tr>
                <td class="label">ESTRATO</td>
                <td class="value"><?= e($paciente['estrato'] ?? '') ?></td>
                <td class="label">GS - RH</td>
                <td class="value"><?= e($datosPaciente['gs_rh'] ?? '') ?></td>
            </tr>
            <tr>
                <td class="label">ACUDIENTE</td>
                <td class="value"><?= e($datosPaciente['acudiente_nombre'] ?? '') ?></td>
                <td class="label">TELÉFONO</td>
                <td class="value"><?= e($datosPaciente['acudiente_telefono'] ?? '') ?></td>
            </tr>
            <tr>
                <td class="label">DIRECCIÓN ACUDIENTE</td>
                <td class="value"><?= e($datosPaciente['acudiente_direccion'] ?? '') ?></td>
                <td class="label">PARENTESCO</td>
                <td class="value"><?= e($datosPaciente['acudiente_parentesco'] ?? '') ?></td>
            </tr>
            <tr>
                <td class="label">EMPRESA</td>
                <td class="value"><?= e($paciente['empresa'] ?? '') ?></td>
                <td class="label">RÉGIMEN</td>
                <td class="value"><?= e($datosPaciente['regimen'] ?? '') ?></td>
            </tr>
            <tr>
                <td class="label">ETNIA</td>
                <td class="value"><?= e($datosPaciente['etnia'] ?? '') ?></td>
                <td class="label">G. POBLACIONAL</td>
                <td class="value"><?= e($paciente['grupo_poblacional'] ?? '') ?></td>
            </tr>
            <tr>
                <td class="label">ESCOLARIDAD</td>
                <td class="value"><?= e($datosPaciente['escolaridad'] ?? '') ?></td>
                <td class="label">EPS</td>
                <td class="value"><?= e($datosPaciente['eps'] ?? '') ?></td>
            </tr>
        </table>

        <!-- MOTIVO DE CONSULTA Y ENFERMEDAD ACTUAL -->
        <table class="print-table">
            <tr><td class="print-section-header">MOTIVO CONSULTA</td></tr>
            <tr><td style="padding: 5px;"><?= e($consulta['motivo_consulta'] ?? $historia['motivo_consulta'] ?? '') ?></td></tr>
            <tr><td class="print-section-header">ENFERMEDAD ACTUAL</td></tr>
            <tr><td style="padding: 5px;"><?= e($consulta['enfermedad_actual'] ?? '') ?></td></tr>
        </table>

        <!-- REVISIÓN POR SISTEMAS -->
        <table class="print-table">
            <tr><th colspan="2" class="print-section-header">HALLAZGOS REVISIÓN POR SISTEMAS</th></tr>
        </table>
        <table class="print-systems">
            <tr>
                <td><span class="system-label">1. RESPIRATORIO:</span> <?= e($revision['respiratorio'] ?? 'Normal') ?></td>
                <td><span class="system-label">7. PIEL Y ANEXOS:</span> <?= e($revision['piel_y_anexos'] ?? 'Normal') ?></td>
            </tr>
            <tr>
                <td><span class="system-label">2. ORGANOS SENTIDOS:</span> <?= e($revision['organos_sentidos'] ?? 'Normal') ?></td>
                <td><span class="system-label">8. OSTEOMUSCULAR:</span> <?= e($revision['osteomuscular'] ?? 'Normal') ?></td>
            </tr>
            <tr>
                <td><span class="system-label">3. CARDIOVASCULAR:</span> <?= e($revision['cardiovascular'] ?? 'Normal') ?></td>
                <td><span class="system-label">9. ENDOCRINO:</span> <?= e($revision['endocrino'] ?? 'Normal') ?></td>
            </tr>
            <tr>
                <td><span class="system-label">4. GASTROINTESTINAL:</span> <?= e($revision['gastrointestinal'] ?? 'Normal') ?></td>
                <td><span class="system-label">10. PSICOSOCIAL:</span> <?= e($revision['psicosocial'] ?? 'Normal') ?></td>
            </tr>
            <tr>
                <td><span class="system-label">5. GENITOURINARIO:</span> <?= e($revision['genitourinario'] ?? 'Normal') ?></td>
                <td><span class="system-label">11. LINFÁTICO:</span> <?= e($revision['linfatico'] ?? 'Normal') ?></td>
            </tr>
            <tr>
                <td><span class="system-label">6. NEUROLÓGICO:</span> <?= e($revision['neurologico'] ?? 'Normal') ?></td>
                <td><span class="system-label">12. OTRO:</span> <?= e($revision['otro'] ?? '') ?></td>
            </tr>
        </table>

        <!-- ANTECEDENTES -->
        <table class="print-table">
            <tr><th colspan="2" class="print-section-header">DESCRIPCIÓN ANTECEDENTES</th></tr>
        </table>
        <table class="print-antecedents">
            <?php
            $tiposAntecedentes = [
                'FAMILIARES', 'QUIRÚRGICOS', 'MEDICAMENTOS EN USO', 
                'TÓXICO-ALÉRGICOS', 'PATOLÓGICOS', 'TRAUMÁTICOS',
                'SINTOMÁTICO', 'RESPIRATORIO', 'SINTOMÁTICO DE PIEL',
                'OCUPACIONAL', 'VACUNALES'
            ];
            foreach ($tiposAntecedentes as $tipo):
                $valorAnt = '';
                foreach ($antecedentes as $ant) {
                    if (strtoupper($ant['tipo'] ?? '') == $tipo) {
                        $valorAnt = $ant['descripcion'] ?? '';
                        break;
                    }
                }
            ?>
            <tr>
                <td class="ant-type"><?= $tipo ?></td>
                <td><?= e($valorAnt) ?: 'Negativo' ?></td>
            </tr>
            <?php endforeach; ?>
        </table>

        <!-- SIGNOS VITALES -->
        <table class="print-table">
            <tr><th colspan="12" class="print-section-header">SIGNOS VITALES</th></tr>
        </table>
        <table class="print-vitals">
            <tr>
                <th>T.A.</th>
                <th>PULSO</th>
                <th>F. RES.</th>
                <th>T°</th>
                <th>PESO</th>
                <th>TALLA</th>
                <th>PC</th>
                <th>SP02</th>
                <th>IMC</th>
                <th>RCV</th>
            </tr>
            <tr>
                <td><?= e($signos['ta'] ?? '') ?></td>
                <td><?= e($signos['pulso'] ?? '') ?></td>
                <td><?= e($signos['f_res'] ?? '') ?></td>
                <td><?= e($signos['temperatura'] ?? '') ?></td>
                <td><?= e($signos['peso'] ?? '') ?></td>
                <td><?= e($signos['talla'] ?? '') ?></td>
                <td><?= e($signos['pc'] ?? '') ?></td>
                <td><?= e($signos['sp02'] ?? '') ?></td>
                <td><?= calcularIMC($signos['peso'] ?? 0, $signos['talla'] ?? 0) ?></td>
                <td><?= e($signos['rcv'] ?? '') ?></td>
            </tr>
        </table>

        <!-- EXAMEN FÍSICO -->
        <table class="print-table">
            <tr><th colspan="2" class="print-section-header">HALLAZGOS EXAMEN FÍSICO</th></tr>
        </table>
        <table class="print-exam">
            <tr>
                <td><span class="exam-num">1. CABEZA:</span> <?= e($examen['cabeza'] ?? 'Normal') ?></td>
                <td><span class="exam-num">11. ABDOMEN:</span> <?= e($examen['abdomen'] ?? 'Normal') ?></td>
            </tr>
            <tr>
                <td><span class="exam-num">2. OJOS:</span> <?= e($examen['ojos'] ?? 'Normal') ?></td>
                <td><span class="exam-num">12. PELVIS:</span> <?= e($examen['pelvis'] ?? 'Normal') ?></td>
            </tr>
            <tr>
                <td><span class="exam-num">3. OÍDOS:</span> <?= e($examen['oidos'] ?? 'Normal') ?></td>
                <td><span class="exam-num">13. TACTO RECTAL:</span> <?= e($examen['tacto_rectal'] ?? 'Normal') ?></td>
            </tr>
            <tr>
                <td><span class="exam-num">4. NARIZ:</span> <?= e($examen['nariz'] ?? 'Normal') ?></td>
                <td><span class="exam-num">14. GENITOURINARIO:</span> <?= e($examen['genitourinario'] ?? 'Normal') ?></td>
            </tr>
            <tr>
                <td><span class="exam-num">5. BOCA:</span> <?= e($examen['boca'] ?? 'Normal') ?></td>
                <td><span class="exam-num">15. EXTREMIDADES SUP.:</span> <?= e($examen['extremidades_sup'] ?? 'Normal') ?></td>
            </tr>
            <tr>
                <td><span class="exam-num">6. GARGANTA:</span> <?= e($examen['garganta'] ?? 'Normal') ?></td>
                <td><span class="exam-num">16. EXTREMIDADES INF.:</span> <?= e($examen['extremidades_inf'] ?? 'Normal') ?></td>
            </tr>
            <tr>
                <td><span class="exam-num">7. CUELLO:</span> <?= e($examen['cuello'] ?? 'Normal') ?></td>
                <td><span class="exam-num">17. ESPALDA:</span> <?= e($examen['espalda'] ?? 'Normal') ?></td>
            </tr>
            <tr>
                <td><span class="exam-num">8. TÓRAX:</span> <?= e($examen['torax'] ?? 'Normal') ?></td>
                <td><span class="exam-num">18. PIEL:</span> <?= e($examen['piel'] ?? 'Normal') ?></td>
            </tr>
            <tr>
                <td><span class="exam-num">9. CORAZÓN:</span> <?= e($examen['corazon'] ?? 'Normal') ?></td>
                <td><span class="exam-num">19. ENDOCRINO:</span> <?= e($examen['endocrino'] ?? 'Normal') ?></td>
            </tr>
            <tr>
                <td><span class="exam-num">10. PULMÓN:</span> <?= e($examen['pulmon'] ?? 'Normal') ?></td>
                <td><span class="exam-num">20. SISTEMA NERVIOSO:</span> <?= e($examen['sistema_nervioso'] ?? 'Normal') ?></td>
            </tr>
        </table>

        <!-- EVALUACIÓN PARACLÍNICOS -->
        <table class="print-table">
            <tr><td class="print-section-header">EVALUACIÓN PARACLÍNICOS</td></tr>
            <tr><td style="padding: 5px; min-height: 40px;"><?= nl2br(e($historia['observaciones'] ?? '')) ?></td></tr>
        </table>

        <!-- DIAGNÓSTICOS -->
        <table class="print-diagnosis">
            <?php 
            $dxPrincipal = null;
            $dxRelacionado = null;
            foreach ($diagnosticos as $dx) {
                if (strtoupper($dx['tipo_dx'] ?? '') == 'PRINCIPAL') {
                    $dxPrincipal = $dx;
                } else {
                    $dxRelacionado = $dx;
                }
            }
            ?>
            <tr>
                <td class="dx-type">DX PRINCIPAL</td>
                <td><?= $dxPrincipal && isset($dxPrincipal['cie10_principal']) ? 
                    e($dxPrincipal['cie10_principal']['codigo'] . ' - ' . $dxPrincipal['cie10_principal']['descripcion']) : 
                    e($historia['diagnostico'] ?? '') ?></td>
            </tr>
            <tr>
                <td class="dx-type">TIPO DX</td>
                <td><?= $dxPrincipal ? e($dxPrincipal['tipo_dx'] ?? 'CONFIRMADO') : '' ?></td>
            </tr>
            <tr>
                <td class="dx-type">DX RELACIONADO</td>
                <td><?= $dxRelacionado && isset($dxRelacionado['cie10_principal']) ? 
                    e($dxRelacionado['cie10_principal']['codigo'] . ' - ' . $dxRelacionado['cie10_principal']['descripcion']) : '' ?></td>
            </tr>
            <tr>
                <td class="dx-type">ANÁLISIS Y PLAN</td>
                <td><?= nl2br(e($historia['analisis_plan'] ?? '')) ?></td>
            </tr>
        </table>

        <!-- SOLICITUDES -->
        <?php if (!empty($solicitudes)): ?>
        <table class="print-orders">
            <tr><th colspan="5" class="print-section-header">SOLICITUDES</th></tr>
            <tr>
                <th>CÓDIGO</th>
                <th>DESCRIPCIÓN</th>
                <th>FECHA - HORA</th>
                <th>CAN</th>
                <th>PERSONAL</th>
            </tr>
            <?php foreach ($solicitudes as $sol): ?>
            <tr>
                <td><?= e($sol['codigo'] ?? '') ?></td>
                <td><?= e($sol['descripcion'] ?? '') ?></td>
                <td><?= formatFecha($sol['fecha'] ?? $sol['created_at'] ?? '') ?></td>
                <td><?= e($sol['cantidad'] ?? '1') ?></td>
                <td><?= e($sol['personal'] ?? getNombreCompletoMedico($medico)) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>

        <!-- FÓRMULAS MÉDICAS -->
        <?php if (!empty($formulas)): ?>
        <table class="print-orders">
            <tr><th colspan="5" class="print-section-header">FÓRMULAS MÉDICAS</th></tr>
            <tr>
                <th>CÓDIGO</th>
                <th>DESCRIPCIÓN</th>
                <th>FECHA - HORA</th>
                <th>CAN</th>
                <th>PERSONAL</th>
            </tr>
            <?php foreach ($formulas as $formula): ?>
            <tr>
                <td><?= e($formula['medicamento_id'] ?? '') ?></td>
                <td><?= e($formula['dosis'] ?? '') ?></td>
                <td><?= formatFecha($formula['created_at'] ?? '') ?></td>
                <td><?= e($formula['cantidad'] ?? '') ?></td>
                <td><?= getNombreCompletoMedico($medico) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>

        <!-- FIRMA DEL MÉDICO -->
        <div class="print-signature">
            <div class="print-signature-box">
                <!-- Espacio para firma manuscrita o imagen de firma -->
                <?php if ($medico): ?>
                <p style="font-size: 12pt; margin: 0;">
                    <?= e($medico['primer_nombre'] . ' ' . ($medico['primer_apellido'] ?? '')) ?>
                </p>
                <?php endif; ?>
            </div>
            <?php if ($medico): ?>
            <div class="print-signature-name">
                Dr(a). <?= getNombreCompletoMedico($medico) ?>
            </div>
            <table class="print-signature-table">
                <tr>
                    <td><strong>REGISTRO NO.</strong></td>
                    <td><?= e($medico['num_registro'] ?? '') ?></td>
                    <td><strong>Esp.</strong></td>
                    <td><?= e($medico['especialidad'] ?? '') ?></td>
                </tr>
            </table>
            <?php else: ?>
            <div class="print-signature-name">
                _________________________________
            </div>
            <p class="print-signature-info">Firma del Profesional de Salud</p>
            <?php endif; ?>
        </div>

    </div><!-- /print-document -->
    
    <?php endif; ?>
</body>
</html>
