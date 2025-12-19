<?php

namespace App;

use App\SupabaseClient;
use App\Validator;

/**
 * Clase para gestionar signos vitales
 */
class SignosVitales
{
    private $supabase;
    private $validator;

    public function __construct(SupabaseClient $supabase)
    {
        $this->supabase = $supabase;
        $this->validator = new Validator();
    }

    /**
     * Crear registro de signos vitales
     */
    public function crear($datos)
    {
        try {
            $signosData = [
                'id_historia' => (int)$datos['id_historia'],
                'ta' => $this->validator->sanitize($datos['ta']), // Tensión Arterial
                'pulso' => (int)$datos['pulso'],
                'f_res' => (int)$datos['f_res'], // Frecuencia Respiratoria
                'temperatura' => (float)$datos['temperatura'],
                'peso' => (float)$datos['peso'],
                'talla' => (float)$datos['talla']
            ];

            // Campos opcionales calculados o adicionales
            if (!empty($datos['pam'])) $signosData['pam'] = (int)$datos['pam']; // Presión Arterial Media
            if (!empty($datos['pc'])) $signosData['pc'] = (float)$datos['pc']; // Perímetro Cefálico
            if (!empty($datos['sp02'])) $signosData['sp02'] = (float)$datos['sp02']; // Saturación O2
            if (!empty($datos['rcv'])) $signosData['rcv'] = (float)$datos['rcv']; // Riesgo Cardiovascular

            $resultado = $this->supabase->insert('signos_vitales', $signosData);
            return $resultado;
        } catch (\Exception $e) {
            throw new \Exception("Error al registrar signos vitales: " . $e->getMessage());
        }
    }

    /**
     * Obtener signos vitales por historia clínica
     */
    public function obtenerPorHistoria($id_historia)
    {
        try {
            $resultado = $this->supabase->select('signos_vitales', '*', "id_historia=eq.$id_historia");
            return !empty($resultado) ? $resultado[0] : null;
        } catch (\Exception $e) {
            throw new \Exception("Error al obtener signos vitales: " . $e->getMessage());
        }
    }

    /**
     * Actualizar signos vitales
     */
    public function actualizar($id, $datos)
    {
        $datosSanitizados = [];
        foreach ($datos as $key => $value) {
            $datosSanitizados[$key] = is_string($value) ? $this->validator->sanitize($value) : $value;
        }

        try {
            return $this->supabase->update('signos_vitales', $datosSanitizados, "id=eq.$id");
        } catch (\Exception $e) {
            throw new \Exception("Error al actualizar signos vitales: " . $e->getMessage());
        }
    }
}
