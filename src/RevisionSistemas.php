<?php

namespace App;

use App\SupabaseClient;
use App\Validator;

/**
 * Clase para gestionar revisión por sistemas
 */
class RevisionSistemas
{
    private $supabase;
    private $validator;

    public function __construct(SupabaseClient $supabase)
    {
        $this->supabase = $supabase;
        $this->validator = new Validator();
    }

    /**
     * Crear registro de revisión por sistemas
     */
    public function crear($datos)
    {
        try {
            $sysData = ['id_historia' => (int)$datos['id_historia']];
            
            // Campos de revisión por sistemas
            $campos = [
                'respiratorio', 'organos_sentidos', 'cardiovascular', 
                'gastrointestinal', 'genitourinario', 'neurologico', 
                'piel_y_anexos', 'osteomuscular', 'endocrino', 
                'psicosocial', 'linfatico', 'otro'
            ];

            foreach ($campos as $campo) {
                if (!empty($datos[$campo])) {
                    $sysData[$campo] = $this->validator->sanitize($datos[$campo]);
                }
            }

            $resultado = $this->supabase->insert('revision_por_sistemas', $sysData);
            return $resultado;
        } catch (\Exception $e) {
            throw new \Exception("Error al registrar revisión por sistemas: " . $e->getMessage());
        }
    }

    /**
     * Obtener revisión por historia clínica
     */
    public function obtenerPorHistoria($id_historia)
    {
        try {
            $resultado = $this->supabase->select('revision_por_sistemas', '*', "id_historia=eq.$id_historia");
            return !empty($resultado) ? $resultado[0] : null;
        } catch (\Exception $e) {
            throw new \Exception("Error al obtener revisión por sistemas: " . $e->getMessage());
        }
    }
}
