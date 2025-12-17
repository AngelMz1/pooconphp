<?php

namespace App;

use App\SupabaseClient;

class HistoriaClinica
{
    private $supabase;

    public function __construct(SupabaseClient $supabase)
    {
        $this->supabase = $supabase;
    }

    public function crear($datos)
    {
        try {
            // Validar datos requeridos
            if (empty($datos['id_paciente'])) {
                throw new \Exception("ID del paciente es requerido");
            }

            // Preparar datos para insertar
            $historiaData = [
                'id_paciente' => $datos['id_paciente'],
                'motivo_consulta' => $datos['motivo_consulta'] ?? '',
                'analisis_plan' => $datos['analisis_plan'] ?? '',
                'diagnostico' => $datos['diagnostico'] ?? '',
                'tratamiento' => $datos['tratamiento'] ?? '',
                'observaciones' => $datos['observaciones'] ?? ''
            ];

            // Si se proporciona fecha de egreso
            if (!empty($datos['fecha_egreso'])) {
                $historiaData['fecha_egreso'] = $datos['fecha_egreso'];
            }

            $resultado = $this->supabase->insert('historias_clinicas', $historiaData);
            return $resultado;

        } catch (\Exception $e) {
            throw new \Exception("Error al crear historia clínica: " . $e->getMessage());
        }
    }

    public function obtenerPorPaciente($idPaciente)
    {
        try {
            return $this->supabase->select(
                'historias_clinicas', 
                '*', 
                "id_paciente=eq.$idPaciente",
                'fecha_ingreso.desc'
            );
        } catch (\Exception $e) {
            throw new \Exception("Error al obtener historias: " . $e->getMessage());
        }
    }

    public function actualizar($idHistoria, $datos)
    {
        try {
            $resultado = $this->supabase->update(
                'historias_clinicas', 
                $datos, 
                "id_historia=eq.$idHistoria"
            );
            return $resultado;
        } catch (\Exception $e) {
            throw new \Exception("Error al actualizar historia: " . $e->getMessage());
        }
    }
}