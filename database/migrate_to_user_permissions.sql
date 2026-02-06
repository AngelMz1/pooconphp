-- ============================================
-- Migration: Role-Based to User-Only Permissions
-- ============================================
-- This script creates the user_permissions table
-- and migrates existing role permissions to users

-- Step 1: Create user_permissions table
-- ============================================
CREATE TABLE IF NOT EXISTS user_permissions (
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    permiso_codigo VARCHAR(50) NOT NULL REFERENCES permisos(codigo) ON DELETE CASCADE,
    granted_by BIGINT REFERENCES users(id),
    granted_at TIMESTAMP DEFAULT NOW(),
    notes TEXT,
    PRIMARY KEY (user_id, permiso_codigo)
);

-- Add indexes for performance
CREATE INDEX IF NOT EXISTS idx_user_permissions_user 
ON user_permissions(user_id);

CREATE INDEX IF NOT EXISTS idx_user_permissions_permission 
ON user_permissions(permiso_codigo);

-- Add comments
COMMENT ON TABLE user_permissions IS 'Individual permissions assigned to users. This is now the primary permission system.';
COMMENT ON COLUMN user_permissions.user_id IS 'User receiving the permission';
COMMENT ON COLUMN user_permissions.permiso_codigo IS 'Permission code being granted';
COMMENT ON COLUMN user_permissions.granted_by IS 'Admin user who granted this permission (audit trail)';
COMMENT ON COLUMN user_permissions.notes IS 'Optional notes about why this permission was granted';

-- Step 2: Migrate existing role permissions to user permissions
-- ============================================
-- For each user, copy all permissions from their role
INSERT INTO user_permissions (user_id, permiso_codigo, granted_by, notes)
SELECT 
    u.id,
    rp.permiso_codigo,
    1, -- System migration (admin user ID 1)
    'Migrated from role: ' || u.rol
FROM users u
CROSS JOIN rol_permisos rp
WHERE u.rol = rp.rol
ON CONFLICT (user_id, permiso_codigo) DO NOTHING;

-- Step 3: Mark old tables as deprecated
-- ============================================
COMMENT ON TABLE rol_permisos IS 'DEPRECATED - No longer used for access control. Kept for historical reference only. All permissions now managed via user_permissions table.';
COMMENT ON COLUMN users.rol IS 'Informational only (job title). Does NOT control permissions. See user_permissions table instead.';

-- Step 4: Verification queries
-- ============================================

-- Count permissions per user
SELECT 
    u.id,
    u.username,
    u.rol as job_title,
    COUNT(up.permiso_codigo) as permission_count
FROM users u
LEFT JOIN user_permissions up ON u.id = up.user_id
GROUP BY u.id, u.username, u.rol
ORDER BY u.id;

-- List all permissions for each user
SELECT 
    u.username,
    u.rol as job_title,
    p.codigo,
    p.nombre
FROM users u
JOIN user_permissions up ON u.id = up.user_id
JOIN permisos p ON up.permiso_codigo = p.codigo
ORDER BY u.username, p.categoria, p.codigo;

-- Check for users without permissions (should be none after migration)
SELECT 
    u.id,
    u.username,
    u.rol
FROM users u
LEFT JOIN user_permissions up ON u.id = up.user_id
WHERE up.user_id IS NULL;
