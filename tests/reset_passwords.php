<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\SupabaseClient;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();
$supabase = new SupabaseClient($_ENV['SUPABASE_URL'], $_ENV['SUPABASE_KEY']);

echo "<h1>Restableciendo Contraseñas...</h1>";

$updates = [
    'medico1' => 'medico123',
    'cajero1' => 'cajero123',
    'admin'   => 'admin'
];

foreach ($updates as $username => $new_pass) {
    try {
        $hash = password_hash($new_pass, PASSWORD_DEFAULT);
        
        // Update user
        $supabase->update('users', ['password_hash' => $hash], "username.eq.$username");
        
        echo "✅ Contraseña para <strong>$username</strong> actualizada a: <code>$new_pass</code><br>";
    } catch (Exception $e) {
        echo "❌ Error con $username: " . $e->getMessage() . "<br>";
    }
}

echo "<br><a href='../views/login.php'>Ir al Login</a>";
