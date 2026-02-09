-- ============================================
-- FIX RÁPIDO: Eliminar Constraint UNIQUE de Barrio
-- ============================================
-- Ejecutar ANTES de sync_narino_to_supabase.sql

-- Eliminar el constraint que causa conflictos
ALTER TABLE public.barrio 
  DROP CONSTRAINT IF EXISTS barrio_barrio_key;

-- Crear constraint único compuesto más apropiado
-- (permite mismo nombre de barrio en diferentes ciudades)
ALTER TABLE public.barrio 
  ADD CONSTRAINT barrio_ciudad_barrio_unique 
  UNIQUE (ciudad_id, barrio);

-- Verificación
SELECT 
    'Constraint eliminado' as status,
    COUNT(*) as constraints_restantes
FROM pg_constraint 
WHERE conname = 'barrio_barrio_key';
