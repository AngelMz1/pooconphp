<?php
require_once 'vendor/autoload.php';
require_once 'includes/auth_helper.php';

// Proteger Dashboard
requireLogin();

use App\SupabaseClient;
use App\Paciente;
use App\HistoriaClinica; 
use App\Consulta;
use App\Medico;
use Dotenv\Dotenv;

// Inicializar variables
$totalPacientes = 0;
$totalHistorias = 0;

try {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();
    
    // Verificar configuración
    if (isset($_ENV['SUPABASE_URL']) && isset($_ENV['SUPABASE_KEY'])) {
        $supabase = new SupabaseClient($_ENV['SUPABASE_URL'], $_ENV['SUPABASE_KEY']);
        
        // Obtener estadísticas si las clases existen
        // Simplificación para no romper si no existen los modelos perfectos aun
        if (class_exists('App\Models\Paciente')) {
             $paciente = new Paciente($supabase);
             $totalPacientes = $paciente->contarTotal();
        }
        
        // Count manual si no hay modelo historia
        // $totalHistorias = ...
    }
} catch (Exception $e) {
    // Silenciar errores de configuración por ahora
}

// Lógica específica para Medico
$consultasPendientes = [];
$esMedico = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'medico';

if ($esMedico && isset($_SESSION['user_id'])) {
    try {
        $medicoModel = new Medico($supabase);
        $consultaModel = new Consulta($supabase);
        
        // Obtener perfil médico asociado al usuario
        $perfilMedico = $medicoModel->obtenerPorUserId($_SESSION['user_id']);
        
        if ($perfilMedico) {
            $consultasPendientes = $consultaModel->obtenerPendientesPorMedico($perfilMedico['id']);
        }
    } catch (Exception $e) {
        // Manejar error silenciosamente o loguear
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POO con PHP - Sistema de Gestión Médica</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>
        
        <!-- Header -->
        <?php include 'includes/header.php'; ?>
        
        <!-- Main Content -->
        <main class="main-content">
            <div class="container">
                <!-- Header -->
                <div class="card card-gradient text-center mb-4 fade-in" style="background: linear-gradient(135deg, #1e293b, #334155);">
                    <h1>👋 Bienvenido al Sistema</h1>
                    <p style="font-size: 1.1rem; margin-bottom: 0;">
                        <?= $esMedico ? 'Panel Médico' : 'Panel de Control General' ?>
                    </p>
                </div>

                <?php if ($esMedico): ?>
                    <!-- Panel Específico de Médico -->
                    <div class="card mb-4 fade-in">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                            <h2>🩺 Mis Consultas Pendientes</h2>
                            <span class="badge badge-primary"><?= count($consultasPendientes) ?> Pendientes</span>
                        </div>

                        <?php if (empty($consultasPendientes)): ?>
                            <div class="alert alert-success">
                                ✅ No tienes pacientes en espera en este momento.
                            </div>
                        <?php else: ?>
                            <div class="grid grid-3">
                                <?php foreach ($consultasPendientes as $consulta): ?>
                                    <div class="card" style="border-left: 5px solid var(--primary);">
                                        <h3><?= htmlspecialchars($consulta['pacientes']['primer_nombre'] . ' ' . $consulta['pacientes']['primer_apellido']) ?></h3>
                                        <p style="color: var(--gray-600); font-size: 0.9rem;">
                                            🆔 <?= htmlspecialchars($consulta['pacientes']['documento_id']) ?>
                                        </p>
                                        <hr style="margin: 0.5rem 0; opacity: 0.2;">
                                        <p><strong>Motivo:</strong><br><?= htmlspecialchars(substr($consulta['motivo_consulta'], 0, 50)) ?>...</p>
                                        <div style="margin-top: 1rem;">
                                            <a href="views/atender_consulta.php?id=<?= $consulta['id_consulta'] ?>" class="btn btn-primary btn-sm" style="display: block; text-align: center;">
                                                Atender Paciente ➡️
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Estadísticas -->
                <div class="grid grid-3 mb-4 fade-in">
                    <div class="stat-card">
                        <div class="stat-icon">👥</div>
                        <div class="stat-value"><?= $totalPacientes ?></div>
                        <div class="stat-label">Pacientes</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">📋</div>
                        <div class="stat-value"><?= $totalHistorias ?></div>
                        <div class="stat-label">Historias Clínicas</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">✅</div>
                        <div class="stat-value">100%</div>
                        <div class="stat-label">Sistema Activo</div>
                    </div>
                </div>

                <!-- Estado del Sistema -->
                <div class="card">
                    <h2>🔍 Estado del Sistema</h2>
                    
                    <div class="grid grid-2">
                        <div>
                            <?php if (file_exists('.env')): ?>
                                <div class="alert alert-success">
                                    ✅ Archivo .env encontrado
                                </div>
                            <?php else: ?>
                                <div class="alert alert-error">
                                    ❌ Archivo .env no encontrado
                                </div>
                            <?php endif; ?>
                            
                            <?php if (file_exists('vendor/autoload.php')): ?>
                                <div class="alert alert-success">
                                    ✅ Dependencias instaladas
                                </div>
                            <?php else: ?>
                                <div class="alert alert-error">
                                    ❌ Dependencias no instaladas
                                </div>
                            <?php endif; ?>
                        </div>
                        <div>
                            <?php if (isset($_ENV['SUPABASE_URL']) && isset($_ENV['SUPABASE_KEY'])): ?>
                                <div class="alert alert-success">
                                    ✅ Conexión Configurada
                                </div>
                                <p style="color: var(--gray-600); font-size: 0.8rem; margin-top: 0.5rem;">
                                    <strong>URL:</strong> <?= htmlspecialchars($_ENV['SUPABASE_URL']) ?>
                                </p>
                            <?php else: ?>
                                <div class="alert alert-error">
                                    ❌ Variables no configuradas
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>