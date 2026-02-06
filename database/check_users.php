<?php
require_once __DIR__ . '/../vendor/autoload.php';
use App\DatabaseFactory;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
try { $dotenv->load(); } catch (Exception $e) {}

try {
    $db = DatabaseFactory::create();
    $users = $db->select('users', 'id, username, rol, active');
    
    echo "Total Users: " . count($users) . "\n";
    foreach ($users as $u) {
        echo "ID: {$u['id']} | User: {$u['username']} | Role: {$u['rol']} | Active: " . ($u['active'] ? 'YES' : 'NO') . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
