-- Reestructuración de tabla CIE10
-- Primero eliminamos la tabla anterior que tenía estructura compleja/sucia
DROP TABLE IF EXISTS cie10 CASCADE;

-- Crear tabla limpia y optimizada
CREATE TABLE cie10 (
    id SERIAL PRIMARY KEY,
    codigo VARCHAR(20) UNIQUE NOT NULL,
    descripcion TEXT NOT NULL,
    activo BOOLEAN DEFAULT true,
    sexo_aplicable VARCHAR(10), -- A, M, F
    edad_minima VARCHAR(10),    -- Guardamos como texto para flexibilidad '001', '060' etc
    edad_maxima VARCHAR(10),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Índices
CREATE INDEX idx_cie10_codigo ON cie10(codigo);
CREATE INDEX idx_cie10_descripcion ON cie10(descripcion);

-- RLS
ALTER TABLE cie10 ENABLE ROW LEVEL SECURITY;
CREATE POLICY "Permitir lectura publica cie10" ON cie10 FOR SELECT USING (true);
CREATE POLICY "Permitir insertar autenticado" ON cie10 FOR INSERT WITH CHECK (true);
