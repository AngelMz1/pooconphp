<?php
// Header partial
?>
<header class="top-nav">
    <?php
    // Fetch Configuration dynamically
    require_once __DIR__ . '/../src/Configuracion.php';
    use App\Configuracion;
    
    // Default config fallback
    $sysConfig = [
        'nombre_institucion' => 'Mi Centro Médico',
        'color_principal' => '#0d6efd',
        'color_secundario' => '#6c757d',
        'logo_url' => ''
    ];
    
    try {
        $configModel = new Configuracion(); 
        // Note: SupabaseClient is initialized inside Configuracion constructor if not passed, 
        // provided environment variables are loaded.
        $dbConfig = $configModel->obtenerConfiguracion();
        if ($dbConfig) {
            $sysConfig = array_merge($sysConfig, $dbConfig);
        }
    } catch (Exception $e) {
        // Fallback silently if config fails to load
    }
    ?>
    
    <!-- Dynamic Branding Styles -->
    <style>
        :root {
            --primary: <?php echo $sysConfig['color_principal']; ?>;
            /* Simple darkening for primary-dark, usually handled by HSL but we'll stick to primary for now or use calc */
            /* Ideally we'd convert hex to HSL to keep the shading logic, but for MVP direct override is okay. */
            
            --secondary: <?php echo $sysConfig['color_secundario']; ?>;
        }
        
        /* Toggle Switch Styles */
        .theme-switch-wrapper {
            display: flex;
            align-items: center;
            margin-right: 1rem;
        }
        .theme-switch {
            display: inline-block;
            height: 28px; /* Slightly lowered height */
            position: relative;
            width: 52px; /* Slightly reduced width */
        }
        .theme-switch input {
            display: none;
        }
        .slider {
            background-color: #ccc;
            bottom: 0;
            cursor: pointer;
            left: 0;
            position: absolute;
            right: 0;
            top: 0;
            transition: .4s;
            border-radius: 34px;
        }
        .slider:before {
            background-color: #fff;
            bottom: 4px;
            content: "";
            height: 20px;
            left: 4px;
            position: absolute;
            transition: .4s;
            width: 20px;
            border-radius: 50%;
        }
        input:checked + .slider {
            background-color: var(--primary);
        }
        input:checked + .slider:before {
            transform: translateX(24px);
        }
        .slider .icon {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            font-size: 14px;
            z-index: 1;
        }
        .slider .sun { left: 6px; color: #f39c12; opacity: 1; transition: .4s; }
        .slider .moon { right: 6px; color: #f1c40f; opacity: 0; transition: .4s; }
        
        input:checked + .slider .sun { opacity: 0; }
        input:checked + .slider .moon { opacity: 1; }
    </style>

    <div class="nav-brand">
        <button class="mobile-menu-toggle" id="menu-toggle">
            ☰
        </button>
        <?php if (!empty($sysConfig['logo_url'])): ?>
            <img src="<?php echo htmlspecialchars($sysConfig['logo_url']); ?>" alt="Logo" style="height: 30px; margin-right: 10px;">
        <?php endif; ?>
        <span><?php echo htmlspecialchars($sysConfig['nombre_institucion']); ?></span>
    </div>

    <!-- Sidebar Overlay for Mobile -->
    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <!-- Theme Toggle Initial Script -->
    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>
    
    <div class="user-profile">
        <div class="theme-switch-wrapper">
             <label class="theme-switch" for="checkbox-theme">
                <input type="checkbox" id="checkbox-theme" />
                <div class="slider round">
                    <span class="icon sun">☀️</span>
                    <span class="icon moon">🌙</span>
                </div>
            </label>
        </div>

        <div class="user-info text-right">
            <span class="user-name">
                <?= htmlspecialchars($_SESSION['user_name'] ?? 'Usuario') ?>
            </span>
            <span class="user-role">
                <?= ucfirst(htmlspecialchars($_SESSION['user_role'] ?? 'Invitado')) ?>
            </span>
        </div>
        <div class="user-avatar">
            <?= strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)) ?>
        </div>
        
        <a href="<?= $basePath ?>views/logout.php" class="btn btn-sm btn-danger" style="margin-left: 1rem; padding: 0.25rem 0.5rem;" title="Cerrar Sesión">
            🚪
        </a>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Theme Toggle Logic
            const toggleCheckbox = document.getElementById('checkbox-theme');
            const html = document.documentElement;
            
            // Set initial state
            if (html.getAttribute('data-theme') === 'dark') {
                toggleCheckbox.checked = true;
            }

            toggleCheckbox.addEventListener('change', function() {
                const newTheme = this.checked ? 'dark' : 'light';
                
                html.setAttribute('data-theme', newTheme);
                localStorage.setItem('theme', newTheme);
            });

            // Mobile Menu Toggle Logic
            const menuToggle = document.getElementById('menu-toggle');
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.getElementById('sidebar-overlay');

            function toggleMenu() {
                sidebar.classList.toggle('open');
                overlay.classList.toggle('active');
            }

            if (menuToggle) {
                menuToggle.addEventListener('click', toggleMenu);
            }
            
            if (overlay) {
                overlay.addEventListener('click', toggleMenu);
            }
        });
    </script>
</header>

