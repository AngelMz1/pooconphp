-- Add user_id to medicos table to link with users system
ALTER TABLE medicos 
ADD COLUMN IF NOT EXISTS user_id BIGINT REFERENCES users(id); 
-- Note: unique constraint might be good if 1-to-1, but let's keep it simple for now.
-- Actually, since 'users' table in this app seems to be a custom table (based on login.php reading 'users' table), 
-- checking login.php line 29: $supabase->select('users', ...).
-- So it is a custom 'users' table, likely integer or uuid id. 
-- In login.php: $_SESSION['user_id'] = $user['id'].
-- Let's check DATABASE_SCHEMA.md again or assume generic integer/serial if not specified.
-- Users table schema wasn't fully in DATABASE_SCHEMA.md. 
-- I will assume the 'users' table uses the same ID type as medicos reference.
-- Let's just add the column first.

ALTER TABLE medicos ADD COLUMN IF NOT EXISTS user_id BIGINT;

-- Add status to consultations
ALTER TABLE consultas 
ADD COLUMN IF NOT EXISTS estado VARCHAR(20) DEFAULT 'pendiente' 
CHECK (estado IN ('pendiente', 'en_proceso', 'finalizada'));

-- Add link between historia and consulta
ALTER TABLE historias_clinicas
ADD COLUMN IF NOT EXISTS id_consulta BIGINT REFERENCES consultas(id_consulta);
