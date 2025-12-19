<?php

namespace App;

use App\SupabaseClient;
use App\Validator;

/**
 * Clase para gestionar plan de manejo
 */
class PlanManejo
{
    private $supabase;
    private $validator;

    public function __construct(SupabaseClient $supabase)
    {
        $this->supabase = $supabase;
        $this->validator = new Validator();
    }

    /**
     * Crear plan de manejo
     */
    public function crear($datos)
    {
        try {
            $planData = [
                'id_historia' => (int)$datos['id_historia'],
                'descripcion' => $this->validator->sanitize($datos['descripcion']),
                'tipo_plan' => $this->validator->sanitize($datos['tipo_plan'] ?? 'Ambulatorio')
            ];

            // Recomendaciones generales
            $recomendaciones = [];
            if (!empty($datos['dieta'])) $recomendaciones[] = "Dieta: " . $datos['dieta'];
            if (!empty($datos['cuidados'])) $recomendaciones[] = "Cuidados: " . $datos['cuidados'];
            if (!empty($datos['signos_alarma'])) $recomendaciones[] = "Signos de alarma: " . $datos['signos_alarma'];
            
            if (!empty($recomendaciones)) {
                $planData['recomendaciones'] = implode("\n", $recomendaciones);
            }

            // Próximo control
            if (!empty($datos['proximo_control'])) {
                $planData['proximo_control'] = $datos['proximo_control']; // Fecha
            }

            $resultado = $this->supabase->insert('plan_manejo', $planData);
            return $resultado;
        } catch (\Exception $e) {
            throw new \Exception("Error al crear plan de manejo: " . $e->getMessage());
        }
    }

    /**
     * Obtener plan por historia
     */
    public function obtenerPorHistoria($id_historia)
    {
        try {
            $resultado = $this->supabase->select('plan_manejo', '*', "id_historia=eq.$id_historia");
            return !empty($resultado) ? $resultado[0] : null;
        } catch (\Exception $e) {
            throw new \Exception("Error al obtener plan de manejo: " . $e->getMessage());
        }
    }
}
