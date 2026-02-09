-- =====================================================
-- DATOS GEOGRÁFICOS: NARIÑO - PASTO Y MUNICIPIOS ALEDAÑOS
-- =====================================================
-- Script SQL para poblar tablas de ciudades y barrios
-- Enfocado en el departamento de Nariño, Colombia
-- Con énfasis en Pasto y municipios circundantes
-- =====================================================

-- =====================================================
-- TABLA: CIUDADES / MUNICIPIOS DE NARIÑO
-- =====================================================

CREATE TABLE IF NOT EXISTS ciudades (
    id SERIAL PRIMARY KEY,
    codigo_dane VARCHAR(10) UNIQUE NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    departamento VARCHAR(50) NOT NULL DEFAULT 'Nariño',
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- INSERTAR MUNICIPIOS DE NARIÑO
-- Ordenados por importancia: Pasto primero, luego municipios aledaños
INSERT INTO ciudades (codigo_dane, nombre, departamento) VALUES
-- 1. PASTO (Capital departamental)
('52001', 'Pasto (San Juan de Pasto)', 'Nariño'),

-- 2. MUNICIPIOS DEL ÁREA METROPOLITANA Y CERCANOS A PASTO
('52689', 'Sandoná', 'Nariño'),
('52399', 'La Florida', 'Nariño'),
('52678', 'Consacá', 'Nariño'),
('52786', 'Tangua', 'Nariño'),  
('52885', 'Yacuanquer', 'Nariño'),
('52051', 'Buesaco', 'Nariño'),
('52258', 'Funes', 'Nariño'),
('52381', 'La Cruz', 'Nariño'),
('52788', 'Taminango', 'Nariño'),

-- 3. OTROS MUNICIPIOS IMPORTANTES DE NARIÑO
('52835', 'Túquerres', 'Nariño'),
('52083', 'Ipiales', 'Nariño'),
('52480', 'Nariño', 'Nariño'),
('52694', 'Sapuyes', 'Nariño'),
('52287', 'Guaitarilla', 'Nariño'),
('52520', 'Ospina', 'Nariño'),
('52520', 'Pupiales', 'Nariño'),
('52560', 'Piedrancha', 'Nariño'),
('52585', 'Policarpa', 'Nariño'),
('52612', 'Ricaurte', 'Nariño'),
('52696', 'Samaniego', 'Nariño'),
('52786', 'Tumaco (San Andrés de Tumaco)', 'Nariño'),
('52240', 'El Tambo', 'Nariño'),
('52418', 'La Unión', 'Nariño'),
('52405', 'La Llanada', 'Nariño'),
('52207', 'Cumbal', 'Nariño'),
('52233', 'El Charco', 'Nariño'),
('52490', 'Olaya Herrera', 'Nariño')
ON CONFLICT (codigo_dane) DO NOTHING;

-- =====================================================
-- TABLA: BARRIOS DE PASTO
-- =====================================================

CREATE TABLE IF NOT EXISTS barrio (
    id SERIAL PRIMARY KEY,
    ciudad_id INTEGER REFERENCES ciudades(id) ON DELETE CASCADE,
    barrio VARCHAR(150) NOT NULL,
    zona VARCHAR(50), -- Norte, Sur, Centro, Oriente, Occidente
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- INSERTAR BARRIOS DE PASTO
-- Divididos por zonas geográficas de la ciudad

-- ZONA CENTRO
INSERT INTO barrio (ciudad_id, barrio, zona) 
SELECT id, 'Centro', 'Centro' FROM ciudades WHERE codigo_dane = '52001'
UNION ALL
SELECT id, 'La Panadería', 'Centro' FROM ciudades WHERE codigo_dane = '52001'
UNION ALL
SELECT id, 'San Juan de Dios', 'Centro' FROM ciudades WHERE codigo_dane = '52001'
UNION ALL
SELECT id, 'Santiago', 'Centro' FROM ciudades WHERE codigo_dane = '52001'
UNION ALL
SELECT id, 'San Felipe', 'Centro' FROM ciudades WHERE codigo_dane = '52001'
UNION ALL
SELECT id, 'Las Cuadras', 'Centro' FROM ciudades WHERE codigo_dane = '52001'

-- ZONA NORTE  
UNION ALL
SELECT id, 'Torobajo', 'Norte' FROM ciudades WHERE codigo_dane = '52001'
UNION ALL
SELECT id, 'Jongovito', 'Norte' FROM ciudades WHERE codigo_dane = '52001'
UNION ALL
SELECT id, 'La Lomita', 'Norte' FROM ciudades WHERE codigo_dane = '52001'
UNION ALL
SELECT id, 'El Progreso', 'Norte' FROM ciudades WHERE codigo_dane = '52001'
UNION ALL
SELECT id, 'Aranda', 'Norte' FROM ciudades WHERE codigo_dane = '52001'
UNION ALL
SELECT id, 'San Vicente', 'Norte' FROM ciudades WHERE codigo_dane = '52001'
UNION ALL
SELECT id, 'Pullitopamba', 'Norte' FROM ciudades WHERE codigo_dane = '52001'
UNION ALL
SELECT id, 'La Carolina', 'Norte' FROM ciudades WHERE codigo_dane = '52001'
UNION ALL
SELECT id, 'Chimangual', 'Norte' FROM ciudades WHERE codigo_dane = '52001'

-- ZONA SUR
UNION ALL
SELECT id, 'Lorenzo', 'Sur' FROM ciudades WHERE codigo_dane = '52001'
UNION ALL
SELECT id, 'Mijitayo', 'Sur' FROM ciudades WHERE codigo_dane = '52001'
UNION ALL
SELECT id, 'El Bosque', 'Sur' FROM ciudades WHERE codigo_dane = '52001'
UNION ALL
SELECT id, 'Chambú', 'Sur' FROM ciudades WHERE codigo_dane = '52001'
UNION ALL
SELECT id, 'Pandiaco', 'Sur' FROM ciudades WHERE codigo_dane = '52001'
UNION ALL
SELECT id, 'Catambuco', 'Sur' FROM ciudades WHERE codigo_dane = '52001'
UNION ALL
SELECT id, 'Cujacal', 'Sur' FROM ciudades WHERE codigo_dane = '52001'
UNION ALL
SELECT id, 'Obonuco', 'Sur' FROM ciudades WHERE codigo_dane = '52001'
UNION ALL
SELECT id, 'San Francisco', 'Sur' FROM ciudades WHERE codigo_dane = '52001'

-- ZONA ORIENTAL
UNION ALL
SELECT id, 'Chapalito', 'Oriente' FROM ciudades WHERE codigo_dane = '52001'
UNION ALL
SELECT id, 'Miraflores', 'Oriente' FROM ciudades WHERE codigo_dane = '52001'
UNION ALL
SELECT id, 'El Rosario', 'Oriente' FROM ciudades WHERE codigo_dane = '52001'
UNION ALL
SELECT id, 'La Aurora', 'Oriente' FROM ciudades WHERE codigo_dane = '52001'
UNION ALL
SELECT id, 'San Andrés', 'Oriente' FROM ciudades WHERE codigo_dane = '52001'
UNION ALL
SELECT id, 'La Esperanza', 'Oriente' FROM ciudades WHERE codigo_dane = '52001'

-- ZONA OCCIDENTAL
UNION ALL
SELECT id, 'Tamasagra', 'Occidente' FROM ciudades WHERE codigo_dane = '52001'
UNION ALL
SELECT id, 'La Isla', 'Occidente' FROM ciudades WHERE codigo_dane = '52001'
UNION ALL
SELECT id, 'Fátima', 'Occidente' FROM ciudades WHERE codigo_dane = '52001'
UNION ALL
SELECT id, 'Alameda del Río', 'Occidente' FROM ciudades WHERE codigo_dane = '52001'
UNION ALL
SELECT id, 'Aguapamba', 'Occidente' FROM ciudades WHERE codigo_dane = '52001'
UNION ALL
SELECT id, 'Tejar', 'Occidente' FROM ciudades WHERE codigo_dane = '52001'
UNION ALL
SELECT id, 'Sindagua', 'Occidente' FROM ciudades WHERE codigo_dane = '52001'

-- COMUNAS Y BARRIOS ADICIONALES IMPORTANTES
UNION ALL
SELECT id, 'San Antonio de Padua', 'Sur' FROM ciudades WHERE codigo_dane = '52001'
UNION ALL
SELECT id, 'Puerres', 'Norte' FROM ciudades WHERE codigo_dane = '52001'
UNION ALL
SELECT id, 'Modelo Centro', 'Centro' FROM ciudades WHERE codigo_dane = '52001'
UNION ALL
SELECT id, 'Santa Mónica', 'Sur' FROM ciudades WHERE codigo_dane = '52001'
UNION ALL
SELECT id, 'La Floresta', 'Norte' FROM ciudades WHERE codigo_dane = '52001'
UNION ALL
SELECT id, 'La Castellana', 'Sur' FROM ciudades WHERE codigo_dane = '52001'
UNION ALL
SELECT id, 'Morasurco', 'Oriente' FROM ciudades WHERE codigo_dane = '52001'
UNION ALL
SELECT id, 'Tescual', 'Oriente' FROM ciudades WHERE codigo_dane = '52001'
UNION ALL
SELECT id, 'Villa del Prado', 'Norte' FROM ciudades WHERE codigo_dane = '52001'
UNION ALL
SELECT id, 'Arboledas', 'Norte' FROM ciudades WHERE codigo_dane = '52001'
UNION ALL
SELECT id, 'Las Palmas', 'Norte' FROM ciudades WHERE codigo_dane = '52001'
UNION ALL
SELECT id, 'Anganoy', 'Norte' FROM ciudades WHERE codigo_dane = '52001'
UNION ALL
SELECT id, 'Mapachico', 'Sur' FROM ciudades WHERE codigo_dane = '52001'
UNION ALL
SELECT id, 'San Ignacio', 'Oriente' FROM ciudades WHERE codigo_dane = '52001'
ON CONFLICT DO NOTHING;

-- =====================================================
-- ÍNDICES PARA OPTIMIZACIÓN
-- =====================================================

CREATE INDEX IF NOT EXISTS idx_ciudades_departamento ON ciudades(departamento);
CREATE INDEX IF NOT EXISTS idx_ciudades_nombre ON ciudades(nombre);
CREATE INDEX IF NOT EXISTS idx_barrio_ciudad ON barrio(ciudad_id);
CREATE INDEX IF NOT EXISTS idx_barrio_zona ON barrio(zona);

-- =====================================================
-- COMENTARIOS Y NOTAS
-- =====================================================

COMMENT ON TABLE ciudades IS 'Catálogo de municipios de Nariño con énfasis en Pasto y área metropolitana';
COMMENT ON TABLE barrio IS 'Barrios de Pasto organizados por zonas geográficas (Centro, Norte, Sur, Oriente, Occidente)';
COMMENT ON COLUMN barrio.zona IS 'Zona geográfica del barrio dentro de Pasto';

-- =====================================================
-- VERIFICACIÓN
-- =====================================================

-- Ver cantidad de municipios insertados
-- SELECT COUNT(*) as total_municipios FROM ciudades WHERE departamento = 'Nariño';

-- Ver cantidad de barrios de Pasto
-- SELECT COUNT(*) as total_barrios FROM barrio WHERE ciudad_id = (SELECT id FROM ciudades WHERE codigo_dane = '52001');

-- Ver barrios por zona
-- SELECT zona, COUNT(*) as cantidad 
-- FROM barrio b
-- JOIN ciudades c ON b.ciudad_id = c.id
-- WHERE c.codigo_dane = '52001'
-- GROUP BY zona
-- ORDER BY zona;
