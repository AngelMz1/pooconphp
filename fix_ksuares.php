<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/includes/auth_helper.php';

use App\SupabaseClient;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();
$supabase = new SupabaseClient($_ENV['SUPABASE_URL'], $_ENV['SUPABASE_KEY']);

echo "<h1>Reparación de Perfil Médico (ksuares)</h1>";

// 1. Buscar usuario
try {
    // Buscamos por email o nombre que contenga ksuares
    // Supabase filter syntax: email=ilike.*ksuares* or nombre_completo
    $users = $supabase->select('users', '*', 'email=ilike.*ksuares*');
    
    if (empty($users)) {
        // Intentar buscar por nombre si email falla
        $users = $supabase->select('users', '*', 'nombre_completo=ilike.*ksuares*');
    }

    if (empty($users)) {
        die("❌ No se encontró ningún usuario que coincida con 'ksuares'. Verifique el nombre o email.");
    }

    $u = $users[0];
    echo "✅ Usuario encontrado: <b>{$u['nombre_completo']}</b> (ID: {$u['id']}, Rol: {$u['rol']})<br>";

    if (strtolower($u['rol']) !== 'medico') {
        echo "⚠️ Advertencia: El rol de este usuario es '{$u['rol']}', debería ser 'medico'.<br>";
    }

    // 2. Verificar si ya existe en medicos
    $medicoPerfil = $supabase->select('medicos', '*', "user_id=eq." . $u['id']);

    if (!empty($medicoPerfil)) {
        die("✅ Este usuario YA tiene un perfil médico creado (ID Médico: {$medicoPerfil[0]['id']}). Debería aparecer en la lista si tiene especialidad.");
    }

    // 3. Crear perfil médico
    echo "⏳ Creando perfil en tabla 'medicos'...<br>";
    
    // Separar nombre para rellenar datos
    $parts = explode(' ', $u['nombre_completo']);
    $nombre = $parts[0];
    $apellido = isset($parts[1]) ? $parts[1] : 'Doctor';

    // Asumimos especialidad ID 1 (Medicina General). Si falla, el usuario deberá indicar una válida.
    // Intentemos buscar una especialidad existente
    $esps = $supabase->select('especialidades', 'id');
    $espId = !empty($esps) ? $esps[0]['id'] : 1; 

    $nuevoMedico = [
        'user_id' => $u['id'],
        'primer_nombre' => $nombre,
        'primer_apellido' => $apellido,
        'especialidad_id' => $espId,
        'telefono' => '0000000000',
        'email' => $u['email']
    ];

    $res = $supabase->insert('medicos', $nuevoMedico);
    
    if (isset($res[0]['id']) || (isset($res['code']) && $res === 201)) { // Supabase insert return check
        echo "🎉 <b>ÉXITO:</b> Perfil médico creado correctamente.<br>";
        echo "<a href='views/gestion_citas.php'>Volver a Gestión de Citas</a>";
    } else {
        echo "❌ Error al insertar (Respuesta API): <pre>" . print_r($res, true) . "</pre>";
    }

} catch (Throwable $e) {
    die("Error Crítico: " . $e->getMessage());
}
