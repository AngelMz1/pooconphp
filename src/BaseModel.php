<?php

namespace App;

use App\SupabaseClient;
use App\Validator;

/**
 * Clase Base para todos los modelos
 */
abstract class BaseModel
{
    protected $supabase;
    protected $validator;

    public function __construct(?SupabaseClient $supabase = null)
    {
        if (!$supabase) {
            // Load env if not loaded (generic check)
            if (!isset($_ENV['SUPABASE_URL'])) {
                $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
                try {
                   $dotenv->safeLoad();
                } catch (\Exception $e) { }
            }
            
            $url = $_ENV['SUPABASE_URL'] ?? $_SERVER['SUPABASE_URL'] ?? getenv('SUPABASE_URL');
            $key = $_ENV['SUPABASE_KEY'] ?? $_SERVER['SUPABASE_KEY'] ?? getenv('SUPABASE_KEY');
            
            if ($url && $key) {
                $supabase = new SupabaseClient($url, $key);
            }
        }
        $this->supabase = $supabase;
        $this->validator = new Validator();
    }
}
