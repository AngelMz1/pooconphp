-- Solución al error: value out of range for type integer (Código 22003)
-- Los documentos de identidad y teléfonos a menudo superan el límite de un entero (2 mil millones).
-- Se recomienda usar VARCHAR o TEXT para estos campos.

ALTER TABLE medicos ALTER COLUMN num_documento TYPE VARCHAR(50);
ALTER TABLE medicos ALTER COLUMN num_registro TYPE VARCHAR(50);
ALTER TABLE medicos ALTER COLUMN telefono TYPE VARCHAR(50);

-- Si tienes la tabla users y quieres prevenir lo mismo (aunque users no tiene campos de este tipo usualmente):
-- ALTER TABLE users ALTER COLUMN ... 
