-- Verificar que los datos de Nariño están en la base de datos

-- 1. Contar municipios de Nariño
SELECT COUNT(*) as total_municipios 
FROM ciudades 
WHERE departamento = 'Nariño';

-- 2. Ver todos los municipios de Nariño
SELECT codigo_dane, nombre, departamento 
FROM ciudades 
WHERE departamento = 'Nariño'
ORDER BY nombre;

-- 3. Contar barrios de Pasto
SELECT COUNT(*) as total_barrios 
FROM barrio b
JOIN ciudades c ON b.ciudad_id = c.id
WHERE c.codigo_dane = '52001';

-- 4. Ver barrios de Pasto por zona
SELECT zona, COUNT(*) as cantidad 
FROM barrio b
JOIN ciudades c ON b.ciudad_id = c.id
WHERE c.codigo_dane = '52001'
GROUP BY zona
ORDER BY zona;

-- 5. Ver todos los barrios de Pasto
SELECT b.barrio, b.zona
FROM barrio b
JOIN ciudades c ON b.ciudad_id = c.id
WHERE c.codigo_dane = '52001'
ORDER BY b.zona, b.barrio;
