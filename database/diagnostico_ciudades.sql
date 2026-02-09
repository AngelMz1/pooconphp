-- ============================================
-- DIAGNÓSTICO: Verificar Datos de Nariño
-- ============================================

-- 1. ¿Cuántas ciudades hay en total en la tabla?
SELECT COUNT(*) as total_ciudades FROM ciudades;

-- 2. ¿Hay municipios de Nariño en la tabla?
SELECT COUNT(*) as total_narino 
FROM ciudades 
WHERE departamento = 'Nariño';

-- 3. ¿Está Pasto específicamente?
SELECT * FROM ciudades 
WHERE nombre LIKE '%Pasto%';

-- 4. Ver TODAS las ciudades que hay actualmente
SELECT id, codigo_dane, nombre, departamento 
FROM ciudades 
ORDER BY departamento, nombre;

-- 5. Ver los primeros 20 registros
SELECT * FROM ciudades LIMIT 20;
