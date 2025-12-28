<?php
require_once '../vendor/autoload.php';

use App\SupabaseClient;
use App\Diagnostico;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$supabase = new SupabaseClient($_ENV['SUPABASE_URL'], $_ENV['SUPABASE_KEY']);
$diagnosticoModel = new Diagnostico($supabase);

$resultados = [];
$termino = '';

// Procesar búsqueda tradicional (fallback)
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
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .search-container {
            max-width: 800px;
            margin: 0 auto;
            position: relative;
        }
        .codigo-cie10 {
            font-family: monospace;
            background: var(--primary);
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: var(--radius-sm);
            font-weight: bold;
        }
        
        /* Estilos para el autocompletado */
        .live-results-container {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: var(--bg-card); /* Changed from white */
            border: 1px solid var(--gray-300);
            border-radius: 0 0 var(--radius-md) var(--radius-md);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            z-index: 50;
            max-height: 400px;
            overflow-y: auto;
            display: none; /* Oculto por defecto */
        }
        
        .live-result-item {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid var(--gray-100);
            cursor: pointer;
            transition: background 0.2s;
            display: flex;
            align-items: center;
            gap: 1rem;
            color: var(--dark); /* Reinforce text color */
        }
        
        .live-result-item:hover {
            background-color: var(--bg-hover);
        }
        
        .live-result-item:last-child {
            border-bottom: none;
        }

        .no-results {
            padding: 1rem;
            text-align: center;
            color: var(--gray-500);
            font-style: italic;
        }

        /* Estilos lista estática (existente) */
        .resultado-item {
            padding: 1rem;
            border-bottom: 1px solid var(--gray-200);
            transition: background var(--transition-normal);
        }
        .resultado-item:hover {
            background: var(--bg-secondary); /* Changed from var(--gray-50) */
        }
        .resultado-item:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include '../includes/sidebar.php'; ?>
        <?php include '../includes/header.php'; ?>
        
        <main class="main-content">
    <div class="container">
        <div class="card card-gradient text-center mb-4">
            <h1>🔍 Buscador CIE-10</h1>
            <p style="margin-bottom: 0;">Clasificación Internacional de Enfermedades - 10ª Revisión</p>
        </div>

        <div class="search-container">
            <div class="card mb-4" style="position: relative; z-index: 51;">
                <form method="GET" class="flex gap-2" autocomplete="off">
                    <div style="position: relative; flex: 1;">
                        <input 
                            type="text" 
                            id="search-input"
                            name="buscar" 
                            placeholder="🔍 Buscar por código o descripción (Ej: J18 o neumonía)..." 
                            value="<?= htmlspecialchars($termino) ?>"
                            autofocus
                            style="margin: 0; width: 100%;"
                        >
                        <!-- Contenedor de resultados en vivo -->
                        <div id="live-results" class="live-results-container"></div>
                    </div>
                    
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

            <!-- Resultados tradicionales (si se hizo submit) -->
            <?php if ($termino): ?>
                <div class="card">
                    <h2>📋 Resultados de búsqueda "<?= htmlspecialchars($termino) ?>"</h2>
                    
                    <?php if (empty($resultados)): ?>
                        <div class="alert alert-info">
                            ℹ️ No se encontraron resultados.
                        </div>
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
            <?php endif; ?>

            <div style="text-align: center; margin-top: 2rem;">
                <a href="../index.php" class="btn btn-outline">🏠 Volver al Inicio</a>
            </div>
        </div>
    </div>

    <script>
        const searchInput = document.getElementById('search-input');
        const resultsContainer = document.getElementById('live-results');
        let debounceTimer;

        // Función para seleccionar código
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
                
                // Ocultar resultados
                resultsContainer.style.display = 'none';
                searchInput.value = texto;
            }
        }

        // Búsqueda en vivo
        searchInput.addEventListener('input', function() {
            const query = this.value.trim();
            
            // Limpiar timer anterior
            clearTimeout(debounceTimer);

            // Ocultar si está vacío
            if (query.length < 2) {
                resultsContainer.style.display = 'none';
                return;
            }

            // Debounce de 300ms
            debounceTimer = setTimeout(() => {
                fetch(`../api/api_cie10.php?q=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(data => {
                        resultsContainer.innerHTML = '';
                        
                        if (data.length > 0) {
                            data.forEach(item => {
                                const div = document.createElement('div');
                                div.className = 'live-result-item';
                                div.innerHTML = `
                                    <span class="codigo-cie10">${item.codigo}</span>
                                    <span>${item.descripcion}</span>
                                `;
                                div.onclick = () => seleccionarCodigo(item.id, item.codigo, item.descripcion);
                                resultsContainer.appendChild(div);
                            });
                            resultsContainer.style.display = 'block';
                        } else {
                            resultsContainer.innerHTML = '<div class="no-results">No se encontraron resultados</div>';
                            resultsContainer.style.display = 'block';
                        }
                    })
                    .catch(err => {
                        console.error('Error buscando:', err);
                    });
            }, 300);
        });

        // Cerrar al hacer clic fuera
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !resultsContainer.contains(e.target)) {
                resultsContainer.style.display = 'none';
            }
        });

        // Atajos de teclado
        document.addEventListener('keydown', function(e) {
            // Ctrl/Cmd + K para enfocar búsqueda
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                searchInput.focus();
            }
            // Escape para cerrar
            if (e.key === 'Escape') {
                resultsContainer.style.display = 'none';
            }
        });
    </script>
        </main>
    </div>
</body>
</html>
