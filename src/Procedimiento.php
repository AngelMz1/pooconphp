<?php

namespace App;

use App\SupabaseClient;
use App\Validator;

/**
 * Clase para gestionar procedimientos y solicitudes
 */
class Procedimiento
{
    private $supabase;
    private $validator;

    public function __construct(SupabaseClient $supabase)
    {
        $this->supabase = $supabase;
        $this->validator = new Validator();
    }

    /**
     * Crear orden de procedimiento/examen
     */
    public function crear($datos)
    {
        try {
            $procData = [
                'id_historia' => (int)$datos['id_historia'],
                'codigo_cups' => $this->validator->sanitize($datos['codigo_cups']),
                'nombre_procedimiento' => $this->validator->sanitize($datos['nombre_procedimiento']),
                'cantidad' => (int)($datos['cantidad'] ?? 1),
                'justificacion' => $this->validator->sanitize($datos['justificacion'] ?? '')
            ];

            if (!empty($datos['observaciones'])) {
                $procData['observaciones'] = $this->validator->sanitize($datos['observaciones']);
            }

            $resultado = $this->supabase->insert('procedimientos', $procData);
            return $resultado;
        } catch (\Exception $e) {
            throw new \Exception("Error al crear procedimiento: " . $e->getMessage());
        }
    }

    /**
     * Crear incapacidad
     */
    public function crearIncapacidad($datos)
    {
        try {
            $incapData = [
                'id_historia' => (int)$datos['id_historia'],
                'dias' => (int)$datos['dias'],
                'fecha_inicio' => $datos['fecha_inicio'],
                'fecha_fin' => $datos['fecha_fin'],
                'diagnostico_cie10' => $this->validator->sanitize($datos['diagnostico_cie10']),
                'tipo' => $this->validator->sanitize($datos['tipo'] ?? 'Enfermedad General'),
                'observaciones' => $this->validator->sanitize($datos['observaciones'] ?? '')
            ];

            $resultado = $this->supabase->insert('incapacidades', $incapData);
            return $resultado;
        } catch (\Exception $e) {
            throw new \Exception("Error al crear incapacidad: " . $e->getMessage());
        }
    }

    /**
     * Obtener procedimientos por historia
     */
    public function obtenerPorHistoria($id_historia)
    {
        try {
            return $this->supabase->select('procedimientos', '*', "id_historia=eq.$id_historia");
        } catch (\Exception $e) {
            throw new \Exception("Error al obtener procedimientos: " . $e->getMessage());
        }
    }

    /**
     * Obtener incapacidades por historia
     */
    public function obtenerIncapacidadesPorHistoria($id_historia)
    {
        try {
            return $this->supabase->select('incapacidades', '*', "id_historia=eq.$id_historia");
        } catch (\Exception $e) {
            throw new \Exception("Error al obtener incapacidades: " . $e->getMessage());
        }
    }
}
