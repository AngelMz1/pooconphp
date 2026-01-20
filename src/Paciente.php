<?php

namespace App;

use App\SupabaseClient;
use App\Validator;

use App\BaseModel;

class Paciente extends BaseModel
{
    // Constructor inherited

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
     * Buscar pacientes por nombre, apellidos o documento
     */
    public function buscarPorNombre($termino)
    {
        try {
            $termino = $this->validator->sanitize($termino);
            // Buscar en nombres, apellidos y documento
            $filter = "or=(primer_nombre.ilike.*{$termino}*,segundo_nombre.ilike.*{$termino}*,primer_apellido.ilike.*{$termino}*,segundo_apellido.ilike.*{$termino}*,documento_id.ilike.*{$termino}*)";
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
        
        // Campos que deben ser NULL si están vacíos (campos de fecha y opcionales)
        $camposNullSiVacio = [
            'fecha_nacimiento', 
            'segundo_nombre', 
            'segundo_apellido',
            'telefono',
            'email',
            'direccion',
            'estrato',
            'sexo_id',
            'eps_id',
            'regimen_id',
            'ciudad_id',
            'barrio_id',
            'etnia_id',
            'escolaridad_id',
            'gs_rh_id',
            'estado_civil_id',
            'acudiente_id',
            'lugar_nacimiento',
            'ocupacion',
            'empresa',
            'grupo_poblacional'
        ];
        
        foreach ($datos as $key => $value) {
            // Si el valor es null, no incluirlo
            if ($value === null) {
                continue;
            }
            
            // Si es un campo que debe ser null cuando está vacío
            if (in_array($key, $camposNullSiVacio)) {
                if ($value === '' || $value === '0' && !in_array($key, ['estrato'])) {
                    // No incluir en el array para que no se envíe
                    continue;
                }
            }
            
            // Sanitizar strings
            if (is_string($value)) {
                // Si después de trim está vacío y es un campo nullable, no incluirlo
                $trimmed = trim($value);
                if ($trimmed === '' && in_array($key, $camposNullSiVacio)) {
                    continue;
                }
                $sanitizados[$key] = $this->validator->sanitize($trimmed);
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