<?php
// Determinar ruta base para enlaces
$isRoot = !file_exists('vendor/autoload.php'); // Si no existe vendor en el dir actual, estamos en views
$basePath = $isRoot ? '../' : './';
$viewsPath = 'views/';

// Función para determinar si el link está activo
function isActive($path) {
    $current = basename($_SERVER['PHP_SELF']);
    return $current === $path ? 'active' : '';
}
?>

<aside class="sidebar">
    <div class="sidebar-header">
        🏥 Gestión Médica
    </div>
    
    <div class="sidebar-menu">
        <!-- GENERAL -->
        <div class="menu-category">General</div>
        <a href="<?= $basePath ?>index.php" class="menu-item <?= isActive('index.php') ?>">
            <span class="menu-icon">🏠</span> Dashboard
        </a>

        <!-- AGENDA / CITAS (Todos los roles tienen acceso, pero vistas diferentes) -->
        <div class="menu-category">Agenda</div>
        <?php if (hasRole('medico') || hasRole('admin')): ?>
            <a href="<?= $basePath ?><?= $viewsPath ?>calendario_citas.php" class="menu-item <?= isActive('calendario_citas.php') ?>">
                <span class="menu-icon">📅</span> Mi Calendario
            </a>
        <?php endif; ?>
        
        <?php if (hasRole('cajero') || hasRole('admin')): ?>
            <a href="<?= $basePath ?><?= $viewsPath ?>gestion_citas.php" class="menu-item <?= isActive('gestion_citas.php') ?>">
                <span class="menu-icon">✒️</span> Gestionar Citas
            </a>
        <?php endif; ?>

        <!-- PACIENTES (Cajero y Medico leen/crean, Admin todo) -->
        <div class="menu-category">Pacientes</div>
        <a href="<?= $basePath ?><?= $viewsPath ?>gestionar_pacientes.php" class="menu-item <?= isActive('gestionar_pacientes.php') ?>">
            <span class="menu-icon">➕</span> Nuevo Paciente
        </a>
        <a href="<?= $basePath ?><?= $viewsPath ?>listar_pacientes.php" class="menu-item <?= isActive('listar_pacientes.php') ?> <?= isActive('ver_paciente.php') ?>">
            <span class="menu-icon">👥</span> Listar Pacientes
        </a>

        <!-- CONSULTAS / HISTORIAS (Medico y Admin) -->
        <?php if (hasRole('medico') || hasRole('admin')): ?>
            <div class="menu-category">Consultas</div>
            <a href="<?= $basePath ?><?= $viewsPath ?>nueva_consulta.php" class="menu-item <?= isActive('nueva_consulta.php') ?>">
                <span class="menu-icon">🩺</span> Nueva Consulta
            </a>
            <a href="<?= $basePath ?><?= $viewsPath ?>listar_consultas.php" class="menu-item <?= isActive('listar_consultas.php') ?>">
                <span class="menu-icon">📋</span> Historial Consultas
            </a>
            
            <div class="menu-category">Historias Clínicas</div>
            <a href="<?= $basePath ?><?= $viewsPath ?>historias_clinicas.php" class="menu-item <?= isActive('historias_clinicas.php') ?>">
                <span class="menu-icon">📝</span> Nueva Historia
            </a>
            <a href="<?= $basePath ?><?= $viewsPath ?>listar_historias.php" class="menu-item <?= isActive('listar_historias.php') ?>">
                <span class="menu-icon">📚</span> Ver Historias
            </a>
        <?php endif; ?>

        <!-- HERRAMIENTAS -->
        <div class="menu-category">Herramientas</div>
        <a href="<?= $basePath ?><?= $viewsPath ?>buscar_cie10.php" class="menu-item <?= isActive('buscar_cie10.php') ?>">
            <span class="menu-icon">🔍</span> Códigos CIE-10
        </a>
        
        <?php if (hasRole('admin')): ?>
            <div class="menu-category">Administración</div>
            <a href="<?= $basePath ?><?= $viewsPath ?>gestionar_usuarios.php" class="menu-item <?= isActive('gestionar_usuarios.php') ?>">
                <span class="menu-icon">🛠️</span> Gestionar Usuarios
            </a>
        <?php endif; ?>
    </div>
</aside>
