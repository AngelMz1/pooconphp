<?php
require_once '../vendor/autoload.php';

use App\SupabaseClient;
use App\HistoriaClinica;
use App\SignosVitales;
use App\ExamenFisico;
use App\RevisionSistemas;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$supabase = new SupabaseClient($_ENV['SUPABASE_URL'], $_ENV['SUPABASE_KEY']);
$historiaModel = new HistoriaClinica($supabase);
$signosModel = new SignosVitales($supabase);
$examenModel = new ExamenFisico($supabase);
$revisionModel = new RevisionSistemas($supabase);

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
    
    // Verificar si la historia está cerrada
    if (!empty($historia['fecha_egreso'])) {
        header("Location: ver_historia.php?id=$id_historia&error=closed");
        exit;
    }
} catch (Exception $e) {
    $error = $e->getMessage();
}

if ($_POST) {
    try {
        $datos = $_POST;
        $datos['id_historia'] = $id_historia;

        // Registrar Signos Vitales
        if (!empty($datos['registrar_signos'])) {
            $signosModel->crear($datos);
            $mensaje .= "✅ Signos vitales registrados. ";
        }

        // Registrar Examen Físico
        if (!empty($datos['registrar_examen'])) {
            $examenModel->crear($datos);
            $mensaje .= "✅ Hallazgos físicos registrados. ";
        }

        // Registrar Revisión por Sistemas
        if (!empty($datos['registrar_revision'])) {
            $revisionModel->crear($datos);
            $mensaje .= "✅ Revisión por sistemas registrada. ";
        }

        if (empty($mensaje)) {
            $mensaje = "⚠️ No se seleccionó ninguna sección para guardar.";
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
    <title>Examen Físico Completo - Sistema Médico</title>
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
        .section-check {
            margin-bottom: 1rem;
            padding: 1rem;
            background: var(--gray-50);
            border-radius: var(--radius-sm);
            border-left: 4px solid var(--primary);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card card-gradient text-center mb-4">
            <h1>🩺 Examen Físico Completo</h1>
            <p style="margin-bottom: 0;">Historia Clínica #<?= htmlspecialchars($id_historia) ?></p>
        </div>

        <?php if ($mensaje): ?>
            <div class="alert alert-success"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="card">
            <form method="POST">
                
                <div class="tabs">
                    <button type="button" class="tab-btn active" onclick="showTab(0)">💓 Signos Vitales</button>
                    <button type="button" class="tab-btn" onclick="showTab(1)">👨‍⚕️ Hallazgos Físicos</button>
                    <button type="button" class="tab-btn" onclick="showTab(2)">🔄 Revisión por Sistemas</button>
                </div>

                <!-- Tab 1: Signos Vitales -->
                <div class="tab-content active" id="tab-0">
                    <div class="section-check">
                        <label>
                            <input type="checkbox" name="registrar_signos" value="1" checked> 
                            <strong>Registrar esta sección</strong>
                        </label>
                    </div>

                    <div class="grid grid-3">
                        <div class="form-group">
                            <label for="ta">Tensión Arterial (TA) *</label>
                            <input type="text" name="ta" id="ta" placeholder="Ej: 120/80">
                        </div>
                        <div class="form-group">
                            <label for="pulso">Pulso (lpm) *</label>
                            <input type="number" name="pulso" id="pulso" placeholder="Ej: 80">
                        </div>
                        <div class="form-group">
                            <label for="f_res">Frec. Respiratoria (rpm) *</label>
                            <input type="number" name="f_res" id="f_res" placeholder="Ej: 18">
                        </div>
                    </div>

                    <div class="grid grid-3">
                        <div class="form-group">
                            <label for="temperatura">Temperatura (°C) *</label>
                            <input type="number" step="0.1" name="temperatura" id="temperatura" placeholder="Ej: 36.5">
                        </div>
                        <div class="form-group">
                            <label for="peso">Peso (Kg) *</label>
                            <input type="number" step="0.1" name="peso" id="peso" placeholder="Ej: 70.5">
                        </div>
                        <div class="form-group">
                            <label for="talla">Talla (cm) *</label>
                            <input type="number" step="1" name="talla" id="talla" placeholder="Ej: 175">
                        </div>
                    </div>
                </div>

                <!-- Tab 2: Hallazgos Físicos -->
                <div class="tab-content" id="tab-1">
                    <div class="section-check">
                        <label>
                            <input type="checkbox" name="registrar_examen" value="1"> 
                            <strong>Registrar esta sección</strong>
                        </label>
                    </div>
                    
                    <h3>Descripción de Hallazgos (Normal/Anormal)</h3>
                    <div class="grid grid-2">
                        <div class="form-group"><label>Cabeza</label><textarea name="cabeza" rows="2"></textarea></div>
                        <div class="form-group"><label>Ojos</label><textarea name="ojos" rows="2"></textarea></div>
                        <div class="form-group"><label>Oídos</label><textarea name="oidos" rows="2"></textarea></div>
                        <div class="form-group"><label>Nariz</label><textarea name="nariz" rows="2"></textarea></div>
                        <div class="form-group"><label>Boca</label><textarea name="boca" rows="2"></textarea></div>
                        <div class="form-group"><label>Garganta</label><textarea name="garganta" rows="2"></textarea></div>
                        <div class="form-group"><label>Cuello</label><textarea name="cuello" rows="2"></textarea></div>
                        <div class="form-group"><label>Tórax</label><textarea name="torax" rows="2"></textarea></div>
                        <div class="form-group"><label>Corazón</label><textarea name="corazon" rows="2"></textarea></div>
                        <div class="form-group"><label>Pulmones</label><textarea name="pulmon" rows="2"></textarea></div>
                        <div class="form-group"><label>Abdomen</label><textarea name="abdomen" rows="2"></textarea></div>
                        <div class="form-group"><label>Extremidades</label><textarea name="extremidades_sup" rows="2" placeholder="Superiores"></textarea></div>
                    </div>
                </div>

                <!-- Tab 3: Revisión por Sistemas -->
                <div class="tab-content" id="tab-2">
                    <div class="section-check">
                        <label>
                            <input type="checkbox" name="registrar_revision" value="1"> 
                            <strong>Registrar esta sección</strong>
                        </label>
                    </div>

                    <div class="grid grid-2">
                        <div class="form-group"><label>Respiratorio</label><textarea name="respiratorio" rows="2"></textarea></div>
                        <div class="form-group"><label>Cardiovascular</label><textarea name="cardiovascular" rows="2"></textarea></div>
                        <div class="form-group"><label>Gastrointestinal</label><textarea name="gastrointestinal" rows="2"></textarea></div>
                        <div class="form-group"><label>Genitourinario</label><textarea name="genitourinario" rows="2"></textarea></div>
                        <div class="form-group"><label>Neurológico</label><textarea name="neurologico" rows="2"></textarea></div>
                        <div class="form-group"><label>Osteomuscular</label><textarea name="osteomuscular" rows="2"></textarea></div>
                    </div>
                </div>

                <div style="text-align: center; margin-top: 2rem; padding-top: 1rem; border-top: 1px solid var(--gray-200);">
                    <button type="submit" class="btn btn-primary btn-lg">💾 Guardar Examen Completo</button>
                    <a href="ver_historia.php?id=<?= $id_historia ?>" class="btn btn-secondary btn-lg">Cancelar</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showTab(n) {
            const tabs = document.querySelectorAll('.tab-content');
            const btns = document.querySelectorAll('.tab-btn');
            
            tabs.forEach(t => t.classList.remove('active'));
            btns.forEach(b => b.classList.remove('active'));
            
            tabs[n].classList.add('active');
            btns[n].classList.add('active');
        }
    </script>
</body>
</html>
