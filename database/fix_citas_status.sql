-- Fix for citas_estado_check constraint
-- The previous constraint likely did not include 'por_confirmar'
-- We drop the constraint and re-add it with the correct values.

ALTER TABLE citas DROP CONSTRAINT IF EXISTS citas_estado_check;

ALTER TABLE citas 
ADD CONSTRAINT citas_estado_check 
CHECK (estado IN ('pendiente', 'atendida', 'cancelada', 'por_confirmar'));
