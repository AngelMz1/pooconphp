<?php
// Incluir funciones de autenticación
require_once __DIR__ . '/auth_helper.php';
require_once __DIR__ . '/icons.php';

// Determinar ruta base para enlaces
// Usamos SCRIPT_NAME para saber en qué archivo estamos realmente
$scriptName = $_SERVER['SCRIPT_NAME']; // ej: /pooconphp/index.php o /pooconphp/views/login.php
$inViews = strpos($scriptName, '/views/') !== false;

// Si estamos en una vista (subdir views/), debemos subir un nivel para los assets/includes
// y mantenernos en el nivel para otros links de views.
// Para ir al root (index.php) -> '../'
// Para ir a views -> './'
// SI estamos en root:
// Para ir al root -> './'
// Para ir a views -> 'views/'

$basePath = $inViews ? '../' : './'; 
// Estándar: links a views desde root son 'views/algo.php'
// Links a views desde views son 'algo.php' (o '../views/algo.php' que es redundante pero valido)

// Para simplificar, construimos absolute relative paths
$viewsPath = $inViews ? '' : 'views/'; // Si estoy en views/, no agrego prefijo 'views/'
$rootPath = $inViews ? '../' : './';

// Función para determinar si el link está activo
if (!function_exists('isActive')) {
    function isActive($path) {
        if (strpos($path, '/') !== false) {
            $path = basename($path);
        }
        $current = basename($_SERVER['PHP_SELF']);
        return $current === $path ? 'active' : '';
    }
}
?>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="flex items-center gap-2">
            <span style="color:var(--primary);"><?= getIcon('activity') ?></span>
            <span>Gestión Médica</span>
        </div>
        <!-- Mobile Close Button (Hidden on Desktop) -->
        <button id="sidebar-close" class="btn-icon mobile-only">✕</button>
    </div>
    
    <div class="sidebar-menu">
        <!-- GENERAL -->
        <div class="menu-category">General</div>
        <a href="<?= $rootPath ?>index.php" class="menu-item <?= isActive('index.php') ?>">
            <span class="menu-icon"><?= getIcon('home') ?></span> Dashboard
        </a>

        <!-- AGENDA / CITAS (Todos los roles tienen acceso, pero vistas diferentes) -->
        <div class="menu-category">Agenda</div>
        <?php if (hasRole('medico') || hasRole('admin')): ?>
            <a href="<?= $viewsPath ?>calendario_citas.php" class="menu-item <?= isActive('calendario_citas.php') ?>">
                <span class="menu-icon"><?= getIcon('calendar') ?></span> Mi Calendario
            </a>
        <?php endif; ?>
        
        <?php if (hasRole('cajero') || hasRole('admin')): ?>
            <a href="<?= $viewsPath ?>gestion_citas.php" class="menu-item <?= isActive('gestion_citas.php') ?>">
                <span class="menu-icon"><?= getIcon('edit') ?></span> Gestionar Citas
            </a>
        <?php endif; ?>

        <!-- PACIENTES (Cajero y Medico leen/crean, Admin todo) -->
        <div class="menu-category">Pacientes</div>
        <a href="<?= $viewsPath ?>gestionar_pacientes.php" class="menu-item <?= isActive('gestionar_pacientes.php') ?>">
            <span class="menu-icon"><?= getIcon('user-plus') ?></span> Nuevo Paciente
        </a>
        <a href="<?= $viewsPath ?>listar_pacientes.php" class="menu-item <?= isActive('listar_pacientes.php') ?> <?= isActive('ver_paciente.php') ?>">
            <span class="menu-icon"><?= getIcon('users') ?></span> Listar Pacientes
        </a>

        <!-- CONSULTAS / HISTORIAS (Medico y Admin) -->
        <?php if (hasRole('medico') || hasRole('admin')): ?>
            <div class="menu-category">Consultas</div>
            <a href="<?= $viewsPath ?>nueva_consulta.php" class="menu-item <?= isActive('nueva_consulta.php') ?>">
                <span class="menu-icon"><?= getIcon('stethoscope') ?></span> Nueva Consulta
            </a>
            <a href="<?= $viewsPath ?>listar_consultas.php" class="menu-item <?= isActive('listar_consultas.php') ?>">
                <span class="menu-icon"><?= getIcon('file-text') ?></span> Historial Consultas
            </a>
            
            <div class="menu-category">Historias Clínicas</div>
            <a href="<?= $viewsPath ?>historias_clinicas.php" class="menu-item <?= isActive('historias_clinicas.php') ?>">
                <span class="menu-icon"><?= getIcon('file-text') ?></span> Nueva Historia
            </a>
            <a href="<?= $viewsPath ?>listar_historias.php" class="menu-item <?= isActive('listar_historias.php') ?>">
                <span class="menu-icon"><?= getIcon('file-text') ?></span> Ver Historias
            </a>
        <?php endif; ?>

        <!-- HERRAMIENTAS -->
        <div class="menu-category">Herramientas</div>
        <a href="<?= $viewsPath ?>buscar_cie10.php" class="menu-item <?= isActive('buscar_cie10.php') ?>">
            <span class="menu-icon"><?= getIcon('search') ?></span> Códigos CIE-10
        </a>
        
        <?php if (hasRole('admin')): ?>
            <div class="menu-category">Administración</div>
            <a href="<?= $viewsPath ?>gestionar_usuarios.php" class="menu-item <?= isActive('gestionar_usuarios.php') ?>">
                <span class="menu-icon"><?= getIcon('users') ?></span> Gestionar Usuarios
            </a>
            <a href="<?= $viewsPath ?>gestion_medicos.php" class="menu-item <?= isActive('gestion_medicos.php') ?>">
                <span class="menu-icon"><?= getIcon('users') ?></span> Gestionar Médicos
            </a>
            <a href="<?= $viewsPath ?>configuracion.php" class="menu-item <?= isActive('configuracion.php') ?>">
                <span class="menu-icon"><?= getIcon('settings') ?></span> Configuración
            </a>
            <a href="<?= $viewsPath ?>gestionar_tarifarios.php" class="menu-item <?= isActive('gestionar_tarifarios.php') ?>">
                <span class="menu-icon"><?= getIcon('dollar-sign') ?></span> Tarifarios
            </a>
            <a href="<?= $viewsPath ?>sincronizar.php" class="menu-item <?= isActive('sincronizar.php') ?>">
                <span class="menu-icon"><?= getIcon('cloud') ?></span> Sincronización
            </a>
        <?php endif; ?>
    </div>
    <script>
        document.getElementById('sidebar-close').addEventListener('click', function() {
            document.getElementById('sidebar').classList.remove('open');
            document.getElementById('sidebar-overlay').classList.remove('active');
        });
    </script>
</aside>
