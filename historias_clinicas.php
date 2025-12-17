<?php
require_once 'vendor/autoload.php';

use App\SupabaseClient;
use App\HistoriaClinica;
use App\Paciente;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$supabase = new SupabaseClient($_ENV['SUPABASE_URL'], $_ENV['SUPABASE_KEY']);
$historiaClinica = new HistoriaClinica($supabase);
$paciente = new Paciente($supabase);

$mensaje = '';
$error = '';

// Procesar formulario
if ($_POST) {
    try {
        $id_paciente = $_POST['id_paciente'];
        
        // Si se seleccionó "nuevo" paciente, crear uno nuevo
        if ($id_paciente === 'nuevo') {
            $nuevoPaciente = [
                'documento_id' => $_POST['documento_id'],
                'primer_nombre' => $_POST['primer_nombre'],
                'primer_apellido' => $_POST['primer_apellido'],
                // AÑADIDO: Campo 'estrato' para cumplir con la restricción NOT NULL de la base de datos
                'estrato' => $_POST['estrato'] 
            ];
            
            // Agregar campos opcionales si están presentes
            if (!empty($_POST['segundo_nombre'])) {
                $nuevoPaciente['segundo_nombre'] = $_POST['segundo_nombre'];
            }
            if (!empty($_POST['segundo_apellido'])) {
                $nuevoPaciente['segundo_apellido'] = $_POST['segundo_apellido'];
            }
            
            $resultadoPaciente = $supabase->insert('pacientes', $nuevoPaciente);
            $id_paciente = $resultadoPaciente[0]['id_paciente'];
        }
        
        $datos = [
            'id_paciente' => $id_paciente,
            'motivo_consulta' => $_POST['motivo_consulta'],
            'analisis_plan' => $_POST['analisis_plan'],
            'diagnostico' => $_POST['diagnostico'],
            'tratamiento' => $_POST['tratamiento'],
            'observaciones' => $_POST['observaciones']
        ];

        if (!empty($_POST['fecha_egreso'])) {
            $datos['fecha_egreso'] = $_POST['fecha_egreso'];
        }

        $resultado = $historiaClinica->crear($datos);
        $mensaje = "Historia clínica creada exitosamente. ID: " . $resultado[0]['id_historia'];
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Obtener pacientes para el select
try {
    $pacientes = $paciente->obtenerTodos();
} catch (Exception $e) {
    $error = "Error al cargar pacientes: " . $e->getMessage();
    $pacientes = [];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historias Clínicas - POO con PHP</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #333; }
        input, select, textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; }
        textarea { height: 80px; resize: vertical; }
        .btn { padding: 12px 20px; background: #007cba; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        .btn:hover { background: #005a87; }
        .btn-secondary { background: #6c757d; margin-left: 10px; text-decoration: none; display: inline-block; }
        .success { color: #28a745; background: #d4edda; padding: 10px; border-radius: 4px; margin-bottom: 20px; }
        .error { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 4px; margin-bottom: 20px; }
        .header { text-align: center; margin-bottom: 30px; }
        .form-row { display: flex; gap: 15px; }
        .form-row .form-group { flex: 1; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋 Nueva Historia Clínica</h1>
            <p>Crear una nueva historia clínica para un paciente</p>
        </div>

        <?php if ($mensaje): ?>
            <div class="success">✅ <?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error">❌ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="id_paciente">Paciente *</label>
                <select name="id_paciente" id="id_paciente" required onchange="togglePacienteFields()">
                    <option value="">Seleccionar paciente...</option>
                    <option value="nuevo">➕ Crear nuevo paciente</option>
                    <?php foreach ($pacientes as $p): ?>
                        <option value="<?= $p['id_paciente'] ?>">
                            <?= htmlspecialchars($p['documento_id'] . ' - ' . $p['primer_nombre'] . ' ' . $p['primer_apellido']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div id="nuevoPacienteFields" style="display: none; border: 2px solid #007cba; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                <h3 style="color: #007cba; margin-top: 0;">📝 Datos del Nuevo Paciente</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="documento_id">Documento ID *</label>
                        <input type="text" name="documento_id" id="documento_id" placeholder="Ej: 1234567890">
                    </div>
                    <div class="form-group">
                        <label for="estrato">Estrato *</label>
                        <input type="number" name="estrato" id="estrato" placeholder="Ej: 3" min="1" max="6">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="primer_nombre">Primer Nombre *</label>
                        <input type="text" name="primer_nombre" id="primer_nombre" placeholder="Ej: Juan">
                    </div>
                    <div class="form-group">
                        <label for="segundo_nombre">Segundo Nombre</label>
                        <input type="text" name="segundo_nombre" id="segundo_nombre" placeholder="Ej: Carlos">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="primer_apellido">Primer Apellido *</label>
                        <input type="text" name="primer_apellido" id="primer_apellido" placeholder="Ej: Pérez">
                    </div>
                    <div class="form-group">
                        <label for="segundo_apellido">Segundo Apellido</label>
                        <input type="text" name="segundo_apellido" id="segundo_apellido" placeholder="Ej: González">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="motivo_consulta">Motivo de Consulta *</label>
                <textarea name="motivo_consulta" id="motivo_consulta" required placeholder="Describe el motivo de la consulta..."></textarea>
            </div>

            <div class="form-group">
                <label for="analisis_plan">Análisis y Plan</label>
                <textarea name="analisis_plan" id="analisis_plan" placeholder="Análisis de la situación y plan de tratamiento..."></textarea>
            </div>

            <div class="form-group">
                <label for="diagnostico">Diagnóstico</label>
                <textarea name="diagnostico" id="diagnostico" placeholder="Diagnóstico médico..."></textarea>
            </div>

            <div class="form-group">
                <label for="tratamiento">Tratamiento</label>
                <textarea name="tratamiento" id="tratamiento" placeholder="Tratamiento prescrito..."></textarea>
            </div>

            <div class="form-group">
                <label for="observaciones">Observaciones</label>
                <textarea name="observaciones" id="observaciones" placeholder="Observaciones adicionales..."></textarea>
            </div>

            <div class="form-group">
                <label for="fecha_egreso">Fecha de Egreso (opcional)</label>
                <input type="datetime-local" name="fecha_egreso" id="fecha_egreso">
            </div>

            <div style="text-align: center; margin-top: 30px;">
                <button type="submit" class="btn">💾 Guardar Historia Clínica</button>
                <a href="index.php" class="btn btn-secondary">🏠 Volver al Inicio</a>
            </div>
        </form>
    </div>

    <script>
        function togglePacienteFields() {
            const select = document.getElementById('id_paciente');
            const fields = document.getElementById('nuevoPacienteFields');
            // AÑADIDO: 'estrato' para que sea requerido al crear un nuevo paciente
            const requiredFields = ['documento_id', 'primer_nombre', 'primer_apellido', 'estrato']; 
            
            if (select.value === 'nuevo') {
                fields.style.display = 'block';
                requiredFields.forEach(field => {
                    document.getElementById(field).required = true;
                });
            } else {
                fields.style.display = 'none';
                requiredFields.forEach(field => {
                    document.getElementById(field).required = false;
                });
            }
        }
    </script>
</body>
</html>