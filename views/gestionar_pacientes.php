<?php
require_once '../vendor/autoload.php';

use App\SupabaseClient;
use App\Paciente;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$supabase = new SupabaseClient($_ENV['SUPABASE_URL'], $_ENV['SUPABASE_KEY']);
$pacienteModel = new Paciente($supabase);

$mensaje = '';
$error = '';
$paciente = null;
$isEdit = false;

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
        $datos = [
            'documento_id' => $_POST['documento_id'],
            'primer_nombre' => $_POST['primer_nombre'],
            'segundo_nombre' => $_POST['segundo_nombre'] ?? null,
            'primer_apellido' => $_POST['primer_apellido'],
            'segundo_apellido' => $_POST['segundo_apellido'] ?? null,
            'fecha_nacimiento' => $_POST['fecha_nacimiento'] ?? null,
            'telefono' => $_POST['telefono'] ?? null,
            'email' => $_POST['email'] ?? null,
            'direccion' => $_POST['direccion'] ?? null,
            'estrato' => $_POST['estrato'] ?? null,
            'acudiente_nombre' => $_POST['acudiente_nombre'] ?? null,
            'acudiente_telefono' => $_POST['acudiente_telefono'] ?? null,
            'acudiente_parentesco' => $_POST['acudiente_parentesco'] ?? null,
            'acudiente_documento' => $_POST['acudiente_documento'] ?? null
        ];

        // Limpiar valores vacíos
        $datos = array_filter($datos, function($value) {
            return $value !== null && $value !== '';
        });

        if ($isEdit && isset($_POST['id_paciente'])) {
            // Actualizar paciente existente
            $resultado = $pacienteModel->actualizar($_POST['id_paciente'], $datos);
            $mensaje = "✅ Paciente actualizado exitosamente";
            // Recargar datos del paciente
            $paciente = $pacienteModel->obtenerPorId($_POST['id_paciente']);
        } else {
            // Crear nuevo paciente
            $resultado = $pacienteModel->crear($datos);
            $mensaje = "✅ Paciente creado exitosamente con ID: " . $resultado[0]['id_paciente'];
            // Redirigir a modo edición
            header("Location: gestionar_pacientes.php?id=" . $resultado[0]['id_paciente'] . "&success=1");
            exit;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Mostrar mensaje de éxito si viene de redirección
if (isset($_GET['success']) && !$error) {
    $mensaje = "✅ Paciente creado exitosamente";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isEdit ? 'Editar Paciente' : 'Nuevo Paciente' ?> - Sistema de Gestión Médica</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <div class="container-sm">
        <div class="card card-gradient text-center mb-4">
            <h1><?= $isEdit ? '✏️ Editar Paciente' : '➕ Nuevo Paciente' ?></h1>
            <p style="margin-bottom: 0;">
                <?= $isEdit ? 'Actualizar información del paciente' : 'Registrar nuevo paciente en el sistema' ?>
            </p>
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

                <!-- Información Básica -->
                <div class="form-section">
                    <h3>📋 Información Básica</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="documento_id">
                                Documento de Identidad <span class="required-indicator">*</span>
                            </label>
                            <input 
                                type="text" 
                                name="documento_id" 
                                id="documento_id" 
                                value="<?= $isEdit ? htmlspecialchars($paciente['documento_id']) : '' ?>"
                                required
                                placeholder="Ej: 1234567890"
                                <?= $isEdit ? 'readonly style="background: var(--gray-200);"' : '' ?>
                            >
                            <?php if (!$isEdit): ?>
                                <small class="form-help">El documento no podrá modificarse después de crear el paciente</small>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="estrato">
                                Estrato Socioeconómico <span class="required-indicator">*</span>
                            </label>
                            <select name="estrato" id="estrato" required>
                                <option value="">Seleccionar...</option>
                                <?php for ($i = 1; $i <= 6; $i++): ?>
                                    <option value="<?= $i ?>" <?= ($isEdit && $paciente['estrato'] == $i) ? 'selected' : '' ?>>
                                        Estrato <?= $i ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                            <small class="form-help">Estrato socioeconómico del paciente (1-6)</small>
                        </div>
                    </div>
                </div>

                <!-- Nombres -->
                <div class="form-section">
                    <h3>👤 Nombres</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="primer_nombre">
                                Primer Nombre <span class="required-indicator">*</span>
                            </label>
                            <input 
                                type="text" 
                                name="primer_nombre" 
                                id="primer_nombre"
                                value="<?= $isEdit ? htmlspecialchars($paciente['primer_nombre']) : '' ?>"
                                required
                                placeholder="Ej: Juan"
                            >
                        </div>

                        <div class="form-group">
                            <label for="segundo_nombre">Segundo Nombre</label>
                            <input 
                                type="text" 
                                name="segundo_nombre" 
                                id="segundo_nombre"
                                value="<?= $isEdit ? htmlspecialchars($paciente['segundo_nombre'] ?? '') : '' ?>"
                                placeholder="Ej: Carlos"
                            >
                            <small class="form-help">Opcional</small>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="primer_apellido">
                                Primer Apellido <span class="required-indicator">*</span>
                            </label>
                            <input 
                                type="text" 
                                name="primer_apellido" 
                                id="primer_apellido"
                                value="<?= $isEdit ? htmlspecialchars($paciente['primer_apellido']) : '' ?>"
                                required
                                placeholder="Ej: Pérez"
                            >
                        </div>

                        <div class="form-group">
                            <label for="segundo_apellido">Segundo Apellido</label>
                            <input 
                                type="text" 
                                name="segundo_apellido" 
                                id="segundo_apellido"
                                value="<?= $isEdit ? htmlspecialchars($paciente['segundo_apellido'] ?? '') : '' ?>"
                                placeholder="Ej: González"
                            >
                            <small class="form-help">Opcional</small>
                        </div>
                    </div>
                </div>

                <!-- Información de Contacto -->
                <div class="form-section">
                    <h3>📞 Información de Contacto</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="telefono">Teléfono</label>
                            <input 
                                type="tel" 
                                name="telefono" 
                                id="telefono"
                                value="<?= $isEdit ? htmlspecialchars($paciente['telefono'] ?? '') : '' ?>"
                                placeholder="Ej: 3001234567"
                            >
                            <small class="form-help">Opcional - Solo números</small>
                        </div>

                        <div class="form-group">
                            <label for="email">Correo Electrónico</label>
                            <input 
                                type="email" 
                                name="email" 
                                id="email"
                                value="<?= $isEdit ? htmlspecialchars($paciente['email'] ?? '') : '' ?>"
                                placeholder="Ej: paciente@ejemplo.com"
                            >
                            <small class="form-help">Opcional</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="direccion">Dirección</label>
                        <textarea 
                            name="direccion" 
                            id="direccion"
                            rows="2"
                            placeholder="Ej: Calle 123 #45-67, Barrio Centro"
                        ><?= $isEdit ? htmlspecialchars($paciente['direccion'] ?? '') : '' ?></textarea>
                        <small class="form-help">Opcional</small>
                    </div>
                </div>

                <!-- Información Adicional -->
                <div class="form-section">
                    <h3>📅 Información Adicional</h3>
                    
                    <div class="form-group">
                        <label for="fecha_nacimiento">Fecha de Nacimiento</label>
                        <input 
                            type="date" 
                            name="fecha_nacimiento" 
                            id="fecha_nacimiento"
                            value="<?= $isEdit ? htmlspecialchars($paciente['fecha_nacimiento'] ?? '') : '' ?>"
                        >
                        <small class="form-help">Opcional</small>
                    </div>
                </div>

                <!-- Información del Acudiente -->
                <div class="form-section">
                    <h3>👨‍👩‍👧‍👦 Información del Acudiente</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="acudiente_nombre">Nombre del Acudiente</label>
                            <input 
                                type="text" 
                                name="acudiente_nombre" 
                                id="acudiente_nombre"
                                value="<?= $isEdit ? htmlspecialchars($paciente['acudiente_nombre'] ?? '') : '' ?>"
                                placeholder="Ej: María González"
                            >
                            <small class="form-help">Opcional - Nombre completo del acudiente</small>
                        </div>

                        <div class="form-group">
                            <label for="acudiente_telefono">Teléfono del Acudiente</label>
                            <input 
                                type="tel" 
                                name="acudiente_telefono" 
                                id="acudiente_telefono"
                                value="<?= $isEdit ? htmlspecialchars($paciente['acudiente_telefono'] ?? '') : '' ?>"
                                placeholder="Ej: 3009876543"
                            >
                            <small class="form-help">Opcional - Solo números</small>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="acudiente_parentesco">Parentesco</label>
                            <select name="acudiente_parentesco" id="acudiente_parentesco">
                                <option value="">Seleccionar...</option>
                                <option value="Padre" <?= ($isEdit && ($paciente['acudiente_parentesco'] ?? '') == 'Padre') ? 'selected' : '' ?>>Padre</option>
                                <option value="Madre" <?= ($isEdit && ($paciente['acudiente_parentesco'] ?? '') == 'Madre') ? 'selected' : '' ?>>Madre</option>
                                <option value="Hermano/a" <?= ($isEdit && ($paciente['acudiente_parentesco'] ?? '') == 'Hermano/a') ? 'selected' : '' ?>>Hermano/a</option>
                                <option value="Abuelo/a" <?= ($isEdit && ($paciente['acudiente_parentesco'] ?? '') == 'Abuelo/a') ? 'selected' : '' ?>>Abuelo/a</option>
                                <option value="Tío/a" <?= ($isEdit && ($paciente['acudiente_parentesco'] ?? '') == 'Tío/a') ? 'selected' : '' ?>>Tío/a</option>
                                <option value="Cónyuge" <?= ($isEdit && ($paciente['acudiente_parentesco'] ?? '') == 'Cónyuge') ? 'selected' : '' ?>>Cónyuge</option>
                                <option value="Tutor Legal" <?= ($isEdit && ($paciente['acudiente_parentesco'] ?? '') == 'Tutor Legal') ? 'selected' : '' ?>>Tutor Legal</option>
                                <option value="Otro" <?= ($isEdit && ($paciente['acudiente_parentesco'] ?? '') == 'Otro') ? 'selected' : '' ?>>Otro</option>
                            </select>
                            <small class="form-help">Opcional - Relación con el paciente</small>
                        </div>

                        <div class="form-group">
                            <label for="acudiente_documento">Documento del Acudiente</label>
                            <input 
                                type="text" 
                                name="acudiente_documento" 
                                id="acudiente_documento"
                                value="<?= $isEdit ? htmlspecialchars($paciente['acudiente_documento'] ?? '') : '' ?>"
                                placeholder="Ej: 12345678"
                            >
                            <small class="form-help">Opcional - Documento de identidad</small>
                        </div>
                    </div>
                </div>

                <!-- Botones de Acción -->
                <div style="text-align: center; margin-top: 30px; display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <?= $isEdit ? '💾 Actualizar Paciente' : '➕ Crear Paciente' ?>
                    </button>
                    <a href="listar_pacientes.php" class="btn btn-secondary btn-lg">
                        ← Volver a la Lista
                    </a>
                    <?php if ($isEdit): ?>
                        <a href="ver_paciente.php?id=<?= $paciente['id_paciente'] ?>" class="btn btn-outline btn-lg">
                            👁️ Ver Detalles
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/js/app.js"></script>
    <script>
        // Validación del formulario
        document.getElementById('patientForm').addEventListener('submit', function(e) {
            const estrato = document.getElementById('estrato').value;
            const documento = document.getElementById('documento_id').value;
            
            // Validar estrato
            if (!FormValidator.validateEstrato(estrato)) {
                e.preventDefault();
                alert('El estrato debe estar entre 1 y 6');
                return false;
            }

            // Validar documento (solo si es creación)
            <?php if (!$isEdit): ?>
            if (!FormValidator.validateDocumento(documento)) {
                e.preventDefault();
                alert('El documento debe tener entre 5 y 20 caracteres');
                return false;
            }
            <?php endif; ?>

            // Validar email si se proporciona
            const email = document.getElementById('email').value;
            if (email && !FormValidator.validateEmail(email)) {
                e.preventDefault();
                alert('El correo electrónico no es válido');
                return false;
            }

            // Validar teléfono si se proporciona
            const telefono = document.getElementById('telefono').value;
            if (telefono && !FormValidator.validatePhone(telefono)) {
                e.preventDefault();
                alert('El teléfono debe contener solo números (7-15 dígitos)');
                return false;
            }
        });
    </script>
</body>
</html>
