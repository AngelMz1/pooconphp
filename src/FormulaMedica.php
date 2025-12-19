<?php

namespace App;

use App\SupabaseClient;
use App\Validator;

/**
 * Clase para gestionar fórmulas médicas
 */
class FormulaMedica
{
    private $supabase;
    private $validator;

    public function __construct(SupabaseClient $supabase)
    {
        $this->supabase = $supabase;
        $this->validator = new Validator();
    }

    /**
     * Crear encabezado de fórmula médica
     */
    public function crear($datos)
    {
        try {
            $formulaData = [
                'id_historia' => (int)$datos['id_historia'],
                'tipo_formula' => $this->validator->sanitize($datos['tipo_formula'] ?? 'Ambulatoria')
            ];

            if (!empty($datos['vigencia_dias'])) {
                $formulaData['vigencia_dias'] = (int)$datos['vigencia_dias'];
            }
            
            if (!empty($datos['recomendaciones'])) {
                $formulaData['recomendaciones'] = $this->validator->sanitize($datos['recomendaciones']);
            }

            $resultado = $this->supabase->insert('formulas_medicas', $formulaData);
            return $resultado;
        } catch (\Exception $e) {
            throw new \Exception("Error al crear fórmula médica: " . $e->getMessage());
        }
    }

    /**
     * Agregar medicamento a una fórmula
     */
    public function agregarMedicamento($datos)
    {
        try {
            $medData = [
                'id_formula' => (int)$datos['id_formula'],
                'nombre_medicamento' => $this->validator->sanitize($datos['nombre_medicamento']),
                'presentacion' => $this->validator->sanitize($datos['presentacion']),
                'dosis' => $this->validator->sanitize($datos['dosis']),
                'frecuencia' => $this->validator->sanitize($datos['frecuencia']),
                'via_administracion' => $this->validator->sanitize($datos['via_administracion']),
                'duracion' => $this->validator->sanitize($datos['duracion']),
                'cantidad_total' => (int)$datos['cantidad_total']
            ];

            if (!empty($datos['observaciones'])) {
                $medData['observaciones'] = $this->validator->sanitize($datos['observaciones']);
            }

            $resultado = $this->supabase->insert('medicamentos', $medData);
            return $resultado;
        } catch (\Exception $e) {
            throw new \Exception("Error al agregar medicamento: " . $e->getMessage());
        }
    }

    /**
     * Obtener fórmulas por historia
     */
    public function obtenerPorHistoria($id_historia)
    {
        try {
            return $this->supabase->select('formulas_medicas', '*', "id_historia=eq.$id_historia");
        } catch (\Exception $e) {
            throw new \Exception("Error al obtener fórmulas: " . $e->getMessage());
        }
    }

    /**
     * Obtener medicamentos de una fórmula
     */
    public function obtenerMedicamentos($id_formula)
    {
        try {
            return $this->supabase->select('medicamentos', '*', "id_formula=eq.$id_formula");
        } catch (\Exception $e) {
            throw new \Exception("Error al obtener medicamentos: " . $e->getMessage());
        }
    }
}
