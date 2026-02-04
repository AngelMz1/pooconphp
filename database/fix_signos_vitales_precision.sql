-- ====================================================
-- SCRIPT SQL: Corregir precisión de campos numéricos
-- en tabla signos_vitales
-- Ejecutar en Supabase SQL Editor
-- ====================================================

-- El error "A field with precision 4, scale 2 must round to an 
-- absolute value less than 10^2" significa que DECIMAL(4,2) 
-- solo permite valores de -99.99 a 99.99

-- Corregir campo TEMPERATURA (ej: 36.5 - ok con 4,2)
-- Este probablemente está bien

-- Corregir campo PESO (ej: 175.5 kg necesita más espacio)
ALTER TABLE signos_vitales 
ALTER COLUMN peso TYPE DECIMAL(6,2);

-- Corregir campo TALLA (ej: 175 cm necesita más espacio)  
ALTER TABLE signos_vitales 
ALTER COLUMN talla TYPE DECIMAL(6,2);

-- Corregir campo TEMPERATURA por si acaso
ALTER TABLE signos_vitales 
ALTER COLUMN temperatura TYPE DECIMAL(5,2);

-- Corregir campo SP02 (saturación, 0-100%)
ALTER TABLE signos_vitales 
ALTER COLUMN sp02 TYPE DECIMAL(5,2);

-- Corregir campo PC (perímetro cefálico)
ALTER TABLE signos_vitales 
ALTER COLUMN pc TYPE DECIMAL(5,2);

-- ====================================================
-- FIN DEL SCRIPT
-- ====================================================
