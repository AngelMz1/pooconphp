<?php
require_once __DIR__ . '/../vendor/autoload.php';
use App\DatabaseFactory;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
try { $dotenv->load(); } catch (Exception $e) {}

try {
    $db = DatabaseFactory::create();
    
    $newPass = 'admin123';
    $hash = password_hash($newPass, PASSWORD_BCRYPT);
    
    // Update admin user (id=1 or username='admin')
    $db->update('users', ['password_hash' => $hash], "username=eq.admin");
    
    echo "Password for 'admin' reset to: $newPass\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
