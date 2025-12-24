<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POO con PHP - Sistema de Gestión Médica</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>
        
        <!-- Header -->
        <?php include 'includes/header.php'; ?>
        
        <!-- Main Content -->
        <main class="main-content">
            <?php
            require_once 'vendor/autoload.php';
            
            use App\SupabaseClient;
            use App\Paciente;
            use App\HistoriaClinica;
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
                    $paciente = new Paciente($supabase);
                    $historiaClinica = new HistoriaClinica($supabase);
                    
                    // Obtener estadísticas
                    try {
                        $totalPacientes = $paciente->contarTotal();
                        $totalHistorias = $historiaClinica->contarTotal();
                    } catch (Exception $e) {
                        // Silenciar errores de estadísticas
                    }
                }
            } catch (Exception $e) {
                // Silenciar errores de configuración
            }
            ?>
            
            <div class="container">
                <!-- Header -->
                <div class="card card-gradient text-center mb-4 fade-in" style="background: linear-gradient(135deg, #1e293b, #334155);">
                    <h1>👋 Bienvenido al Sistema</h1>
                    <p style="font-size: 1.1rem; margin-bottom: 0;">Panel de Control General</p>
                </div>

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