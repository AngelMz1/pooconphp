<?php
/**
 * Fix Foreign Key and Complete Migration
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\DatabaseFactory;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$supabase = DatabaseFactory::create();

echo "=== Fixing Foreign Keys and Migrating Permissions ===\n\n";

try {
    // Step 1: Check foreign key constraints
    echo "Step 1: Checking foreign key constraints...\n";
    
    $fkeys = $supabase->query("
        SELECT 
            tc.constraint_name,
            tc.table_name,
            kcu.column_name,
            ccu.table_name AS foreign_table_name,
            ccu.column_name AS foreign_column_name
        FROM information_schema.table_constraints AS tc
        JOIN information_schema.key_column_usage AS kcu
            ON tc.constraint_name = kcu.constraint_name
        JOIN information_schema.constraint_column_usage AS ccu
            ON ccu.constraint_name = tc.constraint_name
        WHERE tc.table_name = 'user_permissions'
            AND tc.constraint_type = 'FOREIGN KEY'
    ");
    
    echo "Current foreign keys:\n";
    foreach ($fkeys as $fk) {
        echo "  - {$fk['column_name']} -> {$fk['foreign_table_name']}.{$fk['foreign_column_name']}\n";
    }
    echo "\n";
    
    // Step 2: Drop old permission_id foreign key if it references wrong table
    echo "Step 2: Fixing foreign key for permission_id...\n";
    
    // Check if we need to drop and recreate
    $wrongFk = array_filter($fkeys, function($fk) {
        return $fk['column_name'] === 'permission_id' && $fk['foreign_table_name'] === 'permissions';
    });
    
    if (!empty($wrongFk)) {
        $constraintName = $wrongFk[array_key_first($wrongFk)]['constraint_name'];
        echo "  - Dropping old foreign key: $constraintName\n";
        $supabase->query("ALTER TABLE user_permissions DROP CONSTRAINT $constraintName");
        
        echo "  - Creating new foreign key to permisos table\n";
        $supabase->query("
            ALTER TABLE user_permissions 
            ADD CONSTRAINT user_permissions_permission_id_fkey 
            FOREIGN KEY (permission_id) REFERENCES permisos(id) ON DELETE CASCADE
        ");
        echo "  ✓ Foreign key updated\n";
    } else {
        echo "  - Foreign key already correct\n";
    }
    echo "\n";
    
    // Step 3: Clear and migrate
    echo "Step 3: Migrating role permissions to users...\n";
    
    $supabase->query("DELETE FROM user_permissions");
    
    $migrate = "
    INSERT INTO user_permissions (user_id, permission_id, granted_by, notes)
    SELECT 
        u.id AS user_id,
        p.id AS permission_id,
        1 AS granted_by,
        'Migrated from role: ' || u.rol AS notes
    FROM users u
    INNER JOIN rol_permisos rp ON rp.rol = u.rol
    INNER JOIN permisos p ON p.codigo = rp.permiso_codigo
    ON CONFLICT (user_id, permission_id) DO NOTHING
    ";
    
    $supabase->query($migrate);
    echo "✓ Permissions migrated\n\n";
    
    // Step 4: Verification
    echo "Step 4: Verification...\n\n";
    
    $verification = $supabase->query("
        SELECT 
            u.id,
            u.username,
            u.rol as job_title,
            COUNT(up.permission_id) as permission_count
        FROM users u
        LEFT JOIN user_permissions up ON u.id = up.user_id
        GROUP BY u.id, u.username, u.rol
        ORDER BY u.id
    ");
    
    echo "📊 Permission Stats:\n";
    echo str_repeat("-", 70) . "\n";
    printf("%-5s | %-20s | %-15s | %s\n", "ID", "Username", "Job Title", "Perms");
    echo str_repeat("-", 70) . "\n";
    
    $totalPerms = 0;
    foreach ($verification as $row) {
        printf("%-5s | %-20s | %-15s | %s\n",
            $row['id'],
            substr($row['username'], 0, 20),
            substr($row['job_title'], 0, 15),
            $row['permission_count']
        );
        $totalPerms += (int)$row['permission_count'];
    }
    
    echo str_repeat("-", 70) . "\n";
    echo "Total permissions: $totalPerms\n\n";
    
    // Sample
    echo "Sample permissions:\n";
    echo str_repeat("-", 90) . "\n";
    
    $sample = $supabase->query("
        SELECT 
            u.username,
            p.codigo,
            p.nombre,
            up.notes
        FROM user_permissions up
        JOIN users u ON up.user_id = u.id
        JOIN permisos p ON up.permission_id = p.id
        ORDER BY u.username, p.codigo
        LIMIT 15
    ");
    
    foreach ($sample as $row) {
        echo substr($row['username'], 0, 12) . " | " . 
             substr($row['codigo'], 0, 25) . " | " . 
             substr($row['nombre'], 0, 30) . "\n";
    }
    
    echo str_repeat("-", 90) . "\n\n";
    
    echo "=== ✅ MIGRATION COMPLETED ===\n\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
