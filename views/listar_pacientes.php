<?php
require_once '../vendor/autoload.php';

use App\SupabaseClient;
use App\Paciente;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$supabase = new SupabaseClient($_ENV['SUPABASE_URL'], $_ENV['SUPABASE_KEY']);
$pacienteModel = new Paciente($supabase);

// Procesamiento de búsqueda y filtros
$busqueda = $_GET['buscar'] ?? '';
$filtroEstrato = $_GET['estrato'] ?? '';
$pacientes = [];
$error = '';

try {
    if (!empty($busqueda)) {
        $pacientes = $pacienteModel->buscarPorNombre($busqueda);
    } elseif (!empty($filtroEstrato)) {
        $pacientes = $pacienteModel->obtenerPorEstrato($filtroEstrato);
    } else {
        $pacientes = $pacienteModel->obtenerTodos();
    }
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Pacientes - Sistema de Gestión Médica</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <div class="dashboard-container">
        <?php include '../includes/sidebar.php'; ?>
        <?php include '../includes/header.php'; ?>
        
        <main class="main-content">
    <div class="container">
        <div class="card card-gradient text-center mb-4">
            <h1>👥 Gestión de Pacientes</h1>
            <p style="margin-bottom: 0;">Buscar, ver y gestionar pacientes del sistema</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Barra de acciones principales -->
        <div class="card mb-4">
            <div class="flex justify-between items-center flex-wrap gap-2">
                <h2 style="margin: 0;">📋 Pacientes (<span id="total-count"><?= count($pacientes) ?></span>)</h2>
                <div class="flex gap-2 flex-wrap">
                    <a href="gestionar_pacientes.php" class="btn btn-success">➕ Nuevo Paciente</a>
                    <button onclick="exportarPacientes()" class="btn btn-export">📥 Exportar CSV</button>
                    <a href="../index.php" class="btn btn-outline">🏠 Inicio</a>
                </div>
            </div>
        </div>

        <!-- Panel de búsqueda y filtros -->
        <div class="card mb-4">
            <div class="filter-toggle" onclick="toggleFilters()">
                🔍 <span id="filter-toggle-text">Mostrar filtros</span>
            </div>
            
            <div id="filter-panel" style="display: none;">
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="search-input">Buscar</label>
                        <input 
                            type="text" 
                            id="search-input"
                            placeholder="🔍 Escribir para buscar..."
                            value="<?= htmlspecialchars($busqueda) ?>"
                        >
                    </div>

                    <div class="filter-group">
                        <label for="filter-estrato">Filtrar por estrato</label>
                        <select id="filter-estrato">
                            <option value="">Todos los estratos</option>
                            <?php for ($i = 1; $i <= 6; $i++): ?>
                                <option value="<?= $i ?>" <?= $filtroEstrato == $i ? 'selected' : '' ?>>
                                    Estrato <?= $i ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="page-size">Items por página</label>
                        <select id="page-size">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                </div>

                <div class="filter-actions">
                    <button onclick="aplicarFiltros()" class="btn btn-primary">Aplicar Filtros</button>
                    <button onclick="limpiarFiltros()" class="btn btn-secondary">Limpiar Filtros</button>
                </div>
            </div>
        </div>

        <!-- Tabla de pacientes -->
        <div class="card">
            <?php if (empty($pacientes)): ?>
                <div class="no-results">
                    <div class="no-results-icon">🔍</div>
                    <p>No se encontraron pacientes<?= $busqueda ? " con el término \"$busqueda\"" : '' ?></p>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table id="patients-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Documento</th>
                                <th>Nombre Completo</th>
                                <th>Estrato</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="table-body">
                            <?php foreach ($pacientes as $p): ?>
                                <tr>
                                    <td><?= htmlspecialchars($p['id_paciente']) ?></td>
                                    <td><?= htmlspecialchars($p['documento_id']) ?></td>
                                    <td>
                                        <strong>
                                            <?= htmlspecialchars($p['primer_nombre']) ?> 
                                            <?= htmlspecialchars($p['segundo_nombre'] ?? '') ?>
                                            <?= htmlspecialchars($p['primer_apellido']) ?> 
                                            <?= htmlspecialchars($p['segundo_apellido'] ?? '') ?>
                                        </strong>
                                    </td>
                                    <td>
                                        <span class="badge badge-primary">
                                            Estrato <?= htmlspecialchars($p['estrato'] ?? 'N/A') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                            <a href="ver_paciente.php?id=<?= $p['id_paciente'] ?>" class="btn btn-sm btn-primary">
                                                👁️ Ver
                                            </a>
                                            <a href="gestionar_pacientes.php?id=<?= $p['id_paciente'] ?>" class="btn btn-sm btn-secondary">
                                                ✏️ Editar
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Controles de paginación -->
                <div id="pagination-controls"></div>
            <?php endif; ?>
        </div>
    </div>

    <script src="../assets/js/app.js"></script>
    <script>
        // Datos de pacientes
        const pacientesData = <?= json_encode($pacientes) ?>;
        let pagination = null;

        // Inicializar paginación
        class PatientPagination extends Pagination {
            renderItems() {
                const tbody = document.getElementById('table-body');
                const items = this.currentItems;
                
                if (items.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5" style="text-align: center;">No hay pacientes para mostrar</td></tr>';
                    return;
                }

                tbody.innerHTML = items.map(p => `
                    <tr>
                        <td>${p.id_paciente}</td>
                        <td>${p.documento_id}</td>
                        <td><strong>${p.primer_nombre} ${p.segundo_nombre || ''} ${p.primer_apellido} ${p.segundo_apellido || ''}</strong></td>
                        <td><span class="badge badge-primary">Estrato ${p.estrato || 'N/A'}</span></td>
                        <td>
                            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                <a href="ver_paciente.php?id=${p.id_paciente}" class="btn btn-sm btn-primary">👁️ Ver</a>
                                <a href="gestionar_pacientes.php?id=${p.id_paciente}" class="btn btn-sm btn-secondary">✏️ Editar</a>
                            </div>
                        </td>
                    </tr>
                `).join('');

                // Actualizar contador
                document.getElementById('total-count').textContent = this.filteredItems.length;
            }
        }

        // Inicializar al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            pagination = new PatientPagination(pacientesData, 10, 'table-body', 'pagination-controls');
            pagination.render();

            // Event listener para cambio de tamaño de página
            document.getElementById('page-size').addEventListener('change', function() {
                pagination.setItemsPerPage(this.value);
            });

            // Event listener para búsqueda en tiempo real
            document.getElementById('search-input').addEventListener('input', function() {
                const searchText = this.value;
                pagination.applyFilter(item => 
                    FilterUtils.byText(item, searchText, ['primer_nombre', 'segundo_nombre', 'primer_apellido', 'segundo_apellido', 'documento_id'])
                );
            });
        });

        function toggleFilters() {
            const panel = document.getElementById('filter-panel');
            const text = document.getElementById('filter-toggle-text');
            
            if (panel.style.display === 'none') {
                panel.style.display = 'block';
                text.textContent = 'Ocultar filtros';
            } else {
                panel.style.display = 'none';
                text.textContent = 'Mostrar filtros';
            }
        }

        function aplicarFiltros() {
            const searchText = document.getElementById('search-input').value;
            const estrato = document.getElementById('filter-estrato').value;

            pagination.applyFilter(item => {
                let matches = true;

                // Filtro de búsqueda
                if (searchText) {
                    matches = matches && FilterUtils.byText(item, searchText, 
                        ['primer_nombre', 'segundo_nombre', 'primer_apellido', 'segundo_apellido', 'documento_id']
                    );
                }

                // Filtro de estrato
                if (estrato) {
                    matches = matches && FilterUtils.bySelect(item, estrato, 'estrato');
                }

                return matches;
            });
        }

        function limpiarFiltros() {
            document.getElementById('search-input').value = '';
            document.getElementById('filter-estrato').value = '';
            pagination.resetFilter();
        }

        function exportarPacientes() {
            const currentData = pagination.filteredItems.map(p => ({
                'ID': p.id_paciente,
                'Documento': p.documento_id,
                'Primer Nombre': p.primer_nombre,
                'Segundo Nombre': p.segundo_nombre || '',
                'Primer Apellido': p.primer_apellido,
                'Segundo Apellido': p.segundo_apellido || '',
                'Fecha Nacimiento': p.fecha_nacimiento || '',
                'Teléfono': p.telefono || '',
                'Email': p.email || '',
                'Dirección': p.direccion || '',
                'Estrato': p.estrato || '',
                'Fecha Creación': p.created_at
            }));

            const filename = `pacientes_${new Date().toISOString().split('T')[0]}.csv`;
            ExportUtils.toCSV(currentData, filename);
            UIUtils.showToast('Pacientes exportados exitosamente', 'success');
        }
    </script>
        </main>
    </div>
</body>
</html>
