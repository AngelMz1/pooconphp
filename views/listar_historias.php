<?php
require_once __DIR__ . '/../includes/auth_helper.php';

// Verificar permiso para ver historias clínicas
requirePermission('ver_historia');
require_once '../vendor/autoload.php';

use App\DatabaseFactory;
use App\HistoriaClinica;
use App\Paciente;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
try {
    $dotenv->safeLoad();
} catch (Exception $e) { }

$supabase = DatabaseFactory::create();
$historiaModel = new HistoriaClinica($supabase);
$pacienteModel = new Paciente($supabase);

$error = '';
$historias = [];

$busqueda = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';
$historias = [];

try {
    if (!empty($busqueda)) {
        $historias = $historiaModel->buscarGeneral($busqueda);
    }
    // Si no hay búsqueda, $historias permanece vacío (seguridad)
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historias Clínicas - Sistema de Gestión Médica</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <div class="dashboard-container">
        <?php include '../includes/sidebar.php'; ?>
        <?php include '../includes/header.php'; ?>
        
        <main class="main-content">
    <div class="container">
        <div class="card card-gradient text-center mb-4">
            <h1>📋 Historias Clínicas</h1>
            <p style="margin-bottom: 0;">Ver y gestionar todas las historias clínicas del sistema</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Barra de acciones principales -->
        <div class="card mb-4">
            <div class="flex justify-between items-center flex-wrap gap-2">
                <h2 style="margin: 0;">📚 Historias (<span id="total-count"><?= count($historias) ?></span>)</h2>
                <div class="flex gap-2 flex-wrap">
                    <a href="historias_clinicas.php" class="btn btn-success">➕ Nueva Historia</a>
                    <button onclick="exportarHistorias()" class="btn btn-export">📥 Exportar CSV</button>
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
                        <label for="search-input">Buscar en diagnóstico o motivo</label>
                        <div style="display: flex; gap: 10px;">
                            <input 
                                type="text" 
                                id="search-input"
                                placeholder="🔍 Cédula, nombre, diagnóstico..."
                                value="<?= htmlspecialchars($busqueda) ?>"
                                style="flex: 1;"
                            >
                            <button id="btn-buscar" class="btn btn-primary">Buscar</button>
                        </div>
                    </div>

                    <div class="filter-group">
                        <label for="filter-status">Filtrar por estado</label>
                        <select id="filter-status">
                            <option value="">Todas</option>
                            <option value="activa">● Activas</option>
                            <option value="cerrada">✓ Cerradas</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="page-size">Items por página</label>
                        <select id="page-size">
                            <option value="10">10</option>
                            <option value="25" selected>25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                </div>

                <div class="filter-row">
                    <div class="filter-group">
                        <label for="fecha-desde">Fecha desde</label>
                        <input type="date" id="fecha-desde">
                    </div>

                    <div class="filter-group">
                        <label for="fecha-hasta">Fecha hasta</label>
                        <input type="date" id="fecha-hasta">
                    </div>
                </div>

                <div class="filter-actions">
                    <button onclick="aplicarFiltros()" class="btn btn-primary">Aplicar Filtros</button>
                    <button onclick="limpiarFiltros()" class="btn btn-secondary">Limpiar Filtros</button>
                </div>
            </div>
        </div>

        <!-- Tabla de Historias -->
        <div class="card">
            <?php if (empty($historias)): ?>
                <div class="no-results">
                    <div class="no-results-icon">📋</div>
                    <p>No hay historias clínicas registradas en el sistema.</p>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table id="historias-table">
                        <thead>
                            <tr>
                                <th>ID Historia</th>
                                <th>ID Paciente</th>
                                <th>Fecha Ingreso</th>
                                <th>Motivo Consulta</th>
                                <th>Diagnóstico</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="table-body">
                            <?php foreach ($historias as $h): ?>
                                <tr>
                                    <td>
                                        <a href="ver_historia.php?id=<?= $h['id_historia'] ?>" style="color: var(--primary); text-decoration: none; font-weight: bold;">
                                            #<?= htmlspecialchars($h['id_historia']) ?>
                                        </a>
                                    </td>
                                    <td>
                                        <a href="ver_paciente.php?id=<?= $h['id_paciente'] ?>" class="badge badge-primary">
                                            <?php 
                                            if (isset($h['pacientes']) && is_array($h['pacientes'])) {
                                                echo htmlspecialchars(($h['pacientes']['primer_nombre'] ?? '') . ' ' . ($h['pacientes']['primer_apellido'] ?? ''));
                                            } else {
                                                echo 'Paciente #' . htmlspecialchars($h['id_paciente']);
                                            }
                                            ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php
                                        $fecha = new DateTime($h['fecha_ingreso']);
                                        echo $fecha->format('d/m/Y H:i');
                                        ?>
                                    </td>
                                    <td>
                                        <?php $motivoConsulta = $h['motivo_consulta'] ?? ''; ?>
                                        <span title="<?= htmlspecialchars($motivoConsulta) ?>">
                                            <?= htmlspecialchars(substr($motivoConsulta, 0, 60)) ?>
                                            <?= strlen($motivoConsulta) > 60 ? '...' : '' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($h['diagnostico'])): ?>
                                            <span title="<?= htmlspecialchars($h['diagnostico']) ?>">
                                                <?= htmlspecialchars(substr($h['diagnostico'], 0, 40)) ?>
                                                <?= strlen($h['diagnostico']) > 40 ? '...' : '' ?>
                                            </span>
                                        <?php else: ?>
                                            <span style="color: var(--gray-500);">Sin diagnóstico</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($h['fecha_egreso']): ?>
                                            <span class="badge badge-success">✓ Cerrada</span>
                                        <?php else: ?>
                                            <span class="badge badge-primary">● Activa</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="ver_historia.php?id=<?= $h['id_historia'] ?>" class="btn btn-sm btn-primary">
                                            📄 Ver
                                        </a>
                                        <a href="imprimir_historia.php?id=<?= $h['id_historia'] ?>" class="btn btn-sm btn-success" target="_blank" title="Imprimir">
                                            🖨️
                                        </a>
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
        // Datos de historias
        const historiasData = <?= json_encode($historias) ?>;
        let pagination = null;

        // Formatear fecha
        function formatFecha(fechaString) {
            const fecha = new Date(fechaString);
            return fecha.toLocaleDateString('es-ES', { 
                day: '2-digit', 
                month: '2-digit', 
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        // Inicializar paginación
        class HistoriaPagination extends Pagination {
            renderItems() {
                const tbody = document.getElementById('table-body');
                const items = this.currentItems;
                
                if (items.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="7" style="text-align: center;">No hay historias clínicas para mostrar</td></tr>';
                    return;
                }

                tbody.innerHTML = items.map(h => {
                    const motivoConsulta = h.motivo_consulta || '';
                    const motivoCorto = motivoConsulta.length > 60 
                        ? motivoConsulta.substring(0, 60) + '...' 
                        : motivoConsulta;
                    
                    const diagnostico = h.diagnostico || '';
                    const diagnosticoHTML = diagnostico 
                        ? (diagnostico.length > 40 
                            ? diagnostico.substring(0, 40) + '...' 
                            : diagnostico)
                        : '<span style="color: var(--gray-500);">Sin diagnóstico</span>';
                    
                    const estadoBadge = h.fecha_egreso 
                        ? '<span class="badge badge-success">✓ Cerrada</span>'
                        : '<span class="badge badge-primary">● Activa</span>';
                    
                    const nombrePaciente = h.pacientes 
                        ? ((h.pacientes.primer_nombre || '') + ' ' + (h.pacientes.primer_apellido || '')).trim()
                        : 'Paciente #' + h.id_paciente;

                    return `
                        <tr>
                            <td>
                                <a href="ver_historia.php?id=${h.id_historia}" style="color: var(--primary); text-decoration: none; font-weight: bold;">
                                    #${h.id_historia}
                                </a>
                            </td>
                            <td>
                                <a href="ver_paciente.php?id=${h.id_paciente}" class="badge badge-primary">
                                    ${nombrePaciente}
                                </a>
                            </td>
                            <td>${formatFecha(h.fecha_ingreso)}</td>
                            <td><span title="${motivoConsulta}">${motivoCorto || '<span style="color: var(--gray-500);">-</span>'}</span></td>
                            <td>${diagnosticoHTML}</td>
                            <td>${estadoBadge}</td>
                            <td>
                                <a href="ver_historia.php?id=${h.id_historia}" class="btn btn-sm btn-primary">
                                    📄 Ver
                                </a>
                                <a href="imprimir_historia.php?id=${h.id_historia}" class="btn btn-sm btn-success" target="_blank" title="Imprimir">
                                    🖨️
                                </a>
                            </td>
                        </tr>
                    `;
                }).join('');

                // Actualizar contador
                document.getElementById('total-count').textContent = this.filteredItems.length;
            }
        }

        // Inicializar al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            pagination = new HistoriaPagination(historiasData, 25, 'table-body', 'pagination-controls');
            pagination.render();

            // Event listener para cambio de tamaño de página
            document.getElementById('page-size').addEventListener('change', function() {
                pagination.setItemsPerPage(this.value);
            });

            // Event listener para búsqueda en tiempo real
            // Event listener para búsqueda con botón
            document.getElementById('btn-buscar').addEventListener('click', function() {
                realizarBusqueda(document.getElementById('search-input').value);
            });

            // Event listener para búsqueda en tiempo real (Enter)
            document.getElementById('search-input').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    realizarBusqueda(this.value);
                }
            });
            
            // Debounce opcional mantenido
            document.getElementById('search-input').addEventListener('input', function() {
                // Opcional: Búsqueda automática con debounce
                clearTimeout(searchTimeout);
                const val = this.value;
                if(val.length > 2 || val.length === 0) {
                    searchTimeout = setTimeout(() => realizarBusqueda(val), 800);
                }
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

        function realizarBusqueda(termino) {
            const url = new URL(window.location.href);
            if (termino && termino.trim().length > 0) {
                url.searchParams.set('buscar', termino.trim());
            } else {
                url.searchParams.delete('buscar');
            }
            window.location.href = url.toString();
        }

        // Reemplazar aplicarFiltros local con redirección si se desea soportar filtros server-side completos,
        // pero por ahora mantenemos el filtro local solo sobre los resultados devueltos si fuera necesario.
        // Dado el requerimiento de seguridad, el filtro 'estado' también debería ser server-side,
        // pero para cumplir "no listar automáticamente", el bloqueo inicial ya está hecho.
        
        function aplicarFiltros() {
            // El filtro actual solo funcionará sobre los resultados YA buscados.
            // Esto es aceptable. Si el usuario busca "Gripe", obtendrá 5 resultados.
            // Luego puede filtrar esos 5 por fecha/estado localmente.
            const status = document.getElementById('filter-status').value;
            const fechaDesde = document.getElementById('fecha-desde').value;
            const fechaHasta = document.getElementById('fecha-hasta').value;

            pagination.applyFilter(item => {
                let matches = true;

                // (Nota: El filtro de texto ya se hizo en servidor)

                // Filtro de estado
                if (status) {
                    if (status === 'activa') {
                        matches = matches && !item.fecha_egreso;
                    } else if (status === 'cerrada') {
                        matches = matches && item.fecha_egreso;
                    }
                }

                // Filtro de rango de fechas
                if (fechaDesde || fechaHasta) {
                    matches = matches && FilterUtils.byDateRange(item, fechaDesde, fechaHasta, 'fecha_ingreso');
                }

                return matches;
            });
        }

        function limpiarFiltros() {
            // Redirigir a limpio
            window.location.href = window.location.pathname;
        }


        function exportarHistorias() {
            const currentData = pagination.filteredItems.map(h => ({
                'ID Historia': h.id_historia,
                'ID Paciente': h.id_paciente,
                'Fecha Ingreso': h.fecha_ingreso,
                'Fecha Egreso': h.fecha_egreso || '',
                'Motivo Consulta': h.motivo_consulta,
                'Análisis Plan': h.analisis_plan || '',
                'Diagnóstico': h.diagnostico || '',
                'Tratamiento': h.tratamiento || '',
                'Observaciones': h.observaciones || '',
                'Estado': h.fecha_egreso ? 'Cerrada' : 'Activa'
            }));

            const filename = `historias_clinicas_${new Date().toISOString().split('T')[0]}.csv`;
            ExportUtils.toCSV(currentData, filename);
            UIUtils.showToast('Historias clínicas exportadas exitosamente', 'success');
        }
    </script>

    <style>
        td a.badge {
            text-decoration: none;
            transition: transform 0.2s;
            display: inline-block;
        }
        td a.badge:hover {
            transform: scale(1.05);
        }
    </style>
        </main>
    </div>
</body>
</html>
