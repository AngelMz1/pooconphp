<?php
require_once __DIR__ . '/../includes/auth_helper.php';

// Verificar permiso para ver pacientes
requirePermission('ver_pacientes');
require_once '../vendor/autoload.php';

use App\SupabaseClient;
use App\Paciente;
use App\HistoriaClinica;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$supabase = new SupabaseClient($_ENV['SUPABASE_URL'], $_ENV['SUPABASE_KEY']);
$pacienteModel = new Paciente($supabase);
$historiaModel = new HistoriaClinica($supabase);

$error = '';
$paciente = null;
$historias = [];
$totalHistorias = 0;

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
    
    // Debug: mostrar información
    // echo "<pre>Debug - ID Paciente: $id<br>";
    // echo "Historias encontradas: " . count($historias) . "<br>";
    // var_dump($historias);
    // echo "</pre>";
    
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles del Paciente - Sistema de Gestión Médica</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <div class="dashboard-container">
        <?php include '../includes/sidebar.php'; ?>
        <?php include '../includes/header.php'; ?>
        
        <main class="main-content">
    <div class="container">
        <?php if ($error): ?>
            <div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div>
            <div class="text-center">
                <a href="listar_pacientes.php" class="btn btn-primary">← Volver a la lista</a>
            </div>
        <?php elseif ($paciente): ?>
            <!-- Header -->
            <div class="card card-gradient text-center mb-4">
                <h1>👤 Detalles del Paciente</h1>
                <p style="margin-bottom: 1rem;">
                    <?= htmlspecialchars($paciente['primer_nombre']) ?> 
                    <?= htmlspecialchars($paciente['primer_apellido']) ?>
                </p>
                <div style="display: flex; gap: 0.5rem; justify-content: center; flex-wrap: wrap;">
                    <a href="gestionar_pacientes.php?id=<?= $paciente['id_paciente'] ?>" class="btn btn-success">
                        ✏️ Editar Paciente
                    </a>
                    <a href="listar_pacientes.php" class="btn" style="background: rgba(255,255,255,0.2); color: white;">
                        ← Volver a la Lista
                    </a>
                </div>
            </div>

            <!-- Información del Paciente -->
            <div class="card mb-4">
                <h2>📋 Información Personal Completa</h2>
                <div class="grid grid-2">
                    <div>
                        <table style="width: 100%; border: none;">
                            <tr>
                                <td style="border: none; padding: 0.75rem 0; font-weight: bold; color: var(--gray-700);">
                                    Documento ID:
                                </td>
                                <td style="border: none; padding: 0.75rem 0;">
                                    <span class="badge badge-primary" style="font-size: 1rem;">
                                        <?= htmlspecialchars($paciente['documento_id']) ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td style="border: none; padding: 0.75rem 0; font-weight: bold; color: var(--gray-700);">
                                    Primer Nombre:
                                </td>
                                <td style="border: none; padding: 0.75rem 0;">
                                    <?= htmlspecialchars($paciente['primer_nombre']) ?>
                                </td>
                            </tr>
                            <tr>
                                <td style="border: none; padding: 0.75rem 0; font-weight: bold; color: var(--gray-700);">
                                    Segundo Nombre:
                                </td>
                                <td style="border: none; padding: 0.75rem 0;">
                                    <?= htmlspecialchars($paciente['segundo_nombre'] ?? '-') ?>
                                </td>
                            </tr>
                            <tr>
                                <td style="border: none; padding: 0.75rem 0; font-weight: bold; color: var(--gray-700);">
                                    Primer Apellido:
                                </td>
                                <td style="border: none; padding: 0.75rem 0;">
                                    <?= htmlspecialchars($paciente['primer_apellido']) ?>
                                </td>
                            </tr>
                            <tr>
                                <td style="border: none; padding: 0.75rem 0; font-weight: bold; color: var(--gray-700);">
                                    Segundo Apellido:
                                </td>
                                <td style="border: none; padding: 0.75rem 0;">
                                    <?= htmlspecialchars($paciente['segundo_apellido'] ?? '-') ?>
                                </td>
                            </tr>
                            <tr>
                                <td style="border: none; padding: 0.75rem 0; font-weight: bold; color: var(--gray-700);">
                                    Estrato:
                                </td>
                                <td style="border: none; padding: 0.75rem 0;">
                                    <span class="badge badge-primary">
                                        Estrato <?= htmlspecialchars($paciente['estrato'] ?? 'N/A') ?>
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <div>
                        <table style="width: 100%; border: none;">
                            <tr>
                                <td style="border: none; padding: 0.75rem 0; font-weight: bold; color: var(--gray-700);">
                                    📅 Fecha de Nacimiento:
                                </td>
                                <td style="border: none; padding: 0.75rem 0;">
                                    <?php if (!empty($paciente['fecha_nacimiento'])): ?>
                                        <?php
                                        $fechaNac = new DateTime($paciente['fecha_nacimiento']);
                                        $hoy = new DateTime();
                                        $edad = $hoy->diff($fechaNac)->y;
                                        echo $fechaNac->format('d/m/Y') . " ($edad años)";
                                        ?>
                                    <?php else: ?>
                                        <span style="color: var(--gray-500);">No registrada</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td style="border: none; padding: 0.75rem 0; font-weight: bold; color: var(--gray-700);">
                                    📞 Teléfono:
                                </td>
                                <td style="border: none; padding: 0.75rem 0;">
                                    <?php if (!empty($paciente['telefono'])): ?>
                                        <a href="tel:<?= htmlspecialchars($paciente['telefono']) ?>" style="color: var(--primary); text-decoration: none;">
                                            <?= htmlspecialchars($paciente['telefono']) ?>
                                        </a>
                                    <?php else: ?>
                                        <span style="color: var(--gray-500);">No registrado</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td style="border: none; padding: 0.75rem 0; font-weight: bold; color: var(--gray-700);">
                                    📧 Email:
                                </td>
                                <td style="border: none; padding: 0.75rem 0;">
                                    <?php if (!empty($paciente['email'])): ?>
                                        <a href="mailto:<?= htmlspecialchars($paciente['email']) ?>" style="color: var(--primary); text-decoration: none;">
                                            <?= htmlspecialchars($paciente['email']) ?>
                                        </a>
                                    <?php else: ?>
                                        <span style="color: var(--gray-500);">No registrado</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td style="border: none; padding: 0.75rem 0; font-weight: bold; color: var(--gray-700);">
                                    📍 Dirección:
                                </td>
                                <td style="border: none; padding: 0.75rem 0;">
                                    <?php if (!empty($paciente['direccion'])): ?>
                                        <?= htmlspecialchars($paciente['direccion']) ?>
                                    <?php else: ?>
                                        <span style="color: var(--gray-500);">No registrada</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Información de Sistema -->
                <div style="margin-top: var(--spacing-lg); padding-top: var(--spacing-lg); border-top: 1px solid var(--gray-200);">
                    <h4 style="color: var(--gray-600); font-size: 0.9rem; margin-bottom: var(--spacing-sm);">
                        📊 Información del Sistema
                    </h4>
                    <div class="grid grid-2">
                        <p style="color: var(--gray-600); margin: 0.25rem 0;">
                            <strong>ID de Paciente:</strong> #<?= htmlspecialchars($paciente['id_paciente']) ?>
                        </p>
                        <p style="color: var(--gray-600); margin: 0.25rem 0;">
                            <strong>Registrado:</strong> 
                            <?php
                            if (!empty($paciente['created_at'])) {
                                $created = new DateTime($paciente['created_at']);
                                echo $created->format('d/m/Y H:i');
                            } else {
                                echo 'N/A';
                            }
                            ?>
                        </p>
                        <?php if (!empty($paciente['updated_at']) && $paciente['updated_at'] !== $paciente['created_at']): ?>
                        <p style="color: var(--gray-600); margin: 0.25rem 0;">
                            <strong>Última actualización:</strong> 
                            <?php
                            $updated = new DateTime($paciente['updated_at']);
                            echo $updated->format('d/m/Y H:i');
                            ?>
                        </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Estadísticas -->
            <div class="stat-card mb-4">
                <div class="stat-icon">📋</div>
                <div class="stat-value"><?= $totalHistorias ?></div>
                <div class="stat-label">Historias Clínicas Registradas</div>
            </div>

            <!-- Acciones -->
            <div class="card mb-4">
                <h3>⚡ Acciones Rápidas</h3>
                <div class="flex gap-2 flex-wrap">
                    <a href="historias_clinicas.php" class="btn btn-success">
                        ➕ Nueva Historia Clínica
                    </a>
                    <a href="listar_pacientes.php" class="btn btn-secondary">
                        ← Volver a la lista
                    </a>
                    <a href="../index.php" class="btn btn-outline">
                        🏠 Inicio
                    </a>
                </div>
            </div>

            <!-- Historias Clínicas -->
            <div class="card">
                <h2>📚 Historias Clínicas (<?= $totalHistorias ?>)</h2>
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
                                    <th>Motivo</th>
                                    <th>Diagnóstico</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($historias as $h): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($h['id_historia']) ?></strong></td>
                                        <td>
                                            <?php
                                            $fecha = new DateTime($h['fecha_ingreso']);
                                            echo $fecha->format('d/m/Y H:i');
                                            ?>
                                        </td>
                                        <td><?= htmlspecialchars(substr($h['motivo_consulta'] ?? 'Sin motivo registrado', 0, 50)) ?>...</td>
                                        <td><?= htmlspecialchars($h['diagnostico'] ?: 'Pendiente') ?></td>
                                        <td>
                                            <?php if ($h['fecha_egreso']): ?>
                                                <span class="badge badge-success">Cerrada</span>
                                            <?php else: ?>
                                                <span class="badge badge-primary">Activa</span>
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
        </main>
    </div>
</body>
</html>
