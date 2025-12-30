-- Tabla de Configuración del Sistema
CREATE TABLE IF NOT EXISTS configuracion (
    id SERIAL PRIMARY KEY,
    nombre_institucion VARCHAR(255) DEFAULT 'Mi Centro Médico',
    color_principal VARCHAR(7) DEFAULT '#0d6efd', -- Bootstrap Primary default
    color_secundario VARCHAR(7) DEFAULT '#6c757d', -- Bootstrap Secondary default
    logo_url TEXT,
    updated_at TIMESTAMP DEFAULT NOW()
);

-- Insertar configuración por defecto si no existe
INSERT INTO configuracion (id, nombre_institucion, color_principal, color_secundario)
SELECT 1, 'Mi Centro Médico', '#0d6efd', '#6c757d'
WHERE NOT EXISTS (SELECT 1 FROM configuracion WHERE id = 1);

-- Tabla de Tarifarios (Servicios/Procedimientos facturables)
CREATE TABLE IF NOT EXISTS tarifarios (
    id SERIAL PRIMARY KEY,
    codigo VARCHAR(50) UNIQUE NOT NULL,
    nombre_servicio VARCHAR(255) NOT NULL,
    precio DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    descripcion TEXT,
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT NOW()
);

-- Tabla de Facturas
CREATE TABLE IF NOT EXISTS facturas (
    id SERIAL PRIMARY KEY,
    paciente_id INTEGER REFERENCES pacientes(id_paciente),
    consulta_id INTEGER REFERENCES consultas(id_consulta), -- Opcional, si viene de una consulta
    fecha TIMESTAMP DEFAULT NOW(),
    total DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    estado VARCHAR(20) DEFAULT 'pendiente', -- pendiente, pagada, anulada
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT NOW()
);

-- Tabla de Items de Factura
CREATE TABLE IF NOT EXISTS factura_items (
    id SERIAL PRIMARY KEY,
    factura_id INTEGER REFERENCES facturas(id) ON DELETE CASCADE,
    tarifario_id INTEGER REFERENCES tarifarios(id),
    concepto VARCHAR(255), -- Puede ser copiado del tarifario o personalizado
    cantidad INTEGER DEFAULT 1,
    precio_unitario DECIMAL(10, 2) NOT NULL,
    subtotal DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT NOW()
);

-- Índices
CREATE INDEX IF NOT EXISTS idx_facturas_paciente ON facturas(paciente_id);
CREATE INDEX IF NOT EXISTS idx_factura_items_factura ON factura_items(factura_id);
