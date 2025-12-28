<?php
// Header partial
?>
<header class="top-nav">
    <div class="nav-brand">
        <button class="mobile-menu-toggle" id="menu-toggle">
            ☰
        </button>
        <span>Sistema de Gestión Médica</span>
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
        <button id="theme-toggle" class="btn btn-sm btn-secondary" style="margin-right: 1rem;" title="Cambiar Tema">
            <span id="theme-icon">🌙</span>
        </button>

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
            const toggleBtn = document.getElementById('theme-toggle');
            const themeIcon = document.getElementById('theme-icon');
            const html = document.documentElement;

            // Set initial icon
            if (html.getAttribute('data-theme') === 'dark') {
                themeIcon.textContent = '☀️';
                toggleBtn.classList.replace('btn-secondary', 'btn-outline'); 
            }

            toggleBtn.addEventListener('click', () => {
                const currentTheme = html.getAttribute('data-theme');
                const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                
                html.setAttribute('data-theme', newTheme);
                localStorage.setItem('theme', newTheme);
                
                // Update icon
                if (newTheme === 'dark') {
                    themeIcon.textContent = '☀️';
                    toggleBtn.classList.replace('btn-secondary', 'btn-outline');
                } else {
                    themeIcon.textContent = '🌙';
                    toggleBtn.classList.replace('btn-outline', 'btn-secondary');
                }
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
