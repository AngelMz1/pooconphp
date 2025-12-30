<?php

namespace App;

use App\SupabaseClient;
use App\Validator;

/**
 * Clase para gestionar procedimientos y solicitudes
 */
use App\BaseModel;

/**
 * Clase para gestionar procedimientos y solicitudes
 */
class Procedimiento extends BaseModel
{
    // Constructor inherited

    /**
     * Crear orden de procedimiento/examen
     */
    public function crear($datos)
    {
        try {
            // 1. Resolver proced_id a partir del codigo_cups
            $codigo = $this->validator->sanitize($datos['codigo_cups']);
            $procedimientoRef = $this->supabase->select('procedimientos', 'id', "codigo=eq.$codigo");
            
            $procedId = null;
            if (!empty($procedimientoRef) && isset($procedimientoRef[0]['id'])) {
                $procedId = $procedimientoRef[0]['id'];
            } else {
                // Opción: Insertar nuevo procedimiento en catálogo si no existe? 
                // O lanzar error. Por estabilidad, lanzamos error si no existe.
                // O podríamos usar un ID "Genérico" si existe.
                throw new \Exception("Procedimiento con código $codigo no encontrado en la base de datos.");
            }

            // 2. Insertar en solicitudes
            // Nota: solicitudes usa id_consulta, no id_historia directamente (aunque están relacionadas)
            // Debemos asegurarnos de recibir id_consulta
            if (empty($datos['id_consulta'])) {
                 // Intentar obtener id_consulta de la historia? (Costoso)
                 // Mejor requerirlo.
                 // Si falla, usar 0 o null si es permitido? Check schema: id_consulta int.
                 throw new \Exception("Se requiere id_consulta para solicitar procedimientos.");
            }

            $solicitudData = [
                'id_consulta' => (int)$datos['id_consulta'],
                'proced_id' => $procedId,
                'cantidad' => (int)($datos['cantidad'] ?? 1),
                'fecha' => date('Y-m-d H:i:s')
            ];

            $resultado = $this->supabase->insert('solicitudes', $solicitudData);
            return $resultado;
        } catch (\Exception $e) {
            throw new \Exception("Error al crear solicitud de procedimiento: " . $e->getMessage());
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
