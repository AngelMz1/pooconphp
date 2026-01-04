-- Insertar Permisos Extendidos
INSERT INTO permissions (name, description) VALUES 
('ver_historia', 'Ver historias clínicas de pacientes'),
('crear_historia', 'Iniciar nuevas consultas e historias'),
('modificar_historia', 'Editar historias clínicas en curso'),
('cerrar_historia', 'Cerrar definitivamente historias clínicas'),
('cancelar_citas', 'Cancelar citas médicas agendadas')
ON CONFLICT (name) DO NOTHING;
