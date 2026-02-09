-- ============================================
-- DATOS DE REFERENCIA
-- Tipos de documento, EPS, ciudades, etc.
-- ============================================

-- Tabla de Tipos de Documento
CREATE TABLE IF NOT EXISTS tipo_documento (
    id SERIAL PRIMARY KEY,
    codigo VARCHAR(10) UNIQUE NOT NULL,
    descripcion VARCHAR(100) NOT NULL
);

-- Insertar tipos de documento colombianos
INSERT INTO tipo_documento (codigo, descripcion) VALUES
('CC', 'Cédula de Ciudadanía'),
('TI', 'Tarjeta de Identidad'),
('RC', 'Registro Civil'),
('CE', 'Cédula de Extranjería'),
('PA', 'Pasaporte'),
('MS', 'Menor Sin Identificación'),
('AS', 'Adulto Sin Identificación')
ON CONFLICT (codigo) DO NOTHING;

-- Tabla de EPS
CREATE TABLE IF NOT EXISTS eps (
    id SERIAL PRIMARY KEY,
    codigo VARCHAR(20) UNIQUE NOT NULL,
    nombre VARCHAR(200) NOT NULL,
    activa BOOLEAN DEFAULT true
);

-- Insertar EPS principales de Colombia
INSERT INTO eps (codigo, nombre) VALUES
('EPS001', 'Nueva EPS'),
('EPS002', 'Sura EPS'),
('EPS003', 'Sanitas EPS'),
('EPS004', 'Salud Total'),
('EPS005', 'Compensar'),
('EPS006', 'Famisanar'),
('EPS007', 'Aliansalud'),
('EPS008', 'Coosalud'),
('EPS009', 'Mutual Ser'),
('EPS010', 'Cafesalud'),
('EPS011', 'Comfenalco'),
('EPS012', 'Comfandi'),
('EPS013', 'Cruz Blanca'),
('EPS014', 'Medimás'),
('EPS015', 'Capital Salud'),
('EPS016', 'Coomeva'),
('EPS017', 'Emssanar'),
('PART', 'Particular'),
('OTRO', 'Otra EPS')
ON CONFLICT (codigo) DO NOTHING;

-- Comentarios
COMMENT ON TABLE tipo_documento IS 'Tipos de documentos de identificación en Colombia';
COMMENT ON TABLE eps IS 'Entidades Promotoras de Salud en Colombia';
