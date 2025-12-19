<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POO con PHP - Sistema de Gestión Médica</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <?php
    require_once 'vendor/autoload.php';
    
    use App\SupabaseClient;
    use App\Paciente;
    use App\HistoriaClinica;
    use Dotenv\Dotenv;
    
    // Inicializar variables
    $totalPacientes = 0;
    $totalHistorias = 0;
    $systemStatus = [];
    
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
        <div class="card card-gradient text-center mb-4 fade-in">
            <h1>🏥 Sistema de Gestión Médica</h1>
            <p style="font-size: 1.1rem; margin-bottom: 0;">POO con PHP & Supabase</p>
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

        <!-- Navegación Principal -->
        <div class="card mb-4">
            <h2>🎯 Menú Principal</h2>
            
            <h4 style="color: var(--gray-600); margin-top: 1.5rem;">👥 Pacientes</h4>
            <div class="grid grid-2">
                <a href="views/gestionar_pacientes.php" class="btn btn-success">
                    ➕ Nuevo Paciente (Básico)
                </a>
                <a href="views/gestionar_pacientes_completo.php" class="btn btn-primary">
                    ➕ Nuevo Paciente (Completo)
                </a>
                <a href="views/listar_pacientes.php" class="btn btn-primary">
                    👥 Gestionar Pacientes
                </a>
            </div>

            <h4 style="color: var(--gray-600); margin-top: 1.5rem;">🩺 Consultas Médicas</h4>
            <div class="grid grid-2">
                <a href="views/nueva_consulta.php" class="btn btn-success">
                    🩺 Nueva Consulta
                </a>
                <a href="views/listar_consultas.php" class="btn btn-primary">
                    📋 Ver Consultas
                </a>
                <a href="views/buscar_cie10.php" class="btn btn-primary">
                    🔍 Buscar CIE-10
                </a>
            </div>

            <h4 style="color: var(--gray-600); margin-top: 1.5rem;">📋 Historias Clínicas</h4>
            <div class="grid grid-2">
                <a href="views/historias_clinicas.php" class="btn btn-success">
                    📋 Nueva Historia Clínica
                </a>
                <a href="views/listar_historias.php" class="btn btn-primary">
                    📚 Ver Historias Clínicas
                </a>
            </div>
            
            <div style="margin-top: 1rem;">
                <a href="tests/test_conexion.php" class="btn btn-outline" style="width: 100%;">
                    🔧 Probar Conexión
                </a>
            </div>
        </div>

        <!-- Estado del Sistema -->
        <div class="card">
            <h2>🔍 Estado del Sistema</h2>
            
            <?php if (file_exists('.env')): ?>
                <div class="alert alert-success">
                    ✅ Archivo .env encontrado y configurado
                </div>
            <?php else: ?>
                <div class="alert alert-error">
                    ❌ Archivo .env no encontrado
                </div>
            <?php endif; ?>
            
            <?php if (file_exists('vendor/autoload.php')): ?>
                <div class="alert alert-success">
                    ✅ Dependencias instaladas correctamente
                </div>
            <?php else: ?>
                <div class="alert alert-error">
                    ❌ Dependencias no instaladas - Ejecuta: <code>composer install</code>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_ENV['SUPABASE_URL']) && isset($_ENV['SUPABASE_KEY'])): ?>
                <div class="alert alert-success">
                    ✅ Variables de entorno configuradas
                </div>
                <p style="color: var(--gray-600); margin-top: var(--spacing-md);">
                    <strong>URL:</strong> <?= htmlspecialchars($_ENV['SUPABASE_URL']) ?>
                </p>
            <?php else: ?>
                <div class="alert alert-error">
                    ❌ Variables de entorno no configuradas
                </div>
            <?php endif; ?>
        </div>

        <!-- Tecnologías -->
        <div class="card mt-4">
            <h2>📚 Tecnologías Utilizadas</h2>
            <div class="grid grid-2">
                <div>
                    <h4>Backend</h4>
                    <ul style="list-style: none; padding: 0;">
                        <li class="mb-1">🐘 <strong>PHP 7.4+</strong> - Programación Orientada a Objetos</li>
                        <li class="mb-1">📦 <strong>Composer</strong> - Gestión de dependencias</li>
                        <li class="mb-1">🌐 <strong>Guzzle HTTP</strong> - Cliente HTTP para API</li>
                    </ul>
                </div>
                <div>
                    <h4>Frontend & Database</h4>
                    <ul style="list-style: none; padding: 0;">
                        <li class="mb-1">🎨 <strong>CSS3 Moderno</strong> - Variables, Grid, Animaciones</li>
                        <li class="mb-1">🗄️ <strong>Supabase</strong> - Base de datos PostgreSQL</li>
                        <li class="mb-1">🔐 <strong>phpdotenv</strong> - Variables de entorno</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <style>
        .mb-1 { margin-bottom: 0.5rem; }
        .mb-4 { margin-bottom: 2rem; }
        .mt-4 { margin-top: 2rem; }
        code {
            background: var(--gray-200);
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
        }
    </style>
</body>
</html>