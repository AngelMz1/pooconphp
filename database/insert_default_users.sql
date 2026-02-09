-- =====================================================
-- SCRIPT: INSERTAR USUARIOS INICIALES
-- =====================================================
-- Ejecutar este script en el nuevo equipo después de crear las tablas
-- Los usuarios tienen contraseña por defecto: admin123
-- ⚠️ IMPORTANTE: Cambiar las contraseñas después del primer login
-- =====================================================

-- Usuario 1: Administrador
INSERT INTO users (username, password_hash, full_name, email, role, active)
VALUES (
    'admin',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: admin123
    'Administrador del Sistema',
    'admin@medical.local',
    'admin',
    true
) ON CONFLICT (username) DO NOTHING;

-- Usuario 2: Médico de Prueba
INSERT INTO users (username, password_hash, full_name, email, role, active)
VALUES (
    'medico',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: admin123
    'Dr. Carlos Médico',
    'medico@medical.local',
    'medico',
    true
) ON CONFLICT (username) DO NOTHING;

-- Usuario 3: Cajero
INSERT INTO users (username, password_hash, full_name, email, role, active)
VALUES (
    'cajero',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: admin123
    'Cajero Principal',
    'cajero@medical.local',
    'cajero',
    true
) ON CONFLICT (username) DO NOTHING;

-- =====================================================
-- VERIFICACIÓN
-- =====================================================

-- Ver usuarios creados
SELECT id, username, full_name, role, active 
FROM users 
ORDER BY id;

-- =====================================================
-- CREDENCIALES POR DEFECTO
-- =====================================================

/*
USUARIO: admin
CONTRASEÑA: admin123
ROL: Administrador

USUARIO: medico
CONTRASEÑA: admin123
ROL: Médico

USUARIO: cajero
CONTRASEÑA: admin123
ROL: Cajero

⚠️ IMPORTANTE: Cambiar estas contraseñas inmediatamente después del primer acceso
*/
