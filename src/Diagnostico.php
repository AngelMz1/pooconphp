<?php

namespace App;

use App\SupabaseClient;
use App\Validator;

/**
 * Clase para gestionar diagnósticos con códigos CIE-10
 */
class Diagnostico
{
    private $supabase;
    private $validator;

    public function __construct(SupabaseClient $supabase)
    {
        $this->supabase = $supabase;
        $this->validator = new Validator();
    }

    /**
     * Crear nuevo diagnóstico
     */
    public function crear($datos)
    {
        try {
            $diagnosticoData = [
                'id_consulta' => (int)$datos['id_consulta'],
                'tipo_dx' => $this->validator->sanitize($datos['tipo_dx'] ?? 'Principal')
            ];

            // CIE-10 principal
            if (!empty($datos['id_cie10_principal'])) {
                $diagnosticoData['id_cie10_principal'] = (int)$datos['id_cie10_principal'];
            }

            // CIE-10 relacionado (opcional)
            if (!empty($datos['id_cie10_relacionado'])) {
                $diagnosticoData['id_cie10_relacionado'] = (int)$datos['id_cie10_relacionado'];
            }

            $resultado = $this->supabase->insert('diagnosticos', $diagnosticoData);
            return $resultado;
        } catch (\Exception $e) {
            throw new \Exception("Error al crear diagnóstico: " . $e->getMessage());
        }
    }

    /**
     * Obtener diagnósticos de una consulta
     */
    public function obtenerPorConsulta($id_consulta)
    {
        try {
            return $this->supabase->select('diagnosticos', '*', "id_consulta=eq.$id_consulta");
        } catch (\Exception $e) {
            throw new \Exception("Error al obtener diagnósticos: " . $e->getMessage());
        }
    }

    /**
     * Buscar códigos CIE-10 por código o descripción
     */
    public function buscarCIE10($termino)
    {
        try {
            $termino = $this->validator->sanitize($termino);
            // Buscar por código o descripción
            $filter = "or=(codigo.ilike.*{$termino}*,descripcion.ilike.*{$termino}*)";
            return $this->supabase->select('cie10', '*', $filter, 'codigo.asc', 50);
        } catch (\Exception $e) {
            throw new \Exception("Error al buscar CIE-10: " . $e->getMessage());
        }
    }

    /**
     * Obtener código CIE-10 por ID
     */
    public function obtenerCIE10PorId($id)
    {
        try {
            $resultado = $this->supabase->select('cie10', '*', "id=eq.$id");
            return !empty($resultado) ? $resultado[0] : null;
        } catch (\Exception $e) {
            throw new \Exception("Error al obtener CIE-10: " . $e->getMessage());
        }
    }

    /**
     * Obtener  código CIE-10 por código
     */
    public function obtenerCIE10PorCodigo($codigo)
    {
        try {
            $codigo = $this->validator->sanitize($codigo);
            $resultado = $this->supabase->select('cie10', '*', "codigo=eq.$codigo");
            return !empty($resultado) ? $resultado[0] : null;
        } catch (\Exception $e) {
            throw new \Exception("Error al obtener CIE-10 por código: " . $e->getMessage());
        }
    }

    /**
     * Actualizar diagnóstico
     */
    public function actualizar($id_diag, $datos)
    {
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
            $resultado = $this->supabase->update('diagnosticos', $datosSanitizados, "id_diag=eq.$id_diag");
            return $resultado;
        } catch (\Exception $e) {
            throw new \Exception("Error al actualizar diagnóstico: " . $e->getMessage());
        }
    }

    /**
     * Obtener CIE-10 más utilizados
     */
    public function obtenerCIE10MasUtilizados($limite = 20)
    {
        try {
            // Esto requeriría una consulta más compleja con conteos
            // Por ahora, devolvemos los primeros registros
            return $this->supabase->select('cie10', '*', "limit=$limite", 'codigo.asc');
        } catch (\Exception $e) {
            throw new \Exception("Error al obtener CIE-10 más utilizados: " . $e->getMessage());
        }
    }
}
