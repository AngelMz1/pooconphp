<?php

namespace App;

use App\SupabaseClient;
use App\Validator;

/**
 * Clase para gestionar médicos del sistema
 */
class Medico
{
    private $supabase;
    private $validator;

    public function __construct(SupabaseClient $supabase)
    {
        $this->supabase = $supabase;
        $this->validator = new Validator();
    }

    /**
     * Obtener todos los médicos
     */
    public function obtenerTodos()
    {
        try {
            return $this->supabase->select('medicos', '*', '', 'primer_apellido.asc');
        } catch (\Exception $e) {
            throw new \Exception("Error al obtener médicos: " . $e->getMessage());
        }
    }

    /**
     * Obtener médico por ID
     */
    public function obtenerPorId($id)
    {
        try {
            $resultado = $this->supabase->select('medicos', '*', "id=eq.$id");
            return !empty($resultado) ? $resultado[0] : null;
        } catch (\Exception $e) {
            throw new \Exception("Error al obtener médico: " . $e->getMessage());
        }
    }

    /**
     * Obtener médicos por especialidad
     */
    public function obtenerPorEspecialidad($especialidad_id)
    {
        try {
            return $this->supabase->select('medicos', '*', "especialidad_id=eq.$especialidad_id", 'primer_apellido.asc');
        } catch (\Exception $e) {
            throw new \Exception("Error al obtener médicos por especialidad: " . $e->getMessage());
        }
    }

    /**
     * Buscar médico por número de documento
     */
    public function buscarPorDocumento($num_documento)
    {
        try {
            $resultado = $this->supabase->select('medicos', '*', "num_documento=eq.$num_documento");
            return !empty($resultado) ? $resultado[0] : null;
        } catch (\Exception $e) {
            throw new \Exception("Error al buscar médico: " . $e->getMessage());
        }
    }

    /**
     * Buscar médico por número de registro
     */
    public function buscarPorRegistro($num_registro)
    {
        try {
            $resultado = $this->supabase->select('medicos', '*', "num_registro=eq.$num_registro");
            return !empty($resultado) ? $resultado[0] : null;
        } catch (\Exception $e) {
            throw new \Exception("Error al buscar médico por registro: " . $e->getMessage());
        }
    }

    /**
     * Crear nuevo médico
     */
    public function crear($datos)
    {
        try {
            $medicoData = [
                'num_documento' => (int)$datos['num_documento'],
                'num_registro' => (int)$datos['num_registro'],
                'primer_nombre' => $this->validator->sanitize($datos['primer_nombre']),
                'primer_apellido' => $this->validator->sanitize($datos['primer_apellido']),
                'fecha_nacimiento' => $datos['fecha_nacimiento'],
                'genero' => $this->validator->sanitize($datos['genero']),
                'telefono' => $this->validator->sanitize($datos['telefono']),
                'email' => $this->validator->sanitize($datos['email']),
                'direccion' => $this->validator->sanitize($datos['direccion'])
            ];

            // Campos opcionales
            if (!empty($datos['segundo_nombre'])) {
                $medicoData['segundo_nombre'] = $this->validator->sanitize($datos['segundo_nombre']);
            }
            if (!empty($datos['segundo_apellido'])) {
                $medicoData['segundo_apellido'] = $this->validator->sanitize($datos['segundo_apellido']);
            }
            if (!empty($datos['especialidad_id'])) {
                $medicoData['especialidad_id'] = (int)$datos['especialidad_id'];
            }

            $resultado = $this->supabase->insert('medicos', $medicoData);
            return $resultado;
        } catch (\Exception $e) {
            throw new \Exception("Error al crear médico: " . $e->getMessage());
        }
    }

    /**
     * Actualizar médico
     */
    public function actualizar($id, $datos)
    {
        // Verificar que existe
        $medicoExistente = $this->obtenerPorId($id);
        if (!$medicoExistente) {
            throw new \Exception("Médico no encontrado");
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
            $resultado = $this->supabase->update('medicos', $datosSanitizados, "id=eq.$id");
            return $resultado;
        } catch (\Exception $e) {
            throw new \Exception("Error al actualizar médico: " . $e->getMessage());
        }
    }

    /**
     * Obtener especialidades disponibles
     */
    public function obtenerEspecialidades()
    {
        try {
            return $this->supabase->select('especialidades', '*', '', 'nombre.asc');
        } catch (\Exception $e) {
            throw new \Exception("Error al obtener especialidades: " . $e->getMessage());
        }
    }

    /**
     * Contar total de médicos
     */
    public function contarTotal()
    {
        try {
            $medicos = $this->obtenerTodos();
            return count($medicos);
        } catch (\Exception $e) {
            throw new \Exception("Error al contar médicos: " . $e->getMessage());
        }
    }

    /**
     * Obtener nombre completo del médico
     */
    public function getNombreCompleto($medico)
    {
        $nombre = $medico['primer_nombre'];
        if (!empty($medico['segundo_nombre'])) {
            $nombre .= ' ' . $medico['segundo_nombre'];
        }
        $nombre .= ' ' . $medico['primer_apellido'];
        if (!empty($medico['segundo_apellido'])) {
            $nombre .= ' ' . $medico['segundo_apellido'];
        }
        return $nombre;
    }
}
