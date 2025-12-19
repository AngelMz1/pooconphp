<?php
require_once 'vendor/autoload.php';

use App\SupabaseClient;
use App\Paciente;
use App\HistoriaClinica;
use App\ReferenceData;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$supabase = new SupabaseClient($_ENV['SUPABASE_URL'], $_ENV['SUPABASE_KEY']);
$pacienteModel = new Paciente($supabase);
$historiaModel = new HistoriaClinica($supabase);
$refData = new ReferenceData($supabase);

$error = '';
$paciente = null;
$historias = [];
$totalHistorias = 0;

// Referencias para mostrar nombres en lugar de IDs
$referencias = [
    'tipos_documento' => [],
    'sexos' => [],
    'estados_civiles' => [],
    'ciudades' => [],
    'eps' => [],
    'regimenes' => [],
    'grupos_sanguineos' => [],
    'etnias' => [],
    'escolaridades' => [],
    'orientaciones_sexuales' => [],
    'acudientes' => []
];

if (!isset($_GET['id'])) {
    header('Location: listar_pacientes.php');
    exit;
}

$id = $_GET['id'];

try {
    $paciente = $pacienteModel->obtenerPorId($id);
    if (!$paciente) {
        throw new Exception("Paciente no encontrado");
    }
    
    $historias = $historiaModel->obtenerPorPaciente($id);
    $totalHistorias = count($historias);
    
    // Cargar datos de referencia para mostrar nombres
    $formData = $refData->getAllForPatientForm();
    $referencias = $formData;
    
} catch (Exception $e) {
    $error = $e->getMessage();
}

// Helper para obtener nombre de referencia
function getNombreReferencia($array, $id, $campo = 'nombre') {
    foreach ($array as $item) {
        if ($item['id'] == $id) {
            return $item[$campo] ?? 'N/A';
        }
    }
    return 'No especificado';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles del Paciente (Completo) - Sistema Médico</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        .info-card {
            background: var(--gray-50);
            padding: 1.5rem;
            border-radius: var(--radius-md);
            border-left: 4px solid var(--primary);
        }
        .info-card h4 {
            margin-top: 0;
            color: var(--primary);
            margin-bottom: 1rem;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--gray-200);
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: 600;
            color: var(--gray-700);
            flex: 0 0 45%;
        }
        .info-value {
            flex: 1;
            text-align: right;
            color: var(--gray-900);
        }
        .section-title {
            font-size: 1.5rem;
            color: var(--gray-800);
            margin: 2rem 0 1rem 0;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--primary);
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($error): ?>
            <div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div>
            <div class="text-center">
                <a href="listar_pacientes.php" class="btn btn-primary">← Volver a la lista</a>
            </div>
        <?php elseif ($paciente): ?>
            <!-- Header -->
            <div class="card card-gradient text-center mb-4">
                <h1>👤 Perfil Completo del Paciente</h1>
                <p style="margin-bottom: 1rem;">
                    <strong style="font-size: 1.3rem;">
                        <?= htmlspecialchars($paciente['primer_nombre']) ?> 
                        <?= htmlspecialchars($paciente['segundo_nombre'] ?? '') ?>
                        <?= htmlspecialchars($paciente['primer_apellido']) ?> 
                        <?= htmlspecialchars($paciente['segundo_apellido'] ?? '') ?>
                    </strong>
                </p>
                <div style="display: flex; gap: 0.5rem; justify-content: center; flex-wrap: wrap;">
                    <a href="gestionar_pacientes_completo.php?id=<?= $paciente['id_paciente'] ?>" class="btn btn-success">
                        ✏️ Editar Información
                    </a>
                    <a href="historias_clinicas.php?paciente=<?= $paciente['id_paciente'] ?>" class="btn btn-primary">
                        ➕ Nueva Historia Clínica
                    </a>
                    <a href="listar_pacientes.php" class="btn" style="background: rgba(255,255,255,0.2); color: white;">
                        ← Volver a la Lista
                    </a>
                </div>
            </div>

            <!-- Identificación -->
            <div class="section-title">🆔 Identificación</div>
            <div class="info-grid">
                <div class="info-card">
                    <h4>Documento</h4>
                    <div class="info-row">
                        <span class="info-label">Tipo:</span>
                        <span class="info-value">
                            <?php 
                            $tipoDoc = getNombreReferencia($referencias['tipos_documento'], $paciente['tipo_documento_id'] ?? null, 'codigo');
                            echo htmlspecialchars($tipoDoc);
                            ?>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Número:</span>
                        <span class="info-value">
                            <strong><?= htmlspecialchars($paciente['documento_id']) ?></strong>
                        </span>
                    </div>
                </div>

                <div class="info-card">
                    <h4>ID del Sistema</h4>
                    <div class="info-row">
                        <span class="info-label">ID Paciente:</span>
                        <span class="info-value"><span class="badge badge-primary">#<?= $paciente['id_paciente'] ?></span></span>
                    </div>
                </div>
            </div>

            <!-- Datos Personales -->
            <div class="section-title">👤 Datos Personales</div>
            <div class="info-grid">
                <div class="info-card">
                    <h4>Personales</h4>
                    <div class="info-row">
                        <span class="info-label">Fecha Nacimiento:</span>
                        <span class="info-value">
                            <?php if (!empty($paciente['fecha_nacimiento'])): ?>
                                <?php
                                $fechaNac = new DateTime($paciente['fecha_nacimiento']);
                                $hoy = new DateTime();
                                $edad = $hoy->diff($fechaNac)->y;
                                echo $fechaNac->format('d/m/Y') . " <small>($edad años)</small>";
                                ?>
                            <?php else: ?>
                                <span style="color: var(--gray-500);">No registrada</span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Sexo:</span>
                        <span class="info-value">
                            <?= htmlspecialchars(getNombreReferencia($referencias['sexos'], $paciente['sexo_id'] ?? null, 'sexo')) ?>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Estado Civil:</span>
                        <span class="info-value">
                            <?= htmlspecialchars(getNombreReferencia($referencias['estados_civiles'], $paciente['estado_civil_id'] ?? null, 'estado_civil')) ?>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Ocupación:</span>
                        <span class="info-value">
                            <?= htmlspecialchars($paciente['ocupacion'] ?? 'No especificada') ?>
                        </span>
                    </div>
                </div>

                <div class="info-card">
                    <h4>Contacto</h4>
                    <div class="info-row">
                        <span class="info-label">Teléfono:</span>
                        <span class="info-value">
                            <?php if (!empty($paciente['telefono'])): ?>
                                <a href="tel:<?= htmlspecialchars($paciente['telefono']) ?>" style="color: var(--primary);">
                                    <?= htmlspecialchars($paciente['telefono']) ?>
                                </a>
                            <?php else: ?>
                                <span style="color: var(--gray-500);">No registrado</span>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Ubicación -->
            <div class="section-title">📍 Ubicación</div>
            <div class="info-grid">
                <div class="info-card">
                    <h4>Residencia</h4>
                    <div class="info-row">
                        <span class="info-label">Ciudad:</span>
                        <span class="info-value">
                            <?= htmlspecialchars(getNombreReferencia($referencias['ciudades'], $paciente['ciudad_id'] ?? null, 'nombre')) ?>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Dirección:</span>
                        <span class="info-value">
                            <?= htmlspecialchars($paciente['direccion'] ?? 'No registrada') ?>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Estrato:</span>
                        <span class="info-value">
                            <span class="badge badge-primary">Estrato <?= $paciente['estrato'] ?? 'N/A' ?></span>
                        </span>
                    </div>
                </div>

                <div class="info-card">
                    <h4>Nacimiento</h4>
                    <div class="info-row">
                        <span class="info-label">Lugar:</span>
                        <span class="info-value">
                            <?= htmlspecialchars(getNombreReferencia($referencias['ciudades'], $paciente['lugar_nacimiento'] ?? null, 'nombre')) ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Salud -->
            <div class="section-title">🏥 Aseguramiento y Salud</div>
            <div class="info-grid">
                <div class="info-card">
                    <h4>Aseguramiento</h4>
                    <div class="info-row">
                        <span class="info-label">EPS:</span>
                        <span class="info-value">
                            <?= htmlspecialchars(getNombreReferencia($referencias['eps'], $paciente['eps_id'] ?? null, 'nombre_eps')) ?>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Régimen:</span>
                        <span class="info-value">
                            <?= htmlspecialchars(getNombreReferencia($referencias['regimenes'], $paciente['regimen_id'] ?? null, 'regimen')) ?>
                        </span>
                    </div>
                </div>

                <div class="info-card">
                    <h4>Datos Clínicos</h4>
                    <div class="info-row">
                        <span class="info-label">Grupo Sanguíneo:</span>
                        <span class="info-value">
                            <strong><?= htmlspecialchars(getNombreReferencia($referencias['grupos_sanguineos'], $paciente['gs_rh_id'] ?? null, 'nombre')) ?></strong>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Sociodemográficos y Diversidad -->
            <div class="section-title">📊 Datos Sociodemográficos y Diversidad</div>
            <div class="info-grid">
                <div class="info-card">
                    <h4>Educación y Cultura</h4>
                    <div class="info-row">
                        <span class="info-label">Escolaridad:</span>
                        <span class="info-value">
                            <?= htmlspecialchars(getNombreReferencia($referencias['escolaridades'], $paciente['escolaridad_id'] ?? null, 'escolaridad')) ?>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Etnia:</span>
                        <span class="info-value">
                            <?= htmlspecialchars(getNombreReferencia($referencias['etnias'], $paciente['etnia_id'] ?? null, 'etnia')) ?>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Orientación:</span>
                        <span class="info-value">
                            <?= htmlspecialchars(getNombreReferencia($referencias['orientaciones_sexuales'], $paciente['orien_sexual_id'] ?? null, 'orientacion')) ?>
                        </span>
                    </div>
                </div>

                <div class="info-card">
                    <h4>Social</h4>
                    <div class="info-row">
                        <span class="info-label">Grupo Poblacional:</span>
                        <span class="info-value">
                            <?= htmlspecialchars($paciente['g_poblacion'] ?? 'No especificado') ?>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Programas Sociales:</span>
                        <span class="info-value">
                            <?= htmlspecialchars($paciente['prog_social'] ?? 'Ninguno') ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Vulnerabilidad -->
            <?php if (!empty($paciente['discapacidad']) || !empty($paciente['cond_vulnerabilidad']) || !empty($paciente['hech_victimizantes'])): ?>
            <div class="section-title">⚠️ Condiciones Especiales y Vulnerabilidad</div>
            <div class="info-grid">
                <?php if (!empty($paciente['discapacidad'])): ?>
                <div class="info-card">
                    <h4>Discapacidad</h4>
                    <p><?= htmlspecialchars($paciente['discapacidad']) ?></p>
                </div>
                <?php endif; ?>

                <?php if (!empty($paciente['cond_vulnerabilidad'])): ?>
                <div class="info-card">
                    <h4>Condición de Vulnerabilidad</h4>
                    <p><?= htmlspecialchars($paciente['cond_vulnerabilidad']) ?></p>
                </div>
                <?php endif; ?>

                <?php if (!empty($paciente['hech_victimizantes'])): ?>
                <div class="info-card">
                    <h4>Hechos Victimizantes</h4>
                    <p><?= htmlspecialchars($paciente['hech_victimizantes']) ?></p>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Estadísticas -->
            <div class="section-title">📋 Estadísticas</div>
            <div class="stat-card mb-4">
                <div class="stat-icon">📋</div>
                <div class="stat-value"><?= $totalHistorias ?></div>
                <div class="stat-label">Historias Clínicas Registradas</div>
            </div>

            <!-- Historias Clínicas -->
            <div class="card">
                <h2>📚 Historias Clínicas</h2>
                <?php if (empty($historias)): ?>
                    <div class="alert alert-info">
                        ℹ️ Este paciente no tiene historias clínicas registradas aún.
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Fecha Ingreso</th>
                                    <th>Diagnóstico</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($historias as $h): ?>
                                    <tr>
                                        <td><strong>#<?= htmlspecialchars($h['id_historia']) ?></strong></td>
                                        <td>
                                            <?php
                                            $fecha = new DateTime($h['fecha_ingreso']);
                                            echo $fecha->format('d/m/Y H:i');
                                            ?>
                                        </td>
                                        <td><?= htmlspecialchars($h['diagnostico'] ?: 'Pendiente') ?></td>
                                        <td>
                                            <?php if ($h['fecha_egreso']): ?>
                                                <span class="badge badge-success">✓ Cerrada</span>
                                            <?php else: ?>
                                                <span class="badge badge-primary">● Activa</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="ver_historia.php?id=<?= $h['id_historia'] ?>" class="btn btn-sm btn-primary">
                                                📄 Ver Detalles
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
