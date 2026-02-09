-- ============================================
-- USUARIOS Y PERFILES
-- ============================================

-- Insertar usuarios del sistema con contraseñas (admin123 para todos)
INSERT INTO users (username, password_hash, nombre_completo, rol, active) VALUES
('admin', '$2y$10$M4OviwdGVsE29dOPm6xq3.yLkN/Q8nB7mQZJ8T1zKxLz2YhGxVJRW', 'Administrador', 'admin', true),
('medico', '$2y$10$M4OviwdGVsE29dOPm6xq3.yLkN/Q8nB7mQZJ8T1zKxLz2YhGxVJRW', 'Médico de Prueba', 'medico', true),
('cajero', '$2y$10$M4OviwdGVsE29dOPm6xq3.yLkN/Q8nB7mQZJ8T1zKxLz2YhGxVJRW', 'Cajero de Prueba', 'cajero', true),
('facturador', '$2y$10$M4OviwdGVsE29dOPm6xq3.yLkN/Q8nB7mQZJ8T1zKxLz2YhGxVJRW', 'Facturador de Prueba', 'cajero', true)
ON CONFLICT (username) DO NOTHING;

-- Crear perfil médico para el usuario medico
INSERT INTO medicos (user_id, especialidad, registro_medico, telefono, email)
SELECT id, 'Medicina General', 'RM-12345', '3001234567', 'medico@example.com'
FROM users 
WHERE username = 'medico'
ON CONFLICT (user_id) DO NOTHING;

-- ============================================
-- VERIFICACIÓN FINAL
-- ============================================

-- Resumen de datos insertados
DO $$
BEGIN
    RAISE NOTICE '========================================';
    RAISE NOTICE 'CONFIGURACIÓN COMPLETADA';
    RAISE NOTICE '========================================';
    RAISE NOTICE '';
    RAISE NOTICE 'Usuarios: %', (SELECT COUNT(*) FROM users);
    RAISE NOTICE 'Perfiles médicos: %', (SELECT COUNT(*) FROM medicos);
    RAISE NOTICE 'Municipios Nariño: %', (SELECT COUNT(*) FROM ciudades WHERE departamento = 'Nariño');
    RAISE NOTICE 'Barrios Pasto: %', (SELECT COUNT(*) FROM barrio b JOIN ciudades c ON b.ciudad_id = c.id WHERE c.codigo_dane = '52001');
    RAISE NOTICE '';
    RAISE NOTICE 'El sistema está listo para usar';
    RAISE NOTICE '========================================';
END $$;
