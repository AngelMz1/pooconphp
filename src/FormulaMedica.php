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
            // Concatenar detalles en dosis para asegurar persistencia
            $dosisCompleta = $this->validator->sanitize($datos['dosis'] ?? '');
            if (!empty($datos['frecuencia'])) $dosisCompleta .= ' - Frec: ' . $this->validator->sanitize($datos['frecuencia']);
            if (!empty($datos['via_administracion'])) $dosisCompleta .= ' - Vía: ' . $this->validator->sanitize($datos['via_administracion']);
            if (!empty($datos['duracion'])) $dosisCompleta .= ' - Dur: ' . $this->validator->sanitize($datos['duracion']);
            if (!empty($datos['observaciones'])) $dosisCompleta .= ' - Obs: ' . $this->validator->sanitize($datos['observaciones']);

            $medData = [
                'id_historia' => (int)$datos['id_historia'], // Usamos id_historia para ligarlo
                'medicamento_id' => (int)$datos['medicamento_id'],
                'dosis' => $dosisCompleta,
                'cantidad' => (int)$datos['cantidad_total']
            ];

            // Insertar en formulas_medicas (Tabla de detalle según schema)
            $resultado = $this->supabase->insert('formulas_medicas', $medData);
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
            // 'formulas_medicas' ahora contiene los items.
            // Para mostrarlos, idealmente hariamos un JOIN.
            // Aqui obtenemos los items raw.
            return $this->supabase->select('formulas_medicas', '*', "id_historia=eq.$id_historia");
        } catch (\Exception $e) {
            throw new \Exception("Error al obtener fórmulas: " . $e->getMessage());
        }
    }

    /**
     * Obtener medicamentos de una fórmula (Deprecated/Alias)
     * Ahora devuelve los items de una historia
     */
    public function obtenerMedicamentos($id_historia)
    {
        // En este esquema simplificado, id_formula no se usa para agrupar items, sino id_historia.
        return $this->obtenerPorHistoria($id_historia);
    }
}
