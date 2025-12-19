<?php

namespace App;

use App\SupabaseClient;
use App\Validator;

class Paciente
{
    private $supabase;
    private $validator;

    public function __construct(SupabaseClient $supabase)
    {
        $this->supabase = $supabase;
        $this->validator = new Validator();
    }

    /**
     * Obtener todos los pacientes ordenados por nombre
     */
    public function obtenerTodos()
    {
        try {
            return $this->supabase->select('pacientes', '*', '', 'primer_nombre.asc');
        } catch (\Exception $e) {
            throw new \Exception("Error al obtener pacientes: " . $e->getMessage());
        }
    }

    /**
     * Obtener paciente por ID
     */
    public function obtenerPorId($id)
    {
        try {
            $resultado = $this->supabase->select('pacientes', '*', "id_paciente=eq.$id");
            return !empty($resultado) ? $resultado[0] : null;
        } catch (\Exception $e) {
            throw new \Exception("Error al obtener paciente: " . $e->getMessage());
        }
    }

    /**
     * Buscar paciente por documento
     */
    public function buscarPorDocumento($documento)
    {
        try {
            $resultado = $this->supabase->select('pacientes', '*', "documento_id=eq.$documento");
            return !empty($resultado) ? $resultado[0] : null;
        } catch (\Exception $e) {
            throw new \Exception("Error al buscar paciente: " . $e->getMessage());
        }
    }

    /**
     * Crear nuevo paciente con validación
     */
    public function crear($datos)
    {
        // Validar datos
        if (!$this->validator->validarPaciente($datos)) {
            throw new \Exception("Datos inválidos: " . implode(", ", $this->validator->getErrors()));
        }

        // Sanitizar datos
        $datosSanitizados = $this->sanitizarDatos($datos);

        try {
            $resultado = $this->supabase->insert('pacientes', $datosSanitizados);
            return $resultado;
        } catch (\Exception $e) {
            throw new \Exception("Error al crear paciente: " . $e->getMessage());
        }
    }

    /**
     * Actualizar paciente existente
     */
    public function actualizar($id, $datos)
    {
        // Verificar que el paciente existe
        $pacienteExistente = $this->obtenerPorId($id);
        if (!$pacienteExistente) {
            throw new \Exception("Paciente no encontrado");
        }

        // Validar solo los campos proporcionados
        $datosSanitizados = $this->sanitizarDatos($datos);

        try {
            $resultado = $this->supabase->update('pacientes', $datosSanitizados, "id_paciente=eq.$id");
            return $resultado;
        } catch (\Exception $e) {
            throw new \Exception("Error al actualizar paciente: " . $e->getMessage());
        }
    }

    /**
     * Eliminar paciente
     */
    public function eliminar($id)
    {
        try {
            return $this->supabase->delete('pacientes', "id_paciente=eq.$id");
        } catch (\Exception $e) {
            throw new \Exception("Error al eliminar paciente: " . $e->getMessage());
        }
    }

    /**
     * Buscar pacientes por nombre
     */
    public function buscarPorNombre($nombre)
    {
        try {
            $nombre = $this->validator->sanitize($nombre);
            // Buscar en primer nombre o primer apellido
            $filter = "or=(primer_nombre.ilike.*{$nombre}*,primer_apellido.ilike.*{$nombre}*)";
            return $this->supabase->select('pacientes', '*', $filter, 'primer_nombre.asc');
        } catch (\Exception $e) {
            throw new \Exception("Error al buscar pacientes: " . $e->getMessage());
        }
    }

    /**
     * Obtener pacientes por estrato
     */
    public function obtenerPorEstrato($estrato)
    {
        if (!$this->validator->estrato($estrato)) {
            throw new \Exception("Estrato inválido");
        }

        try {
            return $this->supabase->select('pacientes', '*', "estrato=eq.$estrato", 'primer_nombre.asc');
        } catch (\Exception $e) {
            throw new \Exception("Error al obtener pacientes por estrato: " . $e->getMessage());
        }
    }

    /**
     * Sanitizar datos del paciente
     */
    private function sanitizarDatos($datos)
    {
        $sanitizados = [];
        
        foreach ($datos as $key => $value) {
            if (is_string($value)) {
                $sanitizados[$key] = $this->validator->sanitize($value);
            } else {
                $sanitizados[$key] = $value;
            }
        }
        
        return $sanitizados;
    }

    /**
     * Contar total de pacientes
     */
    public function contarTotal()
    {
        try {
            $pacientes = $this->obtenerTodos();
            return count($pacientes);
        } catch (\Exception $e) {
            throw new \Exception("Error al contar pacientes: " . $e->getMessage());
        }
    }
}