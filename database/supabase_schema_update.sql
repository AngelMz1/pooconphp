-- ============================================
-- ACTUALIZACIÓN DE SCHEMA SUPABASE
-- ============================================
-- Este script actualiza el schema de Supabase para ser compatible
-- con PostgreSQL local, agregando columnas necesarias para datos de Nariño

-- ============================================
-- 1. ACTUALIZAR TABLA CIUDADES
-- ============================================

-- Agregar columnas faltantes
ALTER TABLE public.ciudades 
  ADD COLUMN IF NOT EXISTS codigo_dane VARCHAR(10),
  ADD COLUMN IF NOT EXISTS departamento VARCHAR(100);

-- Crear índices para optimización
CREATE INDEX IF NOT EXISTS idx_ciudades_departamento ON public.ciudades(departamento);
CREATE INDEX IF NOT EXISTS idx_ciudades_codigo_dane ON public.ciudades(codigo_dane);
CREATE INDEX IF NOT EXISTS idx_ciudades_nombre ON public.ciudades(nombre);

-- Agregar constraint único para codigo_dane
DO $$ 
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint 
        WHERE conname = 'ciudades_codigo_dane_unique'
    ) THEN
        ALTER TABLE public.ciudades 
        ADD CONSTRAINT ciudades_codigo_dane_unique UNIQUE (codigo_dane);
    END IF;
END $$;

-- Comentarios para documentación
COMMENT ON COLUMN public.ciudades.codigo_dane IS 'Código DANE del municipio (estándar colombiano)';
COMMENT ON COLUMN public.ciudades.departamento IS 'Departamento al que pertenece el municipio';

-- ============================================
-- 2. ACTUALIZAR TABLA BARRIO
-- ============================================

-- CRÍTICO: Eliminar constraint UNIQUE de barrio
-- (permite barrios con mismo nombre en diferentes ciudades)
ALTER TABLE public.barrio 
  DROP CONSTRAINT IF EXISTS barrio_barrio_key;

-- Agregar columna zona
ALTER TABLE public.barrio 
  ADD COLUMN IF NOT EXISTS zona VARCHAR(100);

-- Crear índices para zona y ciudad
CREATE INDEX IF NOT EXISTS idx_barrio_zona ON public.barrio(zona);
CREATE INDEX IF NOT EXISTS idx_barrio_ciudad ON public.barrio(ciudad_id);

-- Crear constraint único compuesto (barrio + ciudad)
-- para evitar duplicados dentro de la misma ciudad
DO $$ 
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint 
        WHERE conname = 'barrio_ciudad_barrio_unique'
    ) THEN
        ALTER TABLE public.barrio 
        ADD CONSTRAINT barrio_ciudad_barrio_unique UNIQUE (ciudad_id, barrio);
    END IF;
END $$;

-- Comentario para documentación
COMMENT ON COLUMN public.barrio.zona IS 'Zona geográfica del barrio (Centro, Norte, Sur, Oriente, Occidente)';

-- ============================================
-- 3. VERIFICACIÓN
-- ============================================

-- Verificar que las columnas se agregaron correctamente
DO $$ 
DECLARE
    ciudades_cols INTEGER;
    barrio_cols INTEGER;
BEGIN
    -- Verificar ciudades
    SELECT COUNT(*) INTO ciudades_cols
    FROM information_schema.columns 
    WHERE table_name = 'ciudades' 
    AND column_name IN ('codigo_dane', 'departamento');
    
    -- Verificar barrio
    SELECT COUNT(*) INTO barrio_cols
    FROM information_schema.columns 
    WHERE table_name = 'barrio' 
    AND column_name = 'zona';
    
    -- Mostrar resultados
    RAISE NOTICE 'Columnas agregadas en ciudades: %', ciudades_cols;
    RAISE NOTICE 'Columnas agregadas en barrio: %', barrio_cols;
    
    IF ciudades_cols = 2 AND barrio_cols = 1 THEN
        RAISE NOTICE '✅ Schema actualizado exitosamente';
    ELSE
        RAISE WARNING '⚠️  Algunas columnas no se agregaron correctamente';
    END IF;
END $$;

-- ============================================
-- RESUMEN
-- ============================================
-- Cambios aplicados:
-- 1. ciudades: +codigo_dane, +departamento
-- 2. barrio: +zona
-- 3. Índices creados para optimización
-- 4. Constraints y comentarios agregados
-- ============================================
