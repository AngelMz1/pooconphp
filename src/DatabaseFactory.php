<?php

namespace App;

use App\SupabaseClient;
use App\LocalPostgresAdapter;
use App\SupabaseAdapter;
use Dotenv\Dotenv;

class DatabaseFactory
{
    /**
     * Create a database connection based on environment
     * @return \App\Interfaces\DatabaseAdapterInterface
     */
    public static function create($dotenvPath = __DIR__ . '/..')
    {
        // Load env if not loaded (check specific key)
        if (!isset($_ENV['DB_CONNECTION']) && !isset($_ENV['SUPABASE_URL'])) {
             $dotenv = Dotenv::createImmutable($dotenvPath);
             try {
                $dotenv->safeLoad();
             } catch (\Exception $e) {}
        }
        
        $mode = $_ENV['DB_CONNECTION'] ?? 'supabase'; // Default to supabase if not set
        
        if ($mode === 'pgsql' || $mode === 'postgres' || $mode === 'local') {
            $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
            $port = $_ENV['DB_PORT'] ?? '5432';
            $db   = $_ENV['DB_DATABASE'] ?? 'pooconphp_local';
            $user = $_ENV['DB_USERNAME'] ?? 'postgres';
            $pass = $_ENV['DB_PASSWORD'] ?? '';
            
            return new LocalPostgresAdapter($host, $db, $user, $pass, $port);
        } else {
            // Fallback to Supabase
            $url = $_ENV['SUPABASE_URL'] ?? '';
            $key = $_ENV['SUPABASE_KEY'] ?? '';
            
            if (empty($url) || empty($key)) {
                // If called from a subfolder, maybe env didn't load right?
                // But generally we expect env to be loaded.
                throw new \Exception("Supabase credentials missing and DB_CONNECTION not set to local.");
            }
            
            $client = new SupabaseClient($url, $key);
            return new SupabaseAdapter($client);
        }
    }
}
