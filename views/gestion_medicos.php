<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/auth_helper.php';

// Solo admin
requireRole('admin');

use App\SupabaseClient;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();
$supabase = new SupabaseClient($_ENV['SUPABASE_URL'], $_ENV['SUPABASE_KEY']);

$message = '';
$error = '';

// --- ACCIONES ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'crear') {
        $user_id = $_POST['user_id'];
        $primer_nombre = $_POST['primer_nombre'];
        $segundo_nombre = $_POST['segundo_nombre'];
        $primer_apellido = $_POST['primer_apellido'];
        $segundo_apellido = $_POST['segundo_apellido'];
        $num_documento = $_POST['num_documento'];
        $num_registro = $_POST['num_registro'];
        $fecha_nacimiento = $_POST['fecha_nacimiento'];
        $genero = $_POST['genero'];
        $direccion = $_POST['direccion'];
        $especialidad_id = $_POST['especialidad_id'];
        $telefono = $_POST['telefono'];
        $email = $_POST['email']; // Ahora viene del input manual

        try {
            $data = [
                'user_id' => $user_id,
                'primer_nombre' => $primer_nombre,
                'segundo_nombre' => $segundo_nombre,
                'primer_apellido' => $primer_apellido,
                'segundo_apellido' => $segundo_apellido,
                'num_documento' => $num_documento,
                'num_registro' => $num_registro,
                'fecha_nacimiento' => $fecha_nacimiento,
                'genero' => $genero,
                'direccion' => $direccion,
                'especialidad_id' => $especialidad_id,
                'telefono' => $telefono,
                'email' => $email
            ];
            
            $supabase->insert('medicos', $data);
            $message = "Médico registrado correctamente.";
        } catch (Exception $e) {
            $error = "Error al crear médico: " . $e->getMessage();
        }
    }
}

// --- DATOS ---

// 1. Obtener Medicos Activos
try {
    // Traemos especialidad si es posible
    // Supabase join syntax: medicos(*, especialidades(nombre))
    $medicos = $supabase->select('medicos', '*, especialidades(nombre)');
} catch (Exception $e) {
    $medicos = [];
    $error = "Error cargando medicos: " . $e->getMessage();
}

// 2. Obtener Usuarios con rol 'medico' disponibles (que NO tengan perfil aun)
try {
    // Usamos ilike, y quitamos 'email' del select por si la columna no existe en users
    $allMedUsers = $supabase->select('users', 'id, nombre_completo', 'rol=ilike.medico');
    
    // Filtrar los que ya están en $medicos
    $linkedUserIds = array_column($medicos, 'user_id');
    
    $availableUsers = array_filter($allMedUsers, function($u) use ($linkedUserIds) {
        return !in_array($u['id'], $linkedUserIds);
    });
} catch (Exception $e) {
    $availableUsers = [];
}

// 3. Obtener Especialidades
try {
    $especialidades = $supabase->select('especialidades', '*');
} catch (Exception $e) {
    $especialidades = [];
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Gestión de Médicos</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <script>
        // Script para autocompletar nombre cuando se elige un usuario (Básico)
        function onUserSelect(select) {
            const option = select.options[select.selectedIndex];
            if (!option.value) return;

            const name = option.getAttribute('data-name');
            
            // Intentar separar nombre (muy básico)
            const parts = name.split(' ');
            if (parts.length > 0) document.getElementById('primer_nombre').value = parts[0];
            if (parts.length > 1) document.getElementById('primer_apellido').value = parts[parts.length - 1]; // Usar último como apellido
        }
    </script>
</head>
<body class="dashboard-container">
    <?php include '../includes/sidebar.php'; ?>
    <?php include '../includes/header.php'; ?>
    
    <main class="main-content">
        <div class="container">
            <h1>Gestión de Personal Médico</h1>
            
            <?php if($message): ?>
                <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            <?php if($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <!-- CREAR MÉDICO -->
            <div class="card mb-4">
                <h2>Vincular Nuevo Médico</h2>
                <p>Seleccione un usuario existente con rol 'medico' para crear su perfil profesional.</p>
                
                <?php if (empty($availableUsers)): ?>
                    <div class="alert alert-warning">
                        No hay usuarios con rol 'medico' pendientes de vincular. 
                        <a href="gestionar_usuarios.php">Crear Usuario primero</a>
                    </div>
                <?php else: ?>
                    <form method="POST">
                        <input type="hidden" name="action" value="crear">
                        
                        <div class="form-group" style="flex: 2;">
                            <label>Usuario (Rol Médico)</label>
                            <select name="user_id" required onchange="onUserSelect(this)">
                                <option value="">Seleccione Usuario...</option>
                                <?php foreach($availableUsers as $u): ?>
                                    <option value="<?= $u['id'] ?>" 
                                            data-name="<?= htmlspecialchars($u['nombre_completo']) ?>">
                                        <?= htmlspecialchars($u['nombre_completo']) ?> (<?= htmlspecialchars($u['username'] ?? 'Sin Usuario') ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Especialidad</label>
                            <select name="especialidad_id" required>
                                <option value="">Seleccione...</option>
                                <?php foreach($especialidades as $e): ?>
                                    <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="break"></div>

                        <!-- FILA 1: Nombres -->
                        <div class="grid grid-2">
                            <div class="form-group">
                                <label>Primer Nombre *</label>
                                <input type="text" name="primer_nombre" id="primer_nombre" required>
                            </div>
                            <div class="form-group">
                                <label>Segundo Nombre</label>
                                <input type="text" name="segundo_nombre">
                            </div>
                        </div>

                        <!-- FILA 2: Apellidos -->
                        <div class="grid grid-2">
                            <div class="form-group">
                                <label>Primer Apellido *</label>
                                <input type="text" name="primer_apellido" id="primer_apellido" required>
                            </div>
                            <div class="form-group">
                                <label>Segundo Apellido</label>
                                <input type="text" name="segundo_apellido">
                            </div>
                        </div>

                        <!-- FILA 3: Documentos -->
                        <div class="grid grid-2">
                            <div class="form-group">
                                <label>Número de Documento *</label>
                                <input type="text" name="num_documento" required placeholder="Cédula / DNI">
                            </div>
                            <div class="form-group">
                                <label>Registro Médico (TP) *</label>
                                <input type="text" name="num_registro" required placeholder="Tarjeta Profesional">
                            </div>
                        </div>

                        <!-- FILA 4: Detalles -->
                        <div class="grid grid-2">
                            <div class="form-group">
                                <label>Fecha de Nacimiento *</label>
                                <input type="date" name="fecha_nacimiento" required>
                            </div>
                            <div class="form-group">
                                <label>Género</label>
                                <select name="genero">
                                    <option value="">Seleccione...</option>
                                    <option value="M">Masculino</option>
                                    <option value="F">Femenino</option>
                                    <option value="O">Otro</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- FILA 5: Contacto -->
                        <div class="grid grid-2">
                            <div class="form-group">
                                <label>Teléfono</label>
                                <input type="text" name="telefono">
                            </div>
                            <div class="form-group">
                                <label>Dirección</label>
                                <input type="text" name="direccion">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Email Contacto (Opcional)</label>
                            <input type="email" name="email">
                        </div>
                        
                        <div class="form-group" style="display:flex; justify-content:flex-end; margin-top: 20px;">
                            <button type="submit" class="btn btn-primary">Registrar Perfil Médico</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>

            <!-- LISTADO -->
            <div class="card">
                <h2>Médicos Activos</h2>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Especialidad</th>
                                <th>Email</th>
                                <th>Teléfono</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($medicos)): ?>
                                <tr><td colspan="5">No hay médicos registrados en el sistema.</td></tr>
                            <?php else: ?>
                                <?php foreach($medicos as $m): 
                                    $espName = isset($m['especialidades']) ? $m['especialidades']['nombre'] : 'Sin Especialidad';
                                ?>
                                    <tr>
                                        <td>Dr. <?= htmlspecialchars($m['primer_nombre'] . ' ' . $m['primer_apellido']) ?></td>
                                        <td><span class="badge badge-primary"><?= htmlspecialchars($espName) ?></span></td>
                                        <td><?= htmlspecialchars($m['email']) ?></td>
                                        <td><?= htmlspecialchars($m['telefono']) ?></td>
                                        <td>
                                            <?php if($m['user_id']): ?>
                                                <span class="badge badge-success">Vinculado</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">Sin Usuario</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>
</body>
</html>
