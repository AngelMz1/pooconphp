<?php

namespace App;

use App\SupabaseClient;
use App\Validator;

use App\BaseModel;

/**
 * Clase para gestionar historias clínicas
 */
class HistoriaClinica extends BaseModel
{
    // Constructor inherited

    /**
     * Crear nueva historia clínica con validación
     */
    public function crear($datos)
    {
        // Validar datos
        if (!$this->validator->validarHistoriaClinica($datos)) {
            throw new \Exception("Datos inválidos: " . implode(", ", $this->validator->getErrors()));
        }

        try {
            // Preparar datos para insertar (SIN motivo_consulta - ese campo está en tabla consultas)
            $historiaData = [
                'id_paciente' => $datos['id_paciente'],
                'analisis_plan' => $this->validator->sanitize($datos['analisis_plan'] ?? ''),
                'diagnostico' => $this->validator->sanitize($datos['diagnostico'] ?? ''),
                'tratamiento' => $this->validator->sanitize($datos['tratamiento'] ?? ''),
                'observaciones' => $this->validator->sanitize($datos['observaciones'] ?? ''),
                'id_consulta' => isset($datos['id_consulta']) ? (int)$datos['id_consulta'] : null
            ];

            // Si se proporciona fecha de ingreso personalizada, usarla. Si no, usar fecha actual.
            if (!empty($datos['fecha_ingreso'])) {
                $historiaData['fecha_ingreso'] = $datos['fecha_ingreso'];
            } else {
                $historiaData['fecha_ingreso'] = date('Y-m-d H:i:s');
            }

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

    /**
     * Obtener historias clínicas de un paciente
     */
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

    /**
     * Obtener historia clínica por ID
     */
    public function obtenerPorId($idHistoria)
    {
        try {
            $resultado = $this->supabase->select(
                'historias_clinicas',
                '*',
                "id_historia=eq.$idHistoria"
            );
            return !empty($resultado) ? $resultado[0] : null;
        } catch (\Exception $e) {
            throw new \Exception("Error al obtener historia: " . $e->getMessage());
        }
    }

    /**
     * Obtener todas las historias clínicas
     */
    public function obtenerTodas($limite = 100)
    {
        try {
            return $this->supabase->select(
                'historias_clinicas',
                '*',
                "limit=$limite",
                'fecha_ingreso.desc'
            );
        } catch (\Exception $e) {
            throw new \Exception("Error al obtener historias: " . $e->getMessage());
        }
    }

    /**
     * Actualizar historia clínica
     */
    public function actualizar($idHistoria, $datos)
    {
        // Verificar que la historia existe
        $historiaExistente = $this->obtenerPorId($idHistoria);
        if (!$historiaExistente) {
            throw new \Exception("Historia clínica no encontrada");
        }

        // Sanitizar datos
        $datosSanitizados = [];
        foreach ($datos as $key => $value) {
            if (is_string($value) && $key !== 'fecha_egreso') {
                $datosSanitizados[$key] = $this->validator->sanitize($value);
            } else {
                $datosSanitizados[$key] = $value;
            }
        }

        try {
            $resultado = $this->supabase->update(
                'historias_clinicas', 
                $datosSanitizados, 
                "id_historia=eq.$idHistoria"
            );
            return $resultado;
        } catch (\Exception $e) {
            throw new \Exception("Error al actualizar historia: " . $e->getMessage());
        }
    }

    /**
     * Buscar historias por diagnóstico
     */
    public function buscarPorDiagnostico($diagnostico)
    {
        try {
            $diagnostico = $this->validator->sanitize($diagnostico);
            return $this->supabase->select(
                'historias_clinicas',
                '*',
                "diagnostico.ilike.*{$diagnostico}*",
                'fecha_ingreso.desc'
            );
        } catch (\Exception $e) {
            throw new \Exception("Error al buscar historias: " . $e->getMessage());
        }
    }

    /**
     * Buscar historias por término general (diagnóstico, motivo o documento del paciente)
     */
    public function buscarGeneral($termino)
    {
        try {
            $termino = $this->validator->sanitize($termino);
            
            // 1. Buscar coincidencia en documentos de pacientes
            $filterIds = '';
            try {
                // Buscamos pacientes cuyo documento contenga el término
                // Nota: Usamos select directo a la tabla pacientes
                $pacientes = $this->supabase->select('pacientes', 'id_paciente', "documento_id.ilike.*{$termino}*");
                
                if (!empty($pacientes)) {
                    $ids = array_column($pacientes, 'id_paciente');
                    // Limitar a una cantidad razonable para evitar URLs gigantes
                    $ids = array_slice($ids, 0, 50); 
                    if (!empty($ids)) {
                        $idsStr = implode(',', $ids);
                        $filterIds = ",id_paciente.in.($idsStr)";
                    }
                }
            } catch (\Exception $e) {
                // Si falla la búsqueda de pacientes, ignoramos y seguimos con texto
            }

            // 2. Construir filtro combinadocon OR
            // Supabase PostgREST 'or' syntax: or=(cond1,cond2,cond3)
            $filter = "or=(diagnostico.ilike.*{$termino}*,motivo_consulta.ilike.*{$termino}*{$filterIds})";
            
            return $this->supabase->select(
                'historias_clinicas',
                '*, pacientes:id_paciente(primer_nombre, primer_apellido, documento_id)',
                $filter,
                'fecha_ingreso.desc'
            );
        } catch (\Exception $e) {
            throw new \Exception("Error al buscar historias: " . $e->getMessage());
        }
    }

    /**
     * Obtener historias recientes
     */
    public function obtenerRecientes($limite = 10)
    {
        try {
            return $this->supabase->select(
                'historias_clinicas',
                '*, pacientes:id_paciente(primer_nombre, primer_apellido, documento_id)',
                "limit=$limite",
                'fecha_ingreso.desc'
            );
        } catch (\Exception $e) {
            throw new \Exception("Error al obtener historias recientes: " . $e->getMessage());
        }
    }

    /**
     * Contar total de historias clínicas
     */
    public function contarTotal()
    {
        try {
            $historias = $this->supabase->select('historias_clinicas', 'id_historia');
            return count($historias);
        } catch (\Exception $e) {
            throw new \Exception("Error al contar historias: " . $e->getMessage());
        }
    }

    /**
     * Contar historias por paciente
     */
    public function contarPorPaciente($idPaciente)
    {
        try {
            $historias = $this->obtenerPorPaciente($idPaciente);
            return count($historias);
        } catch (\Exception $e) {
            throw new \Exception("Error al contar historias del paciente: " . $e->getMessage());
        }
    }

    /**
     * Cerrar historia clínica (establecer fecha de egreso)
     */
    public function cerrar($idHistoria)
    {
        try {
            $datos = [
                'fecha_egreso' => date('Y-m-d H:i:s')
            ];
            return $this->supabase->update('historias_clinicas', $datos, "id_historia=eq.$idHistoria");
        } catch (\Exception $e) {
            throw new \Exception("Error al cerrar historia: " . $e->getMessage());
        }
    }

    /**
     * Eliminar historia clínica
     */
    public function eliminar($idHistoria)
    {
        try {
            return $this->supabase->delete('historias_clinicas', "id_historia=eq.$idHistoria");
        } catch (\Exception $e) {
            throw new \Exception("Error al eliminar historia: " . $e->getMessage());
        }
    }
}