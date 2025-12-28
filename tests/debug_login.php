<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\SupabaseClient;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();
$supabase = new SupabaseClient($_ENV['SUPABASE_URL'], $_ENV['SUPABASE_KEY']);

$test_users = [
    'admin' => 'admin',
    'medico1' => 'medico123',
    'cajero1' => 'cajero123'
];

echo "<h1>Debug Login</h1>";

foreach ($test_users as $username => $password) {
    echo "<hr>Testing <strong>$username</strong> (Pass: $password)<br>";
    
    $users = $supabase->select('users', '*', "username.eq.$username");
    
    if (empty($users)) {
        echo "❌ Usuario no encontrado en DB.<br>";
        continue;
    }
    
    $db_user = $users[0];
    $hash = $db_user['password_hash'];
    
    echo "Hash en DB: " . substr($hash, 0, 15) . "...<br>";
    
    if (password_verify($password, $hash)) {
        echo "<span style='color:green'>✅ Password OK!</span><br>";
    } else {
        echo "<span style='color:red'>❌ Password Incorrecto!</span><br>";
        echo "Hash generado ahora mismo con esa pass: " . password_hash($password, PASSWORD_DEFAULT) . "<br>";
    }
}
