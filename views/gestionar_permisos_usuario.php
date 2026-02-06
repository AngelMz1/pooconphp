<?php
/**
 * Gestionar Permisos de Usuario
 * Interface for assigning permissions to individual users
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/auth_helper.php';

// Solo usuarios con permiso de gestionar usuarios pueden acceder
requirePermission('gestionar_usuarios');

use App\DatabaseFactory;

$supabase = DatabaseFactory::create();

// Get all users for selector
$users = $supabase->select('users', 'id,username,nombre_completo,rol', '');

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Permisos de Usuario</title>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .permissions-container {
            max-width: 1000px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .user-selector {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #007bff;
        }
        
        .user-selector select {
            padding: 10px;
            font-size: 16px;
            border: 1px solid #ddd;
            border-radius: 4px;
            min-width: 300px;
        }
        
        .user-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            margin: 15px 0;
        }
        
        .templates-section {
            margin: 20px 0;
            padding: 15px;
            background: #e3f2fd;
            border-radius: 4px;
        }
        
        .template-btn {
            margin: 5px;
            padding: 8px 15px;
            background: #2196F3;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .template-btn:hover {
            background: #1976D2;
        }
        
        .permissions-grid {
            display: grid;
            gap: 20px;
            margin-top: 20px;
        }
        
        .category-section {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
        }
        
        .category-header {
            font-weight: bold;
            font-size: 18px;
            color: #007bff;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 2px solid #007bff;
        }
        
        .permission-item {
            padding: 8px;
            margin: 5px 0;
        }
        
        .permission-item label {
            display: flex;
            align-items: center;
            cursor: pointer;
        }
        
        .permission-item input[type="checkbox"] {
            margin-right: 10px;
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        
        .permission-name {
            font-weight: 500;
        }
        
        .permission-code {
            color: #666;
            font-size: 13px;
            margin-left: 10px;
        }
        
        .action-buttons {
            margin-top: 30px;
            text-align: center;
            padding-top: 20px;
            border-top: 2px solid #ddd;
        }
        
        .btn-primary {
            padding: 12px 30px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            margin: 0 10px;
        }
        
        .btn-primary:hover {
            background: #218838;
        }
        
        .btn-secondary {
            padding: 12px 30px;
            background: #6c757d;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            margin: 0 10px;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .loading {
            text-align: center;
            padding: 20px;
            color: #666;
        }
        
        .message {
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .quick-actions {
            margin: 15px 0;
        }
        
        .quick-actions button {
            margin: 5px;
            padding: 6px 12px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="permissions-container">
            <h1>🔐 Gestionar Permisos de Usuario</h1>
            
            <div class="user-selector">
                <h3>Seleccionar Usuario:</h3>
                <select id="userSelect">
                    <option value="">-- Seleccione un usuario --</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?= $user['id'] ?>" 
                                data-username="<?= htmlspecialchars($user['username']) ?>"
                                data-name="<?= htmlspecialchars($user['nombre_completo']) ?>"
                                data-role="<?= htmlspecialchars($user['rol']) ?>">
                            <?= htmlspecialchars($user['nombre_completo']) ?> (<?= htmlspecialchars($user['username']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div id="messageContainer"></div>
            
            <div id="permissionsContent" style="display: none;">
                <div class="user-info">
                    <strong>Usuario:</strong> <span id="currentUserName"></span><br>
                    <strong>Cargo/Rol:</strong> <span id="currentUserRole"></span> (solo informativo, los permisos se asignan individualmente)
                </div>
                
                <div class="templates-section">
                    <h3>💡 Plantillas Rápidas:</h3>
                    <p>Aplicar un conjunto predefinido de permisos:</p>
                    <div id="templateButtons"></div>
                </div>
                
                <div class="quick-actions">
                    <button class="btn-secondary" onclick="selectAll()">✓ Seleccionar Todos</button>
                    <button class="btn-secondary" onclick="deselectAll()">✗ Quitar Todos</button>
                </div>
                
                <div id="permissionsGrid" class="permissions-grid"></div>
                
                <div class="action-buttons">
                    <button class="btn-primary"onclick="savePermissions()">
                        💾 Guardar Cambios
                    </button>
                    <button class="btn-secondary" onclick="cancelChanges()">
                        ❌ Cancelar
                    </button>
                </div>
            </div>
            
            <div id="loadingIndicator" class="loading" style="display: none;">
                Cargando permisos...
            </div>
        </div>
    </div>
    
    <script>
        let currentUserId = null;
        let allPermissions = [];
        let userPermissions = [];
        let templates = {};
        
        // Load user permissions when user is selected
        document.getElementById('userSelect').addEventListener('change', function() {
            const userId = this.value;
            if (!userId) {
                document.getElementById('permissionsContent').style.display = 'none';
                return;
            }
            
            const selectedOption = this.options[this.selectedIndex];
            currentUserId = userId;
            
            // Update user info display
            document.getElementById('currentUserName').textContent = selectedOption.dataset.name;
            document.getElementById('currentUserRole').textContent = selectedOption.dataset.role;
            
            loadUserPermissions(userId);
        });
        
        // Load permissions from API
        function loadUserPermissions(userId) {
            document.getElementById('loadingIndicator').style.display = 'block';
            document.getElementById('permissionsContent').style.display = 'none';
            
            fetch(`../api/get_user_permissions.php?user_id=${userId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        allPermissions = data.all_permissions;
                        userPermissions = data.user_permissions || [];
                        renderPermissions(data.permissions_by_category);
                        document.getElementById('permissionsContent').style.display = 'block';
                    } else {
                        showMessage(data.error || 'Error al cargar permisos', 'error');
                    }
                })
                .catch(error => {
                    showMessage('Error de conexión: ' + error.message, 'error');
                })
                .finally(() => {
                    document.getElementById('loadingIndicator').style.display = 'none';
                });
        }
        
        // Load templates
        fetch('../api/get_permission_templates.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    templates = data.templates;
                    renderTemplateButtons();
                }
            });
        
        function renderTemplateButtons() {
            const container = document.getElementById('templateButtons');
            container.innerHTML = '';
            
            for (const [key, template] of Object.entries(templates)) {
                const btn = document.createElement('button');
                btn.className = 'template-btn';
                btn.textContent = template.name;
                btn.title = template.description;
                btn.onclick = () => applyTemplate(template);
                container.appendChild(btn);
            }
        }
        
        function applyTemplate(template) {
            // Uncheck all first
            document.querySelectorAll('.permission-checkbox').forEach(cb => {
                cb.checked = false;
            });
            
            // Check template permissions
            template.permissions.forEach(permCode => {
                const checkbox = document.querySelector(`input[data-code="${permCode}"]`);
                if (checkbox) {
                    checkbox.checked = true;
                }
            });
            
            showMessage(`Plantilla "${template.name}" aplicada. Haz clic en Guardar para confirmar.`, 'success');
        }
        
        function renderPermissions(permsByCategory) {
            const grid = document.getElementById('permissionsGrid');
            grid.innerHTML = '';
            
            for (const [category, perms] of Object.entries(permsByCategory)) {
                const section = document.createElement('div');
                section.className = 'category-section';
                
                const header = document.createElement('div');
                header.className = 'category-header';
                header.textContent = category;
                section.appendChild(header);
                
                perms.forEach(perm => {
                    const item = document.createElement('div');
                    item.className = 'permission-item';
                    
                    const label = document.createElement('label');
                    
                    const checkbox = document.createElement('input');
                    checkbox.type = 'checkbox';
                    checkbox.className = 'permission-checkbox';
                    checkbox.dataset.code = perm.codigo;
                    checkbox.checked = userPermissions.includes(perm.codigo);
                    
                    const name = document.createElement('span');
                    name.className = 'permission-name';
                    name.textContent = perm.nombre;
                    
                    const code = document.createElement('span');
                    code.className = 'permission-code';
                    code.textContent = `(${perm.codigo})`;
                    
                    label.appendChild(checkbox);
                    label.appendChild(name);
                    label.appendChild(code);
                    item.appendChild(label);
                    section.appendChild(item);
                });
                
                grid.appendChild(section);
            }
        }
        
        function selectAll() {
            document.querySelectorAll('.permission-checkbox').forEach(cb => {
                cb.checked = true;
            });
        }
        
        function deselectAll() {
            if (confirm('¿Estás seguro de quitar todos los permisos?')) {
                document.querySelectorAll('.permission-checkbox').forEach(cb => {
                    cb.checked = false;
                });
            }
        }
        
        function savePermissions() {
            if (!currentUserId) {
                showMessage('No hay usuario seleccionado', 'error');
                return;
            }
            
            // Get selected permissions
            const selectedPerms = [];
            document.querySelectorAll('.permission-checkbox:checked').forEach(cb => {
                selectedPerms.push(cb.dataset.code);
            });
            
            if (selectedPerms.length === 0) {
                if (!confirm('¿Estás seguro de guardar SIN PERMISOS? El usuario no podrá acceder a ninguna función.')) {
                    return;
                }
            }
            
            // Save via API
            document.getElementById('loadingIndicator').style.display = 'block';
            
            fetch('../api/save_user_permissions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    user_id: currentUserId,
                    permissions: selectedPerms
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage(`✅ ${data.message} (${data.permissions_assigned} permisos asignados)`, 'success');
                    // Reload to reflect changes
                    setTimeout(() => {
                        loadUserPermissions(currentUserId);
                    }, 1500);
                } else {
                    showMessage('❌ ' + (data.error || 'Error al guardar'), 'error');
                }
            })
            .catch(error => {
                showMessage('❌ Error de conexión: ' + error.message, 'error');
            })
            .finally(() => {
                document.getElementById('loadingIndicator').style.display = 'none';
            });
        }
        
        function cancelChanges() {
            if(confirm('¿Descartar cambios y recargar?')) {
                loadUserPermissions(currentUserId);
            }
        }
        
        function showMessage(text, type) {
            const container = document.getElementById('messageContainer');
            container.innerHTML = `<div class="message ${type}">${text}</div>`;
            setTimeout(() => {
                container.innerHTML = '';
            }, 5000);
        }
    </script>
</body>
</html>
