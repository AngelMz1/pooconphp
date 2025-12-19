-- Script de migración para agregar columnas faltantes a historias_clinicas
-- Ejecutar este script en el SQL Editor de Supabase

-- Agregar columnas faltantes si no existen
DO $$ 
BEGIN
    -- Agregar columna analisis_plan si no existe
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_name = 'historias_clinicas' 
        AND column_name = 'analisis_plan'
    ) THEN
        ALTER TABLE historias_clinicas ADD COLUMN analisis_plan TEXT;
    END IF;

    -- Agregar columna diagnostico si no existe
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_name = 'historias_clinicas' 
        AND column_name = 'diagnostico'
    ) THEN
        ALTER TABLE historias_clinicas ADD COLUMN diagnostico TEXT;
    END IF;

    -- Agregar columna tratamiento si no existe
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_name = 'historias_clinicas' 
        AND column_name = 'tratamiento'
    ) THEN
        ALTER TABLE historias_clinicas ADD COLUMN tratamiento TEXT;
    END IF;

    -- Agregar columna observaciones si no existe
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_name = 'historias_clinicas' 
        AND column_name = 'observaciones'
    ) THEN
        ALTER TABLE historias_clinicas ADD COLUMN observaciones TEXT;
    END IF;
END $$;

-- Verificar las columnas de la tabla
SELECT column_name, data_type, is_nullable
FROM information_schema.columns
WHERE table_name = 'historias_clinicas'
ORDER BY ordinal_position;
