-- ============================================================
-- Script para corregir el problema de historias que aparecen cerradas
-- Ejecutar en el SQL Editor de Supabase
-- ============================================================

-- 1. Eliminar el valor por defecto de fecha_egreso
-- (Este es el problema: si tiene DEFAULT NOW(), todas las historias nuevas 
--  tendrán fecha_egreso automáticamente, haciéndolas aparecer como "cerradas")
ALTER TABLE historias_clinicas 
ALTER COLUMN fecha_egreso DROP DEFAULT;

-- 2. Verificar la columna después del cambio
SELECT 
    column_name, 
    column_default, 
    is_nullable,
    data_type
FROM information_schema.columns
WHERE table_name = 'historias_clinicas' 
AND column_name = 'fecha_egreso';

-- 3. OPCIONAL: Si deseas "reabrir" historias que fueron creadas incorrectamente
-- con fecha_egreso (solo si fueron creadas muy recientemente, ej: mismo día)
-- ADVERTENCIA: Esto puede afectar historias que realmente debían estar cerradas.
-- Descomentar solo si estás seguro:

-- UPDATE historias_clinicas 
-- SET fecha_egreso = NULL 
-- WHERE fecha_egreso::date = fecha_ingreso::date 
-- AND fecha_egreso IS NOT NULL;

-- 4. Verificar el estado actual de las historias
SELECT 
    id_historia,
    id_paciente,
    fecha_ingreso,
    fecha_egreso,
    CASE 
        WHEN fecha_egreso IS NULL THEN 'ACTIVA'
        ELSE 'CERRADA'
    END as estado
FROM historias_clinicas
ORDER BY id_historia DESC
LIMIT 10;
