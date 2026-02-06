-- ====================================================
-- MIGRACIONES: Sistema de Facturación Médica
-- Mejoras a tablas existentes y nuevas tablas
-- ====================================================

-- 1. Agregar campos a tabla FACTURAS
ALTER TABLE facturas ADD COLUMN IF NOT EXISTS metodo_pago VARCHAR(50);
ALTER TABLE facturas ADD COLUMN IF NOT EXISTS referencia_pago VARCHAR(100);
ALTER TABLE facturas ADD COLUMN IF NOT EXISTS fecha_pago TIMESTAMP;
ALTER TABLE facturas ADD COLUMN IF NOT EXISTS usuario_cajero_id INTEGER REFERENCES users(id);
ALTER TABLE facturas ADD COLUMN IF NOT EXISTS descuento DECIMAL(12,2) DEFAULT 0;
ALTER TABLE facturas ADD COLUMN IF NOT EXISTS copago DECIMAL(12,2) DEFAULT 0;
ALTER TABLE facturas ADD COLUMN IF NOT EXISTS subtotal DECIMAL(12,2) DEFAULT 0;
ALTER TABLE facturas ADD COLUMN IF NOT EXISTS notas_internas TEXT;

-- 2. Mejorar tabla TARIFARIOS para soportar múltiples precios
ALTER TABLE tarifarios ADD COLUMN IF NOT EXISTS tipo_tarifa VARCHAR(20) DEFAULT 'general';
-- Valores: 'general', 'eps', 'particular', 'subsidiado'

ALTER TABLE tarifarios ADD COLUMN IF NOT EXISTS eps_id INTEGER REFERENCES eps(id);
-- NULL = aplica a todos, específico = solo para esa EPS

ALTER TABLE tarifarios ADD COLUMN IF NOT EXISTS porcentaje_copago DECIMAL(5,2) DEFAULT 0;
-- Ej: 20.00 = paciente paga 20% del total

ALTER TABLE tarifarios ADD COLUMN IF NOT EXISTS cups_codigo VARCHAR(20);
-- Referencia al código CUPS si el servicio es un procedimiento

-- 3. Crear tabla PAGOS (auditoría completa de pagos)
CREATE TABLE IF NOT EXISTS pagos (
    id SERIAL PRIMARY KEY,
    factura_id INTEGER NOT NULL REFERENCES facturas(id) ON DELETE CASCADE,
    monto DECIMAL(12, 2) NOT NULL,
    metodo_pago VARCHAR(50) NOT NULL, 
    -- Efectivo, Tarjeta Débito, Tarjeta Crédito, Transferencia, Datafono, Nequi, etc.
    referencia VARCHAR(100), 
    -- Número de transacción bancaria, voucher, etc.
    fecha_pago TIMESTAMP DEFAULT NOW(),
    usuario_id INTEGER REFERENCES users(id), 
    -- Quien registró el pago (cajero)
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT NOW()
);

-- Índices para performance
CREATE INDEX IF NOT EXISTS idx_pagos_factura ON pagos(factura_id);
CREATE INDEX IF NOT EXISTS idx_pagos_fecha ON pagos(fecha_pago);
CREATE INDEX IF NOT EXISTS idx_tarifarios_cups ON tarifarios(cups_codigo);
CREATE INDEX IF NOT EXISTS idx_facturas_estado ON facturas(estado);
CREATE INDEX IF NOT EXISTS idx_facturas_fecha_pago ON facturas(fecha_pago);

-- 4. Habilitar RLS en tabla pagos
ALTER TABLE pagos ENABLE ROW LEVEL SECURITY;
CREATE POLICY "Allow all access to pagos" ON pagos FOR ALL USING (true) WITH CHECK (true);

-- 5. Insertar tarifarios adicionales (procedimientos comunes)
INSERT INTO tarifarios (codigo, nombre_servicio, descripcion, precio, activo, cups_codigo) VALUES
    ('PROC003', 'Sutura Simple', 'Sutura de herida simple', 45000.00, true, '180101'),
    ('PROC004', 'Nebulización', 'Tratamiento respiratorio', 18000.00, true, NULL),
    ('PROC005', 'Electrocardiograma', 'EKG de reposo', 35000.00, true, '890201'),
    ('URG001', 'Consulta de Urgencias', 'Atención de urgencias', 65000.00, true, NULL),
    ('DOM001', 'Consulta Domiciliaria', 'Atención médica a domicilio', 90000.00, true, NULL)
ON CONFLICT (codigo) DO NOTHING;

-- 6. Configurar tarifas diferenciadas por EPS (ejemplos)
-- Sura: ID 2 (asumiendo orden de inserción del setup_reference_data.sql)
INSERT INTO tarifarios (codigo, nombre_servicio, precio, activo, tipo_tarifa, eps_id, porcentaje_copago) VALUES
    ('CONS001-SURA', 'Consulta General - Sura', 45000.00, true, 'eps', 2, 20.00),
    ('CONS002-SURA', 'Consulta Especializada - Sura', 75000.00, true, 'eps', 2, 20.00)
ON CONFLICT DO NOTHING;

-- Particular (sin EPS)
INSERT INTO tarifarios (codigo, nombre_servicio, precio, activo, tipo_tarifa, eps_id, porcentaje_copago) VALUES
    ('CONS001-PART', 'Consulta General - Particular', 60000.00, true, 'particular', NULL, 0),
    ('CONS002-PART', 'Consulta Especializada - Particular', 100000.00, true, 'particular', NULL, 0)
ON CONFLICT DO NOTHING;

-- 7. Función auxiliar para calcular copago
CREATE OR REPLACE FUNCTION calcular_copago(
    p_precio DECIMAL, 
    p_porcentaje DECIMAL
) RETURNS DECIMAL AS $$
BEGIN
    RETURN ROUND(p_precio * (p_porcentaje / 100.0), 2);
END;
$$ LANGUAGE plpgsql IMMUTABLE;

-- ====================================================
-- FIN DE MIGRACIONES
-- ====================================================
