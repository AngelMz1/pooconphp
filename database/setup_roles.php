<?php
/**
 * Script para configurar roles y permisos del sistema
 * Ejecutar vía navegador: http://localhost/pooconphp/database/setup_roles.php
 * O desde CLI: php setup_roles.php
 */

require_once '../vendor/autoload.php';

use App\DatabaseFactory;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
try {
    $dotenv->safeLoad();
} catch (Exception $e) {
    echo "Error loading .env: " . $e->getMessage() . "\n";
}

$db = DatabaseFactory::create();

echo "<pre>";
echo "=== CONFIGURACIÓN DE ROLES Y PERMISOS ===\n\n";

try {
    // 1. Actualizar constraint de roles
    echo "[1/10] Actualizando constraint de roles...\n";
    $db->query("ALTER TABLE users DROP CONSTRAINT IF EXISTS users_rol_check");
    $db->query("ALTER TABLE users ADD CONSTRAINT users_rol_check CHECK (rol IN ('admin', 'facturador', 'medico'))");
    echo "✓ Constraint actualizado\n\n";

    // 2. Migrar cajeros a facturadores
    echo "[2/10] Migrando usuarios 'cajero' a 'facturador'...\n";
    $result = $db->query("UPDATE users SET rol = 'facturador' WHERE rol = 'cajero'");
    echo "✓ Usuarios migrados\n\n";

    // 3. Crear tabla de permisos
    echo "[3/10] Creando tabla de permisos...\n";
    $db->query("
        CREATE TABLE IF NOT EXISTS permisos (
            id SERIAL PRIMARY KEY,
            codigo VARCHAR(50) UNIQUE NOT NULL,
            nombre VARCHAR(100) NOT NULL,
            descripcion TEXT,
            categoria VARCHAR(50)
        )
    ");
    echo "✓ Tabla de permisos creada\n\n";

    // 4. Crear tabla rol_permisos
    echo "[4/10] Creando tabla rol_permisos...\n";
    $db->query("
        CREATE TABLE IF NOT EXISTS rol_permisos (
            id SERIAL PRIMARY KEY,
            rol VARCHAR(20) NOT NULL,
            permiso_codigo VARCHAR(50) REFERENCES permisos(codigo),
            UNIQUE(rol, permiso_codigo)
        )
    ");
    echo "✓ Tabla rol_permisos creada\n\n";

    // 5. Insertar permisos
    echo "[5/10] Insertando permisos del sistema...\n";
    $permisos = [
        // Pacientes
        ['gestionar_pacientes', 'Gestionar Pacientes', 'Crear, editar y eliminar pacientes', 'Pacientes'],
        ['ver_pacientes', 'Ver Pacientes', 'Ver información de pacientes', 'Pacientes'],
        
        // Citas
        ['agendar_citas', 'Agendar Citas', 'Crear nuevas citas', 'Citas'],
        ['confirmar_citas', 'Confirmar Citas', 'Confirmar citas pendientes', 'Citas'],
        ['cancelar_citas', 'Cancelar Citas', 'Cancelar citas', 'Citas'],
        ['reagendar_citas', 'Reagendar Citas', 'Modificar fechas de citas', 'Citas'],
        ['ver_todas_citas', 'Ver Todas las Citas', 'Ver calendario completo', 'Citas'],
        
        // Consultas
        ['atender_consulta', 'Atender Consulta', 'Atender consultas médicas', 'Consultas'],
        ['crear_historia', 'Crear Historia Clínica', 'Crear historias clínicas', 'Consultas'],
        ['ver_historia', 'Ver Historias Clínicas', 'Consultar historias', 'Consultas'],
        ['prescribir_medicamentos', 'Prescribir Medicamentos', 'Crear fórmulas médicas', 'Consultas'],
        ['solicitar_procedimientos', 'Solicitar Procedimientos', 'Ordenar procedimientos', 'Consultas'],
        
        // Facturación
        ['generar_factura', 'Generar Factura', 'Generar facturas', 'Facturación'],
        ['registrar_pago', 'Registrar Pago', 'Registrar pagos', 'Facturación'],
        ['ver_facturas', 'Ver Facturas', 'Consultar facturas', 'Facturación'],
        ['anular_factura', 'Anular Factura', 'Anular facturas', 'Facturación'],
        ['administrar_tarifarios', 'Administrar Tarifarios', 'Gestionar tarifas', 'Facturación'],
        
        // Administración
        ['gestionar_usuarios', 'Gestionar Usuarios', 'Administrar usuarios', 'Administración'],
        ['gestionar_medicos', 'Gestionar Médicos', 'Administrar médicos', 'Administración'],
        ['configurar_sistema', 'Configurar Sistema', 'Configuraciones', 'Administración'],
        ['ver_reportes', 'Ver Reportes', 'Reportes y estadísticas', 'Administración'],
    ];

    foreach ($permisos as $permiso) {
        $db->query("
            INSERT INTO permisos (codigo, nombre, descripcion, categoria) 
            VALUES ('$permiso[0]', '$permiso[1]', '$permiso[2]', '$permiso[3]')
            ON CONFLICT (codigo) DO NOTHING
        ");
    }
    echo "✓ " . count($permisos) . " permisos insertados\n\n";

    // 6. Limpiar y asignar permisos por rol
    echo "[6/10] Asignando permisos a roles...\n";
    $db->query("DELETE FROM rol_permisos");

    // ADMIN: Todos los permisos
    $db->query("INSERT INTO rol_permisos (rol, permiso_codigo) SELECT 'admin', codigo FROM permisos");
    echo "✓ Admin: Todos los permisos\n";

    // FACTURADOR: Gestión de citas y consulta de historias
    $permisos_facturador = [
        'gestionar_pacientes', 'ver_pacientes',
        'agendar_citas', 'confirmar_citas', 'cancelar_citas', 'reagendar_citas', 'ver_todas_citas',
        'ver_historia',
        'ver_facturas', 'registrar_pago'
    ];
    foreach ($permisos_facturador as $p) {
        $db->query("INSERT INTO rol_permisos (rol, permiso_codigo) VALUES ('facturador', '$p') ON CONFLICT DO NOTHING");
    }
    echo "✓ Facturador: " . count($permisos_facturador) . " permisos\n";

    // MEDICO: Atención médica
    $permisos_medico = [
        'ver_pacientes',
        'ver_todas_citas',
        'atender_consulta', 'crear_historia', 'ver_historia', 'prescribir_medicamentos', 'solicitar_procedimientos',
        'generar_factura', 'ver_facturas', 'registrar_pago'
    ];
    foreach ($permisos_medico as $p) {
        $db->query("INSERT INTO rol_permisos (rol, permiso_codigo) VALUES ('medico', '$p') ON CONFLICT DO NOTHING");
    }
    echo "✓ Médico: " . count($permisos_medico) . " permisos\n\n";

    // 7. Actualizar estados de consulta
    echo "[7/10] Actualizando estados de consulta...\n";
    $db->query("ALTER TABLE consultas DROP CONSTRAINT IF EXISTS consultas_estado_check");
    $db->query("ALTER TABLE consultas ADD CONSTRAINT consultas_estado_check CHECK (estado IN ('pendiente', 'confirmada', 'en_curso', 'atendida', 'cancelada'))");
    echo "✓ Estados actualizados: pendiente, confirmada, en_curso, atendida, cancelada\n\n";

    // 8. Crear/actualizar usuarios de prueba
    echo "[8/10] Creando usuarios de prueba...\n";
    
    // Password hash para "admin123"
    $hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

    // Admin (ya existe)
    echo "  - admin (admin123)\n";

    // Facturador
    $db->query("
        INSERT INTO users (username, password_hash, nombre_completo, rol, active) 
        VALUES ('facturador', '$hash', 'Facturador de Prueba', 'facturador', true)
        ON CONFLICT (username) DO UPDATE 
        SET rol = 'facturador', password_hash = '$hash', active = true
    ");
    echo "  - facturador (admin123)\n";

    // Médico
    $db->query("
        INSERT INTO users (username, password_hash, nombre_completo, rol, active) 
        VALUES ('medico', '$hash', 'Médico de Prueba', 'medico', true)
        ON CONFLICT (username) DO UPDATE 
        SET rol = 'medico', password_hash = '$hash', active = true
    ");
    echo "  - medico (admin123)\n";

    // Actualizar medico2 si existe
    $db->query("UPDATE users SET rol = 'medico', password_hash = '$hash' WHERE username = 'medico2'");
    echo "✓ Usuarios de prueba configurados\n\n";

    // 9. Crear función helper
    echo "[9/10] Creando función de verificación de permisos...\n";
    $db->query("
        CREATE OR REPLACE FUNCTION tiene_permiso(p_user_id BIGINT, p_permiso_codigo VARCHAR)
        RETURNS BOOLEAN AS \$\$
        DECLARE
            v_rol VARCHAR(20);
            v_tiene_permiso BOOLEAN;
        BEGIN
            SELECT rol INTO v_rol FROM users WHERE id = p_user_id AND active = true;
            IF v_rol IS NULL THEN RETURN FALSE; END IF;
            SELECT EXISTS(SELECT 1 FROM rol_permisos WHERE rol = v_rol AND permiso_codigo = p_permiso_codigo) INTO v_tiene_permiso;
            RETURN v_tiene_permiso;
        END;
        \$\$ LANGUAGE plpgsql;
    ");
    echo "✓ Función tiene_permiso() creada\n\n";

    // 10. Resumen
    echo "[10/10] Resumen de configuración:\n";
    $result = $db->select('rol_permisos', 'rol, COUNT(*) as total', '', '', '', 'rol');
    foreach ($result as $row) {
        echo "  - {$row['rol']}: {$row['total']} permisos\n";
    }

    echo "\n=== CONFIGURACIÓN COMPLETADA EXITOSAMENTE ===\n";
    echo "\nCredenciales de prueba (todos con password 'admin123'):\n";
    echo "  - admin / admin123 (Administrador)\n";
    echo "  - facturador / admin123 (Facturador)\n";
    echo "  - medico / admin123 (Médico)\n";

} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}

echo "</pre>";
?>
