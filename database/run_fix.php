<?php
require_once __DIR__ . '/../vendor/autoload.php';
use App\DatabaseFactory;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
try { $dotenv->load(); } catch (Exception $e) {}

try {
    $db = DatabaseFactory::create();
    // We need to run raw SQL. LocalPostgresAdapter doesn't expose raw exec publically usually, 
    // but the 'query' method I added to the interface (and need to add to Adapter) would help.
    // Wait, I haven't added 'query' to LocalPostgresAdapter yet! 
    // I added it to SupabaseClient.
    // I need to add 'query' method to LocalPostgresAdapter.php first.
    
    // BUT, I can just instantiate PDO here directly using env vars like the adapter does.
    
    $host = $_ENV['DB_HOST'];
    $name = $_ENV['DB_DATABASE'];
    $user = $_ENV['DB_USERNAME'];
    $pass = $_ENV['DB_PASSWORD'];
    $port = $_ENV['DB_PORT'] ?? 5432;
    
    $dsn = "pgsql:host=$host;port=$port;dbname=$name;";
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    
    $sql = file_get_contents(__DIR__ . '/fix_citas_status.sql');
    
    echo "Running fix_citas_status.sql...\n";
    $pdo->exec($sql);
    echo "Success!\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
