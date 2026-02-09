<?php

namespace App;

use App\SupabaseClient;
use App\PostgreSQLClient;
use Dotenv\Dotenv;

/**
 * Factory para crear instancias de clientes de base de datos
 * Soporta modo dual: PostgreSQL Local o Supabase
 */
class DatabaseFactory
{
    private static $instance = null;
    
    /**
     * Crear cliente de base de datos según configuración
     * 
     * @param string|null $dotenvPath Ruta al archivo .env
     * @return \App\Interfaces\DatabaseAdapterInterface
     */
    public static function create($dotenvPath = null)
    {
        $dotenvPath = $dotenvPath ?? dirname(__DIR__);
        
        // Cargar variables de entorno si no están cargadas
        if (!isset($_ENV['DB_MODE']) && !isset($_ENV['SUPABASE_URL'])) {
            self::loadEnvironment($dotenvPath);
        }
        
        // Determinar modo de base de datos
        // Prioridad: DB_MODE > DB_CONNECTION > default (local)
        $mode = $_ENV['DB_MODE'] ?? $_ENV['DB_CONNECTION'] ?? 'local';
        
        // Normalizar modo
        if (in_array($mode, ['pgsql', 'postgres', 'postgresql'])) {
            $mode = 'local';
        }
        
        if ($mode === 'local') {
            return self::createPostgreSQLClient();
        } else {
            return self::createSupabaseClient();
        }
    }
    
    /**
     * Crear cliente PostgreSQL local
     */
    private static function createPostgreSQLClient()
    {
        if (self::$instance === null || !self::$instance instanceof PostgreSQLClient) {
            $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
            $database = $_ENV['DB_DATABASE'] ?? 'pooconphp';
            $username = $_ENV['DB_USERNAME'] ?? 'postgres';
            $password = $_ENV['DB_PASSWORD'] ?? '';
            $port = (int)($_ENV['DB_PORT'] ?? 5432);
            
            self::$instance = new PostgreSQLClient($host, $database, $username, $password, $port);
        }
        
        return self::$instance;
    }
    
    /**
     * Crear cliente Supabase
     */
    private static function createSupabaseClient()
    {
        if (self::$instance === null || !self::$instance instanceof SupabaseClient) {
            $url = $_ENV['SUPABASE_URL'] ?? '';
            $key = $_ENV['SUPABASE_KEY'] ?? '';
            
            if (empty($url) || empty($key)) {
                throw new \Exception("Supabase credentials missing. Please check your .env file.");
            }
            
            self::$instance = new SupabaseClient($url, $key);
        }
        
        return self::$instance;
    }
    
    /**
     * Cargar variables de entorno
     */
    private static function loadEnvironment($path)
    {
        try {
            $dotenv = Dotenv::createImmutable($path);
            $dotenv->safeLoad();
        } catch (\Exception $e) {
            // Silenciar errores si el archivo .env no existe
            // Las variables ya pueden estar cargadas globalmente
        }
    }
    
    /**
     * Resetear instancia singleton (útil para testing)
     */
    public static function reset()
    {
        self::$instance = null;
    }
    
    /**
     * Obtener modo actual de base de datos
     */
    public static function getCurrentMode()
    {
        $mode = $_ENV['DB_MODE'] ?? $_ENV['DB_CONNECTION'] ?? 'local';
        
        if (in_array($mode, ['pgsql', 'postgres', 'postgresql'])) {
            return 'local';
        }
        
        return $mode;
    }
}
