-- Tabla de Permisos
CREATE TABLE IF NOT EXISTS permissions (
    id SERIAL PRIMARY KEY,
    name VARCHAR(50) UNIQUE NOT NULL,
    description TEXT
);

-- Tabla Pivote usuarios-permisos
CREATE TABLE IF NOT EXISTS user_permissions (
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    permission_id INTEGER REFERENCES permissions(id) ON DELETE CASCADE,
    PRIMARY KEY (user_id, permission_id)
);

-- Habilitar RLS
ALTER TABLE permissions ENABLE ROW LEVEL SECURITY;
ALTER TABLE user_permissions ENABLE ROW LEVEL SECURITY;

CREATE POLICY "Public read permissions" ON permissions FOR SELECT USING (true);
CREATE POLICY "Public read user_permissions" ON user_permissions FOR SELECT USING (true);
CREATE POLICY "Admin write permissions" ON permissions FOR ALL USING (auth.role() = 'service_role'); -- Simplificado

-- Insertar Permisos Básicos
INSERT INTO permissions (name, description) VALUES 
('gestion_usuarios', 'Crear, editar y eliminar usuarios'),
('gestion_pacientes', 'Ver, crear y editar pacientes'),
('agendar_citas', 'Crear nuevas citas en el sistema'),
('confirmar_citas', 'Confirmar citas pendientes para que pasen a agenda médica'),
('atender_consulta', 'Realizar consultas médicas y ver historias clínicas'),
('ver_calendario', 'Ver el calendario de citas médicas'),
('ver_reportes', 'Ver reportes y estadísticas')
ON CONFLICT (name) DO NOTHING;
