<?php
require_once '../vendor/autoload.php';

use App\SupabaseClient;
use App\Consulta;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$supabase = new SupabaseClient($_ENV['SUPABASE_URL'], $_ENV['SUPABASE_KEY']);
$consultaModel = new Consulta($supabase);

$error = '';
$consultas = [];

try {
    $consultas = $consultaModel->obtenerRecientes(50);
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultas Médicas - Sistema Médico</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <div class="container">
        <div class="card card-gradient text-center mb-4">
            <h1>🩺 Consultas Médicas</h1>
            <p style="margin-bottom: 0;">Registro de consultas realizadas en el sistema</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Barra de acciones -->
        <div class="card mb-4">
            <div class="flex justify-between items-center flex-wrap gap-2">
                <h2 style="margin: 0;">📋 Consultas (<span id="total-count"><?= count($consultas) ?></span>)</h2>
                <div class="flex gap-2 flex-wrap">
                    <a href="nueva_consulta.php" class="btn btn-success">🩺 Nueva Consulta</a>
                    <a href="../index.php" class="btn btn-outline">🏠 Inicio</a>
                </div>
            </div>
        </div>

        <!-- Tabla de consultas -->
        <div class="card">
            <?php if (empty($consultas)): ?>
                <div class="no-results">
                    <div class="no-results-icon">🩺</div>
                    <p>No hay consultas médicas registradas en el sistema.</p>
                    <a href="nueva_consulta.php" class="btn btn-primary">Registrar Primera Consulta</a>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Paciente</th>
                                <th>Médico</th>
                                <th>Motivo</th>
                                <th>Enfermedad Actual</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($consultas as $c): ?>
                                <tr>
                                    <td><strong>#<?= htmlspecialchars($c['id_consulta']) ?></strong></td>
                                    <td>
                                        <a href="ver_paciente.php?id=<?= $c['id_paciente'] ?>" class="badge badge-primary">
                                            <?= htmlspecialchars($c['pacientes']['primer_nombre'] . ' ' . $c['pacientes']['primer_apellido']) ?>
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge badge-success">
                                            Dr. <?= htmlspecialchars($c['medicos']['primer_nombre'] . ' ' . $c['medicos']['primer_apellido']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span title="<?= htmlspecialchars($c['motivo_consulta']) ?>">
                                            <?= htmlspecialchars(substr($c['motivo_consulta'], 0, 50)) ?>
                                            <?= strlen($c['motivo_consulta']) > 50 ? '...' : '' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span title="<?= htmlspecialchars($c['enfermedad_actual']) ?>">
                                            <?= htmlspecialchars(substr($c['enfermedad_actual'], 0, 50)) ?>
                                            <?= strlen($c['enfermedad_actual']) > 50 ? '...' : '' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="ver_consulta.php?id=<?= $c['id_consulta'] ?>" class="btn btn-sm btn-primary">
                                            📄 Ver
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
