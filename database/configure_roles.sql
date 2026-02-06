-- Script para configurar roles y permisos del sistema
-- Fecha: 2026-02-05
-- Propósito: Definir roles (admin, facturador, medico) con sus permisos correspondientes

-- 1. Actualizar constraint de roles en la tabla users
ALTER TABLE users DROP CONSTRAINT IF EXISTS users_rol_check;
ALTER TABLE users ADD CONSTRAINT users_rol_check 
    CHECK (rol IN ('admin', 'facturador', 'medico'));

-- 2. Actualizar roles existentes de 'cajero' a 'facturador'
UPDATE users SET rol = 'facturador' WHERE rol = 'cajero';

-- 3. Crear tabla de permisos si no existe
CREATE TABLE IF NOT EXISTS permisos (
    id SERIAL PRIMARY KEY,
    codigo VARCHAR(50) UNIQUE NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    categoria VARCHAR(50)
);

-- 4. Crear tabla de asignación rol-permisos
CREATE TABLE IF NOT EXISTS rol_permisos (
    id SERIAL PRIMARY KEY,
    rol VARCHAR(20) NOT NULL,
    permiso_codigo VARCHAR(50) REFERENCES permisos(codigo),
    UNIQUE(rol, permiso_codigo)
);

-- 5. Insertar permisos del sistema
INSERT INTO permisos (codigo, nombre, descripcion, categoria) VALUES
-- Gestión de Pacientes
('gestionar_pacientes', 'Gestionar Pacientes', 'Crear, editar y eliminar pacientes', 'Pacientes'),
('ver_pacientes', 'Ver Pacientes', 'Ver información de pacientes', 'Pacientes'),

-- Gestión de Citas
('agendar_citas', 'Agendar Citas', 'Crear nuevas citas para pacientes', 'Citas'),
('confirmar_citas', 'Confirmar Citas', 'Confirmar citas pendientes', 'Citas'),
('cancelar_citas', 'Cancelar Citas', 'Cancelar citas existentes', 'Citas'),
('reagendar_citas', 'Reagendar Citas', 'Modificar fechas y horarios de citas', 'Citas'),
('ver_todas_citas', 'Ver Todas las Citas', 'Ver calendario completo de citas', 'Citas'),

-- Consultas Médicas
('atender_consulta', 'Atender Consulta', 'Atender consultas médicas', 'Consultas'),
('crear_historia', 'Crear Historia Clínica', 'Crear historias clínicas', 'Consultas'),
('ver_historia', 'Ver Historias Clínicas', 'Consultar historias clínicas', 'Consultas'),
('prescribir_medicamentos', 'Prescribir Medicamentos', 'Crear fórmulas médicas', 'Consultas'),
('solicitar_procedimientos', 'Solicitar Procedimientos', 'Ordenar procedimientos CUPS', 'Consultas'),

-- Facturación
('generar_factura', 'Generar Factura', 'Generar facturas desde consultas', 'Facturación'),
('registrar_pago', 'Registrar Pago', 'Registrar pagos de facturas', 'Facturación'),
('ver_facturas', 'Ver Facturas', 'Consultar facturas del sistema', 'Facturación'),
('anular_factura', 'Anular Factura', 'Anular facturas', 'Facturación'),
('administrar_tarifarios', 'Administrar Tarifarios', 'Gestionar precios y tarifas', 'Facturación'),

-- Administración
('gestionar_usuarios', 'Gestionar Usuarios', 'Crear y editar usuarios del sistema', 'Administración'),
('gestionar_medicos', 'Gestionar Médicos', 'Administrar perfiles de médicos', 'Administración'),
('configurar_sistema', 'Configurar Sistema', 'Acceso a configuraciones del sistema', 'Administración'),
('ver_reportes', 'Ver Reportes', 'Acceder a reportes y estadísticas', 'Administración')
ON CONFLICT (codigo) DO NOTHING;

-- 6. Asignar permisos a cada rol
-- Limpiar asignaciones existentes
DELETE FROM rol_permisos;

-- ADMIN: Todos los permisos
INSERT INTO rol_permisos (rol, permiso_codigo)
SELECT 'admin', codigo FROM permisos;

-- FACTURADOR: Gestión de citas, consulta de historias, ver facturas
INSERT INTO rol_permisos (rol, permiso_codigo) VALUES
-- Pacientes
('facturador', 'gestionar_pacientes'),
('facturador', 'ver_pacientes'),
-- Citas (todas las funciones)
('facturador', 'agendar_citas'),
('facturador', 'confirmar_citas'),
('facturador', 'cancelar_citas'),
('facturador', 'reagendar_citas'),
('facturador', 'ver_todas_citas'),
-- Historias (solo consulta)
('facturador', 'ver_historia'),
-- Facturación
('facturador', 'ver_facturas'),
('facturador', 'registrar_pago');

-- MEDICO: Atención médica, consultas, facturación básica
INSERT INTO rol_permisos (rol, permiso_codigo) VALUES
-- Pacientes
('medico', 'ver_pacientes'),
-- Citas
('medico', 'ver_todas_citas'),
-- Consultas (todas las funciones médicas)
('medico', 'atender_consulta'),
('medico', 'crear_historia'),
('medico', 'ver_historia'),
('medico', 'prescribir_medicamentos'),
('medico', 'solicitar_procedimientos'),
-- Facturación
('medico', 'generar_factura'),
('medico', 'ver_facturas'),
('medico', 'registrar_pago');

-- 7. Actualizar estados de consulta permitidos
ALTER TABLE consultas DROP CONSTRAINT IF EXISTS consultas_estado_check;
ALTER TABLE consultas ADD CONSTRAINT consultas_estado_check 
    CHECK (estado IN ('pendiente', 'confirmada', 'en_curso', 'atendida', 'cancelada'));

-- 8. Crear usuarios de prueba para cada rol (si no existen)

-- Usuario Admin (ya existe por defecto)
-- Username: admin / Password: admin123

-- Usuario Facturador
INSERT INTO users (username, password_hash, nombre_completo, rol, active) 
VALUES (
    'facturador', 
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- admin123
    'Facturador de Prueba', 
    'facturador',
    true
) ON CONFLICT (username) DO UPDATE 
  SET rol = 'facturador', password_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

-- Usuario Médico (actualizar si existe como medico1 o medico2)
INSERT INTO users (username, password_hash, nombre_completo, rol, active) 
VALUES (
    'medico', 
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- admin123
    'Médico de Prueba', 
    'medico',
    true
) ON CONFLICT (username) DO UPDATE 
  SET rol = 'medico', password_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

-- También actualizar medico2 si existe
UPDATE users SET 
    rol = 'medico',
    password_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
WHERE username = 'medico2';

-- 9. Crear función helper para verificar permisos
CREATE OR REPLACE FUNCTION tiene_permiso(p_user_id BIGINT, p_permiso_codigo VARCHAR)
RETURNS BOOLEAN AS $$
DECLARE
    v_rol VARCHAR(20);
    v_tiene_permiso BOOLEAN;
BEGIN
    -- Obtener rol del usuario
    SELECT rol INTO v_rol FROM users WHERE id = p_user_id AND active = true;
    
    IF v_rol IS NULL THEN
        RETURN FALSE;
    END IF;
    
    -- Verificar si el rol tiene el permiso
    SELECT EXISTS(
        SELECT 1 FROM rol_permisos 
        WHERE rol = v_rol AND permiso_codigo = p_permiso_codigo
    ) INTO v_tiene_permiso;
    
    RETURN v_tiene_permiso;
END;
$$ LANGUAGE plpgsql;

-- 10. Crear vista para facilitar consultas de permisos
CREATE OR REPLACE VIEW v_usuarios_permisos AS
SELECT 
    u.id as user_id,
    u.username,
    u.nombre_completo,
    u.rol,
    p.codigo as permiso_codigo,
    p.nombre as permiso_nombre,
    p.categoria
FROM users u
CROSS JOIN permisos p
WHERE EXISTS (
    SELECT 1 FROM rol_permisos rp 
    WHERE rp.rol = u.rol AND rp.permiso_codigo = p.codigo
)
AND u.active = true;

-- Verificación: Mostrar roles y permisos asignados
SELECT 
    rol,
    COUNT(*) as total_permisos,
    STRING_AGG(permiso_codigo, ', ') as permisos
FROM rol_permisos
GROUP BY rol
ORDER BY rol;

COMMIT;
