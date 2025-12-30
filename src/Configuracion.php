<?php

namespace App;

use App\SupabaseClient;
use App\BaseModel;

class Configuracion extends BaseModel {
    private $table = 'configuracion';

    public function __construct($supabase = null) {
        if (!$supabase) {
            // Inicializar cliente si no se pasa (caso header.php)
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
        parent::__construct($supabase);
    }

    public function obtenerConfiguracion() {
        try {
            // Asumimos que siempre usamos el ID 1 para la configuración global
            $data = $this->supabase->select($this->table, '*', 'id=eq.1');
            if (!empty($data) && isset($data[0])) {
                return $data[0];
            }
        } catch (\Exception $e) {
            // Si la tabla no existe o hay error de conexión, usamos defaults
            // error_log("Error fetching config: " . $e->getMessage());
        }
        
        // Retornar defaults si no hay nada en BD o falló
        return [
            'nombre_institucion' => 'Mi Centro Médico',
            'color_principal' => '#0d6efd',
            'color_secundario' => '#6c757d',
            'logo_url' => ''
        ];
    }



    public function actualizarConfiguracion($nombre, $color_primario, $color_secundario, $logo_url = null) {
        $datos = [
            'nombre_institucion' => $nombre,
            'color_principal' => $color_primario,
            'color_secundario' => $color_secundario,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if ($logo_url) {
            $datos['logo_url'] = $logo_url;
        }

        // Intentar actualizar ID 1
        $current = $this->obtenerConfiguracion();
        // Si ya existe (nombre no es default o id existe), update. 
        // Supabase update retorna los datos actualizados usualmente.
        
        // Simplemente intentamos update
        $resultado = $this->supabase->update($this->table, $datos, 'id=eq.1');
        
        return $resultado;
    }
}
