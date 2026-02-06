-- Migración: Agregar columnas de datos extendidos a la tabla pacientes
-- Este script agrega todas las columnas de referencia que estaban en el esquema original de Supabase

-- 1. Identificación y Sexo
ALTER TABLE pacientes ADD COLUMN IF NOT EXISTS tipo_documento_id INTEGER REFERENCES tipo_documento(id);
ALTER TABLE pacientes ADD COLUMN IF NOT EXISTS sexo_id INTEGER REFERENCES sexo(id);

-- 2. Ubicación
ALTER TABLE pacientes ADD COLUMN IF NOT EXISTS ciudad_id INTEGER REFERENCES ciudades(id);
ALTER TABLE pacientes ADD COLUMN IF NOT EXISTS lugar_nacimiento INTEGER REFERENCES ciudades(id);
ALTER TABLE pacientes ADD COLUMN IF NOT EXISTS barrio_id INTEGER REFERENCES barrio(id);

-- 3. Salud y aseguramiento
ALTER TABLE pacientes ADD COLUMN IF NOT EXISTS eps_id INTEGER REFERENCES eps(id);
ALTER TABLE pacientes ADD COLUMN IF NOT EXISTS regimen_id INTEGER REFERENCES regimen(id);
ALTER TABLE pacientes ADD COLUMN IF NOT EXISTS gs_rh_id INTEGER REFERENCES gs_rh(id);

-- 4. Datos sociodemográficos
ALTER TABLE pacientes ADD COLUMN IF NOT EXISTS estrato INTEGER CHECK (estrato BETWEEN 1 AND 6);
ALTER TABLE pacientes ADD COLUMN IF NOT EXISTS estado_civil_id INTEGER REFERENCES estado_civil(id);
ALTER TABLE pacientes ADD COLUMN IF NOT EXISTS ocupacion VARCHAR(100);
ALTER TABLE pacientes ADD COLUMN IF NOT EXISTS escolaridad_id INTEGER REFERENCES escolaridad(id);

-- 5. Diversidad
ALTER TABLE pacientes ADD COLUMN IF NOT EXISTS etnia_id INTEGER REFERENCES etnia(id);
ALTER TABLE pacientes ADD COLUMN IF NOT EXISTS orien_sexual_id INTEGER REFERENCES orient_sexual(id);

-- 6. Vulnerabilidad social (campos de texto libre)
ALTER TABLE pacientes ADD COLUMN IF NOT EXISTS g_poblacion TEXT; -- Grupo poblacional
ALTER TABLE pacientes ADD COLUMN IF NOT EXISTS prog_social TEXT; -- Programas sociales
ALTER TABLE pacientes ADD COLUMN IF NOT EXISTS discapacidad TEXT;
ALTER TABLE pacientes ADD COLUMN IF NOT EXISTS cond_vulnerabilidad TEXT; -- Condiciones de vulnerabilidad
ALTER TABLE pacientes ADD COLUMN IF NOT EXISTS hech_victimizantes TEXT; -- Hechos victimizantes

-- 7. Acudiente
ALTER TABLE pacientes ADD COLUMN IF NOT EXISTS acudiente_id INTEGER REFERENCES acudientes(id);

-- Crear índices para mejorar rendimiento
CREATE INDEX IF NOT EXISTS idx_pacientes_tipo_documento ON pacientes(tipo_documento_id);
CREATE INDEX IF NOT EXISTS idx_pacientes_ciudad ON pacientes(ciudad_id);
CREATE INDEX IF NOT EXISTS idx_pacientes_eps ON pacientes(eps_id);
CREATE INDEX IF NOT EXISTS idx_pacientes_acudiente ON pacientes(acudiente_id);

-- Confirmar
SELECT 'Migración completada: Columnas extendidas agregadas a tabla pacientes' AS status;
