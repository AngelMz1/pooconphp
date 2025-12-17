-- Script SQL para crear las tablas necesarias en Supabase
-- Ejecutar en el SQL Editor de Supabase

-- Tabla de pacientes
CREATE TABLE IF NOT EXISTS pacientes (
    id_paciente SERIAL PRIMARY KEY,
    documento_id VARCHAR(20) UNIQUE NOT NULL,
    primer_nombre VARCHAR(50) NOT NULL,
    segundo_nombre VARCHAR(50),
    primer_apellido VARCHAR(50) NOT NULL,
    segundo_apellido VARCHAR(50),
    fecha_nacimiento DATE,
    telefono VARCHAR(15),
    email VARCHAR(100),
    direccion TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Tabla de historias clínicas
CREATE TABLE IF NOT EXISTS historias_clinicas (
    id_historia SERIAL PRIMARY KEY,
    id_paciente INTEGER REFERENCES pacientes(id_paciente) ON DELETE CASCADE,
    fecha_ingreso TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    fecha_egreso TIMESTAMP WITH TIME ZONE,
    motivo_consulta TEXT,
    analisis_plan TEXT,
    diagnostico TEXT,
    tratamiento TEXT,
    observaciones TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Insertar datos de prueba
INSERT INTO pacientes (documento_id, primer_nombre, primer_apellido, fecha_nacimiento, telefono, email) 
VALUES 
    ('1000000246', 'Juan', 'Pérez', '1990-05-15', '3001234567', 'juan.perez@email.com'),
    ('1000000247', 'María', 'González', '1985-08-22', '3007654321', 'maria.gonzalez@email.com'),
    ('1000000248', 'Carlos', 'Rodríguez', '1992-12-10', '3009876543', 'carlos.rodriguez@email.com')
ON CONFLICT (documento_id) DO NOTHING;

-- Insertar historias clínicas de prueba
INSERT INTO historias_clinicas (id_paciente, motivo_consulta, analisis_plan, diagnostico)
SELECT 
    p.id_paciente,
    'Consulta de control general',
    'Paciente en buen estado general, se recomienda seguimiento',
    'Estado de salud normal'
FROM pacientes p 
WHERE p.documento_id = '1000000246'
ON CONFLICT DO NOTHING;

-- Habilitar RLS (Row Level Security) si es necesario
ALTER TABLE pacientes ENABLE ROW LEVEL SECURITY;
ALTER TABLE historias_clinicas ENABLE ROW LEVEL SECURITY;

-- Crear políticas básicas (permitir todo para usuarios autenticados)
CREATE POLICY "Permitir todo en pacientes" ON pacientes FOR ALL USING (true);
CREATE POLICY "Permitir todo en historias" ON historias_clinicas FOR ALL USING (true);