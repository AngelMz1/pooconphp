-- ============================================
-- SINCRONIZACIÓN DE DATOS DE NARIÑO A SUPABASE
-- ============================================
-- IMPORTANTE: Ejecutar DESPUÉS de supabase_schema_update.sql
-- Este script ingresa los datos de Nariño en Supabase

-- ============================================
-- 1. INSERTAR MUNICIPIOS DE NARIÑO
-- ============================================

-- OPCIÓN A: Actualizar registros existentes (RECOMENDADO)
-- Primero actualizar los que ya existen para agregar codigo_dane y departamento
DO $$
DECLARE
    ciudad_record RECORD;
BEGIN
    -- Actualizar ciudades existentes con sus códigos DANE y departamento
    FOR ciudad_record IN 
        SELECT unnest(ARRAY['Pasto (San Juan de Pasto)', 'Sandoná', 'La Florida', 'Consacá', 
                             'Tangua', 'Yacuanquer', 'Buesaco', 'Funes', 'La Cruz', 'Taminango',
                             'Túquerres', 'Ipiales', 'Nariño', 'Sapuyes', 'Guaitarilla', 
                             'Ospina', 'Pupiales', 'Piedrancha', 'Policarpa', 'Ricaurte',
                             'Samaniego', 'Tumaco (San Andrés de Tumaco)', 'El Tambo', 
                             'La Unión', 'La Llanada', 'Cumbal']) as nombre,
               unnest(ARRAY['52001', '52689', '52399', '52678', '52786', '52885', '52051', 
                             '52258', '52381', '52788', '52835', '52083', '52480', '52694',
                             '52287', '52520', '52621', '52560', '52585', '52612', '52696',
                             '52838', '52240', '52418', '52405', '52207']) as codigo
    LOOP
        UPDATE public.ciudades 
        SET codigo_dane = ciudad_record.codigo,
            departamento = 'Nariño'
        WHERE nombre = ciudad_record.nombre;
        
        -- Si no existe, insertar
        IF NOT FOUND THEN
            INSERT INTO public.ciudades (codigo_dane, nombre, departamento)
            VALUES (ciudad_record.codigo, ciudad_record.nombre, 'Nariño');
        END IF;
    END LOOP;
END $$;

-- OPCIÓN B: Eliminar y reinsertar (solo si OPCIÓN A falla)
-- DESCOMENTAR SOLO SI ES NECESARIO
-- DELETE FROM public.ciudades WHERE nombre IN (
--     'Pasto (San Juan de Pasto)', 'Sandoná', 'La Florida', 'Consacá', 'Tangua', 
--     'Yacuanquer', 'Buesaco', 'Funes', 'La Cruz', 'Taminango', 'Túquerres', 
--     'Ipiales', 'Nariño', 'Sapuyes', 'Guaitarilla', 'Ospina', 'Pupiales', 
--     'Piedrancha', 'Policarpa', 'Ricaurte', 'Samaniego', 'Tumaco (San Andrés de Tumaco)', 
--     'El Tambo', 'La Unión', 'La Llanada', 'Cumbal'
-- );
-- 
-- INSERT INTO public.ciudades (codigo_dane, nombre, departamento) VALUES
-- ('52001', 'Pasto (San Juan de Pasto)', 'Nariño'),
-- ... (resto de valores)
-- ;

-- ============================================
-- 2. INSERTAR BARRIOS DE PASTO
-- ============================================

-- Primero eliminar barrios existentes de Pasto para evitar conflictos
DELETE FROM public.barrio 
WHERE ciudad_id = (SELECT id FROM public.ciudades WHERE codigo_dane = '52001');

-- Insertar barrios de Pasto (requiere obtener el ID de Pasto primero)
INSERT INTO public.barrio (ciudad_id, barrio, zona) 
SELECT id, 'Centro', 'Centro' FROM public.ciudades WHERE codigo_dane = '52001'
UNION ALL SELECT id, 'La Panadería', 'Centro' FROM public.ciudades WHERE codigo_dane = '52001'
UNION ALL SELECT id, 'San Juan de Dios', 'Centro' FROM public.ciudades WHERE codigo_dane = '52001'
UNION ALL SELECT id, 'Santiago', 'Centro' FROM public.ciudades WHERE codigo_dane = '52001'
UNION ALL SELECT id, 'San Felipe', 'Centro' FROM public.ciudades WHERE codigo_dane = '52001'
UNION ALL SELECT id, 'Las Cuadras', 'Centro' FROM public.ciudades WHERE codigo_dane = '52001'
UNION ALL SELECT id, 'Modelo Centro', 'Centro' FROM public.ciudades WHERE codigo_dane = '52001'
UNION ALL SELECT id, 'Torobajo', 'Norte' FROM public.ciudades WHERE codigo_dane = '52001'
UNION ALL SELECT id, 'Jongovito', 'Norte' FROM public.ciudades WHERE codigo_dane = '52001'
UNION ALL SELECT id, 'La Lomita', 'Norte' FROM public.ciudades WHERE codigo_dane = '52001'
UNION ALL SELECT id, 'El Progreso', 'Norte' FROM public.ciudades WHERE codigo_dane = '52001'
UNION ALL SELECT id, 'Aranda', 'Norte' FROM public.ciudades WHERE codigo_dane = '52001'
UNION ALL SELECT id, 'San Vicente', 'Norte' FROM public.ciudades WHERE codigo_dane = '52001'
UNION ALL SELECT id, 'Pullitopamba', 'Norte' FROM public.ciudades WHERE codigo_dane = '52001'
UNION ALL SELECT id, 'La Carolina', 'Norte' FROM public.ciudades WHERE codigo_dane = '52001'
UNION ALL SELECT id, 'Chimangual', 'Norte' FROM public.ciudades WHERE codigo_dane = '52001'
UNION ALL SELECT id, 'Puerres', 'Norte' FROM public.ciudades WHERE codigo_dane = '52001'
UNION ALL SELECT id, 'La Floresta', 'Norte' FROM public.ciudades WHERE codigo_dane = '52001'
UNION ALL SELECT id, 'Villa del Prado', 'Norte' FROM public.ciudades WHERE codigo_dane = '52001'
UNION ALL SELECT id, 'Arboledas', 'Norte' FROM public.ciudades WHERE codigo_dane = '52001'
UNION ALL SELECT id, 'Las Palmas', 'Norte' FROM public.ciudades WHERE codigo_dane = '52001'
UNION ALL SELECT id, 'Anganoy', 'Norte' FROM public.ciudades WHERE codigo_dane = '52001'
UNION ALL SELECT id, 'Lorenzo', 'Sur' FROM public.ciudades WHERE codigo_dane = '52001'
UNION ALL SELECT id, 'Mijitayo', 'Sur' FROM public.ciudades WHERE codigo_dane = '52001'
UNION ALL SELECT id, 'El Bosque', 'Sur' FROM public.ciudades WHERE codigo_dane = '52001'
UNION ALL SELECT id, 'Chambú', 'Sur' FROM public.ciudades WHERE codigo_dane = '52001'
UNION ALL SELECT id, 'Pandiaco', 'Sur' FROM public.ciudades WHERE codigo_dane = '52001'
UNION ALL SELECT id, 'Catambuco', 'Sur' FROM public.ciudades WHERE codigo_dane = '52001'
UNION ALL SELECT id, 'Cujacal', 'Sur' FROM public.ciudades WHERE codigo_dane = '52001'
UNION ALL SELECT id, 'Obonuco', 'Sur' FROM public.ciudades WHERE codigo_dane = '52001'
UNION ALL SELECT id, 'San Francisco', 'Sur' FROM public.ciudades WHERE codigo_dane = '52001'
UNION ALL SELECT id, 'San Antonio de Padua', 'Sur' FROM public.ciudades WHERE codigo_dane = '52001'
UNION ALL SELECT id, 'Santa Mónica', 'Sur' FROM public.ciudades WHERE codigo_dane = '52001'
UNION ALL SELECT id, 'La Castellana', 'Sur' FROM public.ciudades WHERE codigo_dane = '52001'
UNION ALL SELECT id, 'Mapachico', 'Sur' FROM public.ciudades WHERE codigo_dane = '52001'
UNION ALL SELECT id, 'Chapalito', 'Oriente' FROM public.ciudades WHERE codigo_dane = '52001'
UNION ALL SELECT id, 'Miraflores', 'Oriente' FROM public.ciudades WHERE codigo_dane = '52001'
UNION ALL SELECT id, 'El Rosario', 'Oriente' FROM public.ciudades WHERE codigo_dane = '52001'
UNION ALL SELECT id, 'La Aurora', 'Oriente' FROM public.ciudades WHERE codigo_dane = '52001'
UNION ALL SELECT id, 'San Andrés', 'Oriente' FROM public.ciudades WHERE codigo_dane = '52001'
UNION ALL SELECT id, 'La Esperanza', 'Oriente' FROM public.ciudades WHERE codigo_dane = '52001'
UNION ALL SELECT id, 'Morasurco', 'Oriente' FROM public.ciudades WHERE codigo_dane = '52001'
UNION ALL SELECT id, 'Tescual', 'Oriente' FROM public.ciudades WHERE codigo_dane = '52001'
UNION ALL SELECT id, 'San Ignacio', 'Oriente' FROM public.ciudades WHERE codigo_dane = '52001'
UNION ALL SELECT id, 'Tamasagra', 'Occidente' FROM public.ciudades WHERE codigo_dane = '52001'
UNION ALL SELECT id, 'La Isla', 'Occidente' FROM public.ciudades WHERE codigo_dane = '52001'
UNION ALL SELECT id, 'Fátima', 'Occidente' FROM public.ciudades WHERE codigo_dane = '52001'
UNION ALL SELECT id, 'Alameda del Río', 'Occidente' FROM public.ciudades WHERE codigo_dane = '52001'
UNION ALL SELECT id, 'Aguapamba', 'Occidente' FROM public.ciudades WHERE codigo_dane = '52001'
UNION ALL SELECT id, 'Tejar', 'Occidente' FROM public.ciudades WHERE codigo_dane = '52001'
UNION ALL SELECT id, 'Sindagua', 'Occidente' FROM public.ciudades WHERE codigo_dane = '52001';

-- ============================================
-- 3. VERIFICACIÓN
-- ============================================

-- Verificar municipios de Nariño
SELECT 
    'Municipios de Nariño' as tipo,
    COUNT(*) as cantidad 
FROM public.ciudades 
WHERE departamento = 'Nariño';

-- Verificar Pasto
SELECT 
    'Pasto' as tipo,
    id,
    codigo_dane,
    nombre,
    departamento
FROM public.ciudades 
WHERE codigo_dane = '52001';

-- Verificar barrios de Pasto
SELECT 
    'Barrios de Pasto' as tipo,
    COUNT(*) as cantidad
FROM public.barrio 
WHERE ciudad_id = (SELECT id FROM public.ciudades WHERE codigo_dane = '52001');

-- Verificar barrios por zona
SELECT 
    zona,
    COUNT(*) as cantidad
FROM public.barrio b
JOIN public.ciudades c ON b.ciudad_id = c.id
WHERE c.codigo_dane = '52001'
GROUP BY zona
ORDER BY zona;

-- ============================================
-- RESUMEN
-- ============================================
-- Datos insertados:
-- 1. 26 municipios de Nariño
-- 2. 51 barrios de Pasto
-- 3. Organizados por 5 zonas
-- ============================================
