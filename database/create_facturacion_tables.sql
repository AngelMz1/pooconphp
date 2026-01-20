-- ====================================================
-- SCRIPT SQL: Crear tablas de Facturación
-- Ejecutar en Supabase SQL Editor
-- ====================================================

-- Tabla FACTURAS (Cabecera)
CREATE TABLE IF NOT EXISTS facturas (
    id SERIAL PRIMARY KEY,
    paciente_id INTEGER NOT NULL REFERENCES pacientes(id_paciente) ON DELETE CASCADE,
    consulta_id INTEGER REFERENCES consultas(id_consulta),
    fecha TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    total DECIMAL(12, 2) NOT NULL DEFAULT 0,
    estado VARCHAR(20) DEFAULT 'pendiente' CHECK (estado IN ('pendiente', 'pagada', 'anulada')),
    observaciones TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Tabla FACTURA_ITEMS (Detalle)
CREATE TABLE IF NOT EXISTS factura_items (
    id SERIAL PRIMARY KEY,
    factura_id INTEGER NOT NULL REFERENCES facturas(id) ON DELETE CASCADE,
    tarifario_id INTEGER REFERENCES tarifarios(id),
    concepto VARCHAR(255) NOT NULL,
    cantidad INTEGER DEFAULT 1,
    precio_unitario DECIMAL(12, 2) NOT NULL,
    subtotal DECIMAL(12, 2) NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Tabla TARIFARIOS (si no existe)
CREATE TABLE IF NOT EXISTS tarifarios (
    id SERIAL PRIMARY KEY,
    codigo VARCHAR(20) UNIQUE,
    nombre_servicio VARCHAR(255) NOT NULL,
    descripcion TEXT,
    precio DECIMAL(12, 2) NOT NULL DEFAULT 0,
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Índices para mejorar rendimiento
CREATE INDEX IF NOT EXISTS idx_facturas_paciente ON facturas(paciente_id);
CREATE INDEX IF NOT EXISTS idx_facturas_fecha ON facturas(fecha);
CREATE INDEX IF NOT EXISTS idx_factura_items_factura ON factura_items(factura_id);

-- Datos de ejemplo para tarifarios
INSERT INTO tarifarios (codigo, nombre_servicio, descripcion, precio, activo)
VALUES 
    ('CONS001', 'Consulta General', 'Consulta médica general', 50000.00, true),
    ('CONS002', 'Consulta Especializada', 'Consulta con especialista', 80000.00, true),
    ('PROC001', 'Curacion Simple', 'Curación de heridas menores', 25000.00, true),
    ('PROC002', 'Inyectología', 'Aplicación de inyecciones', 15000.00, true),
    ('LAB001', 'Hemograma Completo', 'Examen de sangre completo', 35000.00, true),
    ('LAB002', 'Glicemia', 'Medición de glucosa en sangre', 20000.00, true),
    ('IMG001', 'Radiografía', 'Radiografía simple', 45000.00, true)
ON CONFLICT (codigo) DO NOTHING;

-- Habilitar RLS
ALTER TABLE facturas ENABLE ROW LEVEL SECURITY;
ALTER TABLE factura_items ENABLE ROW LEVEL SECURITY;
ALTER TABLE tarifarios ENABLE ROW LEVEL SECURITY;

-- Políticas para facturas (permitir acceso total por ahora)
CREATE POLICY "Allow all access to facturas" ON facturas FOR ALL USING (true) WITH CHECK (true);
CREATE POLICY "Allow all access to factura_items" ON factura_items FOR ALL USING (true) WITH CHECK (true);
CREATE POLICY "Allow all access to tarifarios" ON tarifarios FOR ALL USING (true) WITH CHECK (true);

-- ====================================================
-- FIN DEL SCRIPT
-- ====================================================
