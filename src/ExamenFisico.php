<?php

namespace App;

use App\SupabaseClient;
use App\Validator;

/**
 * Clase para gestionar hallazgos del examen físico
 */
use App\BaseModel;

/**
 * Clase para gestionar hallazgos del examen físico
 */
class ExamenFisico extends BaseModel
{
    // Constructor inherited

    /**
     * Crear registro de hallazgos físicos
     */
    public function crear($datos)
    {
        try {
            $examenData = ['id_historia' => (int)$datos['id_historia']];
            
            // Lista de campos de hallazgos físicos (21 áreas)
            $campos = [
                'cabeza', 'ojos', 'oidos', 'nariz', 'boca', 'garganta', 'cuello',
                'torax', 'corazon', 'pulmon', 'abdomen', 'pelvis', 'tacto_rectal',
                'genitourinario', 'extremidades_sup', 'extremidades_inf', 'espalda',
                'piel', 'endocrino', 'sistema_nervioso'
            ];

            foreach ($campos as $campo) {
                if (!empty($datos[$campo])) {
                    $examenData[$campo] = $this->validator->sanitize($datos[$campo]);
                }
            }

            $resultado = $this->supabase->insert('examen_fisico_hallazgos', $examenData);
            return $resultado;
        } catch (\Exception $e) {
            throw new \Exception("Error al registrar examen físico: " . $e->getMessage());
        }
    }

    /**
     * Obtener hallazgos por historia clínica
     */
    public function obtenerPorHistoria($id_historia)
    {
        try {
            $resultado = $this->supabase->select('examen_fisico_hallazgos', '*', "id_historia=eq.$id_historia");
            return !empty($resultado) ? $resultado[0] : null;
        } catch (\Exception $e) {
            throw new \Exception("Error al obtener examen físico: " . $e->getMessage());
        }
    }
}
