<?php

namespace App;

use App\SupabaseClient;
use App\Validator;

/**
 * Clase para gestionar consultas médicas
 */
class Consulta
{
    private $supabase;
    private $validator;

    public function __construct(SupabaseClient $supabase)
    {
        $this->supabase = $supabase;
        $this->validator = new Validator();
    }

    /**
     * Crear nueva consulta médica
     */
    public function crear($datos)
    {
        try {
            $consultaData = [
                'id_paciente' => (int)$datos['id_paciente'],
                'medico_id' => (int)$datos['medico_id'],
                'motivo_consulta' => $this->validator->sanitize($datos['motivo_consulta']),
                'enfermedad_actual' => $this->validator->sanitize($datos['enfermedad_actual'])
            ];

            $resultado = $this->supabase->insert('consultas', $consultaData);
            return $resultado;
        } catch (\Exception $e) {
            throw new \Exception("Error al crear consulta: " . $e->getMessage());
        }
    }

    /**
     * Obtener consulta por ID
     */
    public function obtenerPorId($id)
    {
        try {
            $resultado = $this->supabase->select('consultas', '*', "id_consulta=eq.$id");
            return !empty($resultado) ? $resultado[0] : null;
        } catch (\Exception $e) {
            throw new \Exception("Error al obtener consulta: " . $e->getMessage());
        }
    }

    /**
     * Obtener consultas de un paciente
     */
    public function obtenerPorPaciente($id_paciente)
    {
        try {
            return $this->supabase->select('consultas', '*', "id_paciente=eq.$id_paciente", 'id_consulta.desc');
        } catch (\Exception $e) {
            throw new \Exception("Error al obtener consultas del paciente: " . $e->getMessage());
        }
    }

    /**
     * Obtener consultas de un médico
     */
    public function obtenerPorMedico($medico_id)
    {
        try {
            return $this->supabase->select('consultas', '*', "medico_id=eq.$medico_id", 'id_consulta.desc');
        } catch (\Exception $e) {
            throw new \Exception("Error al obtener consultas del médico: " . $e->getMessage());
        }
    }

    /**
     * Obtener consultas recientes
     */
    public function obtenerRecientes($limite = 20)
    {
        try {
            return $this->supabase->select(
                'consultas', 
                '*, pacientes:id_paciente(primer_nombre, primer_apellido), medicos:medico_id(primer_nombre, primer_apellido)', 
                "limit=$limite", 
                'id_consulta.desc'
            );
        } catch (\Exception $e) {
            throw new \Exception("Error al obtener consultas recientes: " . $e->getMessage());
        }
    }

    /**
     * Actualizar consulta
     */
    public function actualizar($id, $datos)
    {
        // Verificar que existe
        $consultaExistente = $this->obtenerPorId($id);
        if (!$consultaExistente) {
            throw new \Exception("Consulta no encontrada");
        }

        // Sanitizar datos
        $datosSanitizados = [];
        foreach ($datos as $key => $value) {
            if (is_string($value)) {
                $datosSanitizados[$key] = $this->validator->sanitize($value);
            } else {
                $datosSanitizados[$key] = $value;
            }
        }

        try {
            $resultado = $this->supabase->update('consultas', $datosSanitizados, "id_consulta=eq.$id");
            return $resultado;
        } catch (\Exception $e) {
            throw new \Exception("Error al actualizar consulta: " . $e->getMessage());
        }
    }

    /**
     * Contar consultas por paciente
     */
    public function contarPorPaciente($id_paciente)
    {
        try {
            $consultas = $this->obtenerPorPaciente($id_paciente);
            return count($consultas);
        } catch (\Exception $e) {
            throw new \Exception("Error al contar consultas: " . $e->getMessage());
        }
    }

    /**
     * Contar consultas por médico
     */
    public function contarPorMedico($medico_id)
    {
        try {
            $consultas = $this->obtenerPorMedico($medico_id);
            return count($consultas);
        } catch (\Exception $e) {
            throw new \Exception("Error al contar consultas: " . $e->getMessage());
        }
    }
}
