<?php
require_once 'vendor/autoload.php';

use App\SupabaseClient;
use App\Diagnostico;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$supabase = new SupabaseClient($_ENV['SUPABASE_URL'], $_ENV['SUPABASE_KEY']);
$diagnosticoModel = new Diagnostico($supabase);

$resultados = [];
$termino = '';

// Procesar búsqueda
if (isset($_GET['buscar']) && !empty($_GET['buscar'])) {
    $termino = $_GET['buscar'];
    try {
        $resultados = $diagnosticoModel->buscarCIE10($termino);
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buscador CIE-10 - Sistema Médico</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        .search-container {
            max-width: 800px;
            margin: 0 auto;
        }
        .codigo-cie10 {
            font-family: monospace;
            background: var(--primary);
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: var(--radius-sm);
            font-weight: bold;
        }
        .resultado-item {
            padding: 1rem;
            border-bottom: 1px solid var(--gray-200);
            transition: background var(--transition-normal);
        }
        .resultado-item:hover {
            background: var(--gray-50);
        }
        .resultado-item:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card card-gradient text-center mb-4">
            <h1>🔍 Buscador CIE-10</h1>
            <p style="margin-bottom: 0;">Clasificación Internacional de Enfermedades - 10ª Revisión</p>
        </div>

        <div class="search-container">
            <div class="card mb-4">
                <form method="GET" class="flex gap-2">
                    <input 
                        type="text" 
                        name="buscar" 
                        placeholder="🔍 Buscar por código o descripción (Ej: J18 o neumonía)..." 
                        value="<?= htmlspecialchars($termino) ?>"
                        autofocus
                        style="margin: 0; flex: 1;"
                    >
                    <button type="submit" class="btn btn-primary" style="white-space: nowrap;">
                        Buscar
                    </button>
                    <?php if ($termino): ?>
                        <a href="buscar_cie10.php" class="btn btn-secondary">Limpiar</a>
                    <?php endif; ?>
                </form>

                <div style="margin-top: 1rem;">
                    <small class="help-text">
                        <strong>Ejemplos:</strong> 
                        "J18" (Neumonía), "I10" (Hipertensión), "E11" (Diabetes tipo 2), 
                        "dolor", "fiebre", etc.
                    </small>
                </div>
            </div>

            <?php if ($termino): ?>
                <div class="card">
                    <h2>📋 Resultados (<?= count($resultados) ?>)</h2>
                    
                    <?php if (empty($resultados)): ?>
                        <div class="alert alert-info">
                            ℹ️ No se encontraron resultados para "<?= htmlspecialchars($termino) ?>"
                        </div>
                        <p>Intenta con otros términos o códigos.</p>
                    <?php else: ?>
                        <div style="max-height: 600px; overflow-y: auto;">
                            <?php foreach ($resultados as $codigo): ?>
                                <div class="resultado-item">
                                    <div style="display: flex; justify-content: space-between; align-items: start; gap: 1rem;">
                                        <div style="flex: 1;">
                                            <div style="margin-bottom: 0.5rem;">
                                                <span class="codigo-cie10"><?= htmlspecialchars($codigo['codigo']) ?></span>
                                            </div>
                                            <div style="color: var(--gray-900);">
                                                <?= htmlspecialchars($codigo['descripcion']) ?>
                                            </div>
                                        </div>
                                        <div>
                                            <button 
                                                onclick="seleccionarCodigo('<?= htmlspecialchars($codigo['id']) ?>', '<?= htmlspecialchars($codigo['codigo']) ?>', '<?= htmlspecialchars($codigo['descripcion']) ?>')"
                                                class="btn btn-sm btn-success">
                                                ✓ Seleccionar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="card text-center" style="padding: 3rem;">
                    <div style="font-size: 4rem; margin-bottom: 1rem;">🔍</div>
                    <h3>Busca códigos CIE-10</h3>
                    <p style="color: var(--gray-600); max-width: 500px; margin: 1rem auto;">
                        Escribe un código (como J18) o una descripción de enfermedad 
                        (como "neumonía") para buscar en la clasificación internacional.
                    </p>
                </div>
            <?php endif; ?>

            <div style="text-align: center; margin-top: 2rem;">
                <a href="index.php" class="btn btn-outline">🏠 Volver al Inicio</a>
            </div>
        </div>
    </div>

    <script>
        function seleccionarCodigo(id, codigo, descripcion) {
            // Si esta página se abrió desde otra ventana (para seleccionar código)
            if (window.opener) {
                window.opener.postMessage({
                    type: 'cie10_seleccionado',
                    id: id,
                    codigo: codigo,
                    descripcion: descripcion
                }, '*');
                window.close();
            } else {
                // Si se abrió directamente, copiar al portapapeles
                const texto = codigo + ' - ' + descripcion;
                navigator.clipboard.writeText(texto).then(() => {
                    alert('✓ Código copiado al portapapeles:\n' + texto);
                });
            }
        }

        // Atajos de teclado
        document.addEventListener('keydown', function(e) {
            // Ctrl/Cmd + K para enfocar búsqueda
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                document.querySelector('input[name="buscar"]').focus();
            }
        });
    </script>
</body>
</html>
