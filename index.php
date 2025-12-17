<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POO con PHP - Supabase</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .card { border: 1px solid #ddd; padding: 20px; margin: 10px 0; border-radius: 5px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        .btn { padding: 10px 15px; margin: 5px; text-decoration: none; background: #007cba; color: white; border-radius: 3px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 POO con PHP - Aplicativo Supabase</h1>
        
        <div class="card">
            <h2>📋 Menú Principal</h2>
            <a href="test_conexion.php" class="btn">🔧 Probar Conexión</a>
            <a href="check_tables.php" class="btn">📊 Verificar Tablas</a>
            <a href="test_paciente.php" class="btn">👤 Probar Pacientes</a>
            <a href="historias_clinicas.php" class="btn">📋 Nueva Historia Clínica</a>
            <a href="test_historia.php" class="btn">🧪 Probar Historias</a>
        </div>

        <?php
        require_once 'vendor/autoload.php';
        
        use App\SupabaseClient;
        use Dotenv\Dotenv;
        
        echo "<div class='card'>";
        echo "<h2>🔍 Estado del Sistema</h2>";
        
        // Verificar .env
        if (file_exists('.env')) {
            echo "<p class='success'>✅ Archivo .env encontrado</p>";
        } else {
            echo "<p class='error'>❌ Archivo .env no encontrado</p>";
        }
        
        // Verificar vendor
        if (file_exists('vendor/autoload.php')) {
            echo "<p class='success'>✅ Dependencias instaladas</p>";
        } else {
            echo "<p class='error'>❌ Dependencias no instaladas - Ejecuta: composer install</p>";
        }
        
        // Verificar variables de entorno
        try {
            $dotenv = Dotenv::createImmutable(__DIR__);
            $dotenv->load();
            
            if (isset($_ENV['SUPABASE_URL']) && isset($_ENV['SUPABASE_KEY'])) {
                echo "<p class='success'>✅ Variables de entorno configuradas</p>";
                echo "<p><strong>URL:</strong> " . $_ENV['SUPABASE_URL'] . "</p>";
            } else {
                echo "<p class='error'>❌ Variables de entorno no configuradas</p>";
            }
        } catch (Exception $e) {
            echo "<p class='error'>❌ Error cargando .env: " . $e->getMessage() . "</p>";
        }
        
        echo "</div>";
        ?>
        
        <div class="card">
            <h2>📚 Documentación</h2>
            <p>Este aplicativo utiliza:</p>
            <ul>
                <li><strong>PHP</strong> - Programación Orientada a Objetos</li>
                <li><strong>Supabase</strong> - Base de datos y API</li>
                <li><strong>Composer</strong> - Gestión de dependencias</li>
                <li><strong>Guzzle HTTP</strong> - Cliente HTTP para API</li>
            </ul>
        </div>
    </div>
</body>
</html>