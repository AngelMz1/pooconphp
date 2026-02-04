-- ====================================================
-- SCRIPT SQL: Eliminar citas pendientes
-- Ejecutar en Supabase SQL Editor
-- ====================================================

-- Ver primero cuántas citas pendientes hay (opcional)
SELECT COUNT(*) as total_pendientes 
FROM citas 
WHERE estado IN ('pendiente', 'por_confirmar');

-- Eliminar todas las citas con estado 'pendiente' o 'por_confirmar'
DELETE FROM citas 
WHERE estado IN ('pendiente', 'por_confirmar');

-- Si solo quieres eliminar las de estado 'pendiente' (sin las por_confirmar):
-- DELETE FROM citas WHERE estado = 'pendiente';

-- ====================================================
-- FIN DEL SCRIPT
-- ====================================================
