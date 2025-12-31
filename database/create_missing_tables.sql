-- Tabla de Tarifarios
CREATE TABLE IF NOT EXISTS tarifarios (
    id SERIAL PRIMARY KEY,
    codigo VARCHAR(50) NOT NULL UNIQUE,
    nombre_servicio VARCHAR(255) NOT NULL,
    precio DECIMAL(10, 2) NOT NULL DEFAULT 0,
    descripcion TEXT NULL,
    activo BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- Tabla de Configuración
CREATE TABLE IF NOT EXISTS configuracion (
    id SERIAL PRIMARY KEY,
    nombre_institucion VARCHAR(255) DEFAULT 'Mi Centro Médico',
    color_principal VARCHAR(50) DEFAULT '#0d6efd',
    color_secundario VARCHAR(50) DEFAULT '#6c757d',
    logo_url TEXT NULL,
    updated_at TIMESTAMP DEFAULT NOW()
);

-- Habilitar RLS
ALTER TABLE tarifarios ENABLE ROW LEVEL SECURITY;
ALTER TABLE configuracion ENABLE ROW LEVEL SECURITY;

CREATE POLICY "Permitir todo tarifarios" ON tarifarios FOR ALL USING (true);
CREATE POLICY "Permitir todo configuracion" ON configuracion FOR ALL USING (true);

-- Insertar configuración inicial si no existe
INSERT INTO configuracion (id, nombre_institucion, color_principal, color_secundario)
VALUES (1, 'Mi Centro Médico', '#0d6efd', '#6c757d')
ON CONFLICT (id) DO NOTHING;

-- Insertar algunos servicios de prueba en el tarifario
INSERT INTO tarifarios (codigo, nombre_servicio, precio, descripcion) VALUES
('CONS-GEN', 'Consulta Medicina General', 50000, 'Consulta de valoración general'),
('CONS-ESP', 'Consulta Especialista', 80000, 'Consulta con especialista'),
('URG-TRI', 'Triage Urgencias', 30000, 'Clasificación de pacientes'),
('PROC-001', 'Curación Menor', 25000, 'Curación de heridas superficiales'),
('PROC-002', 'Sutura Simple', 45000, 'Sutura de herida menor a 5cm')
ON CONFLICT (codigo) DO NOTHING;
