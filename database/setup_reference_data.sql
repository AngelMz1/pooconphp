-- Tablas de Referencia del Sistema de Gestión Médica (Colombia)

-- 1. Tipo de Documento
CREATE TABLE IF NOT EXISTS tipo_documento (
    id SERIAL PRIMARY KEY,
    codigo VARCHAR(10) NOT NULL UNIQUE,
    descripcion VARCHAR(100) NOT NULL,
    activo BOOLEAN DEFAULT true
);

INSERT INTO tipo_documento (codigo, descripcion) VALUES
('CC', 'Cédula de Ciudadanía'),
('TI', 'Tarjeta de Identidad'),
('RC', 'Registro Civil'),
('CE', 'Cédula de Extranjería'),
('PA', 'Pasaporte'),
('MS', 'Menor sin Identificación'),
('AS', 'Adulto sin Identificación'),
('PE', 'Permiso Especial de Permanencia')
ON CONFLICT (codigo) DO NOTHING;

-- 2. Sexo (Biológico / Legal)
CREATE TABLE IF NOT EXISTS sexo (
    id SERIAL PRIMARY KEY,
    codigo VARCHAR(10) NOT NULL UNIQUE,
    sexo VARCHAR(50) NOT NULL
);

INSERT INTO sexo (codigo, sexo) VALUES
('M', 'Masculino'),
('F', 'Femenino'),
('I', 'Intersexual')
ON CONFLICT (codigo) DO NOTHING;

-- 3. Estado Civil
CREATE TABLE IF NOT EXISTS estado_civil (
    id SERIAL PRIMARY KEY,
    codigo VARCHAR(10) UNIQUE,
    estado_civil VARCHAR(50) NOT NULL
);

INSERT INTO estado_civil (codigo, estado_civil) VALUES
('S', 'Soltero/a'),
('C', 'Casado/a'),
('U', 'Unión Libre'),
('V', 'Viudo/a'),
('D', 'Divorciado/a')
ON CONFLICT (codigo) DO NOTHING;

-- 4. Ciudades / Municipios (Muestra representativa)
CREATE TABLE IF NOT EXISTS ciudades (
    id SERIAL PRIMARY KEY,
    codigo_dane VARCHAR(10),
    nombre VARCHAR(100) NOT NULL,
    departamento VARCHAR(100) NOT NULL
);

INSERT INTO ciudades (codigo_dane, nombre, departamento) VALUES
('11001', 'Bogotá D.C.', 'Bogotá D.C.'),
('05001', 'Medellín', 'Antioquia'),
('76001', 'Cali', 'Valle del Cauca'),
('08001', 'Barranquilla', 'Atlántico'),
('13001', 'Cartagena', 'Bolívar'),
('68001', 'Bucaramanga', 'Santander'),
('17001', 'Manizales', 'Caldas'),
('66001', 'Pereira', 'Risaralda'),
('54001', 'Cúcuta', 'Norte de Santander'),
('73001', 'Ibagué', 'Tolima')
ON CONFLICT DO NOTHING;

-- 5. Barrios (Estructura básica, se llena dinámicamente o por lotes)
CREATE TABLE IF NOT EXISTS barrio (
    id SERIAL PRIMARY KEY,
    ciudad_id INTEGER REFERENCES ciudades(id),
    barrio VARCHAR(150) NOT NULL
);

-- Barrios de ejemplo para Bogotá (ID 1 asumiendo orden de inserción)
INSERT INTO barrio (ciudad_id, barrio) 
SELECT id, 'Chapinero' FROM ciudades WHERE nombre = 'Bogotá D.C.'
UNION ALL
SELECT id, 'Usaquén' FROM ciudades WHERE nombre = 'Bogotá D.C.'
UNION ALL
SELECT id, 'Suba' FROM ciudades WHERE nombre = 'Bogotá D.C.'
UNION ALL
SELECT id, 'Kennedy' FROM ciudades WHERE nombre = 'Bogotá D.C.'
UNION ALL
SELECT id, 'El Poblado' FROM ciudades WHERE nombre = 'Medellín';


-- 6. EPS (Entidades Promotoras de Salud)
CREATE TABLE IF NOT EXISTS eps (
    id SERIAL PRIMARY KEY,
    codigo VARCHAR(20),
    nombre_eps VARCHAR(150) NOT NULL,
    regimen VARCHAR(50) -- Contributivo/Subsidiado
);

INSERT INTO eps (codigo, nombre_eps, regimen) VALUES
('EPS001', 'Sanitas', 'Contributivo'),
('EPS002', 'Sura', 'Contributivo'),
('EPS003', 'Nueva EPS', 'Ambos'),
('EPS004', 'Salud Total', 'Contributivo'),
('EPS005', 'Compensar', 'Contributivo'),
('EPS006', 'Famisanar', 'Contributivo'),
('EPS007', 'Coosalud', 'Subsidiado'),
('EPS008', 'Capital Salud', 'Subsidiado'),
('EPS009', 'Mutual Ser', 'Subsidiado'),
('PART', 'Particular / Sin Aseguradora', 'Particular')
ON CONFLICT DO NOTHING;

-- 7. Régimen de Salud
CREATE TABLE IF NOT EXISTS regimen (
    id SERIAL PRIMARY KEY,
    regimen VARCHAR(50) NOT NULL
);

INSERT INTO regimen (regimen) VALUES
('Contributivo'),
('Subsidiado'),
('Especial'),
('Particular')
ON CONFLICT DO NOTHING;

-- 8. Grupo Sanguíneo y RH
CREATE TABLE IF NOT EXISTS gs_rh (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(10) NOT NULL UNIQUE
);

INSERT INTO gs_rh (nombre) VALUES
('A+'), ('A-'),
('B+'), ('B-'),
('AB+'), ('AB-'),
('O+'), ('O-')
ON CONFLICT (nombre) DO NOTHING;

-- 9. Etnias
CREATE TABLE IF NOT EXISTS etnia (
    id SERIAL PRIMARY KEY,
    codigo VARCHAR(10),
    etnia VARCHAR(100) NOT NULL
);

INSERT INTO etnia (codigo, etnia) VALUES
('1', 'Indígena'),
('2', 'Rrom (Gitano)'),
('3', 'Raizal del Archipiélago de San Andrés y Providencia'),
('4', 'Palenquero de San Basilio'),
('5', 'Negro, Mulato, Afrocolombiano'),
('6', 'Ninguna')
ON CONFLICT DO NOTHING;

-- 10. Nivel de Escolaridad
CREATE TABLE IF NOT EXISTS escolaridad (
    id SERIAL PRIMARY KEY,
    escolaridad VARCHAR(100) NOT NULL
);

INSERT INTO escolaridad (escolaridad) VALUES
('Ninguno'),
('Preescolar'),
('Básica Primaria'),
('Básica Secundaria'),
('Media Académica o Técnica'),
('Técnico Profesional'),
('Tecnológico'),
('Profesional Universitario'),
('Especialización'),
('Maestría'),
('Doctorado')
ON CONFLICT DO NOTHING;

-- 11. Orientación Sexual
CREATE TABLE IF NOT EXISTS orient_sexual (
    id SERIAL PRIMARY KEY,
    orientacion VARCHAR(100) NOT NULL
);

INSERT INTO orient_sexual (orientacion) VALUES
('Heterosexual'),
('Homosexual'),
('Bisexual'),
('Pansexual'),
('Asexual'),
('Otro'),
('Prefiero no responder')
ON CONFLICT DO NOTHING;

-- 12. Acudientes (Tabla relacional, no estrictamente catalogo fijo, pero necesaria)
CREATE TABLE IF NOT EXISTS acudientes (
    id SERIAL PRIMARY KEY,
    documento VARCHAR(20),
    nombre VARCHAR(150) NOT NULL,
    telefono VARCHAR(20),
    parentesco VARCHAR(50),
    created_at TIMESTAMP DEFAULT NOW()
);

-- 13. CIE-10 (Diagnósticos) - Estructura para carga masiva
CREATE TABLE IF NOT EXISTS cie10 (
    id SERIAL PRIMARY KEY,
    codigo VARCHAR(10) NOT NULL UNIQUE,
    descripcion TEXT NOT NULL,
    sexo_aplicable VARCHAR(10) DEFAULT 'A', -- A=Ambos, M=Masc, F=Fem
    edad_minima INTEGER DEFAULT 0,
    edad_maxima INTEGER DEFAULT 120,
    activo BOOLEAN DEFAULT true
);

-- 14. CUPS (Procedimientos) - Estructura para carga masiva
CREATE TABLE IF NOT EXISTS cups (
    id SERIAL PRIMARY KEY,
    codigo VARCHAR(20) NOT NULL UNIQUE,
    nombre VARCHAR(500) NOT NULL,
    descripcion TEXT, -- Opcional
    seccion VARCHAR(100), -- Capitulo/Seccion si aplica
    activo BOOLEAN DEFAULT true
);

-- Índices para búsqueda rápida
CREATE INDEX IF NOT EXISTS idx_cie10_codigo ON cie10(codigo);
CREATE INDEX IF NOT EXISTS idx_cie10_descripcion ON cie10(descripcion);
CREATE INDEX IF NOT EXISTS idx_cups_codigo ON cups(codigo);
CREATE INDEX IF NOT EXISTS idx_cups_nombre ON cups(nombre);

-- Políticas RLS (Permitir lectura a todos, escritura solo admin idealmente, pero aqui abierto por desarrollo)
ALTER TABLE tipo_documento ENABLE ROW LEVEL SECURITY;
CREATE POLICY "Lectura tipo_documento" ON tipo_documento FOR SELECT USING (true);

ALTER TABLE cie10 ENABLE ROW LEVEL SECURITY;
CREATE POLICY "Lectura cie10" ON cie10 FOR SELECT USING (true);

ALTER TABLE cups ENABLE ROW LEVEL SECURITY;
CREATE POLICY "Lectura cups" ON cups FOR SELECT USING (true);
