<?php

namespace App;

use App\SupabaseClient;

class Paciente
{
    private $supabase;

    public function __construct(SupabaseClient $supabase)
    {
        $this->supabase = $supabase;
    }

    public function obtenerTodos()
    {
        try {
            return $this->supabase->select('pacientes', '*', '', 'primer_nombre.asc');
        } catch (\Exception $e) {
            throw new \Exception("Error al obtener pacientes: " . $e->getMessage());
        }
    }

    public function obtenerPorId($id)
    {
        try {
            $resultado = $this->supabase->select('pacientes', '*', "id_paciente=eq.$id");
            return !empty($resultado) ? $resultado[0] : null;
        } catch (\Exception $e) {
            throw new \Exception("Error al obtener paciente: " . $e->getMessage());
        }
    }

    public function buscarPorDocumento($documento)
    {
        try {
            $resultado = $this->supabase->select('pacientes', '*', "documento_id=eq.$documento");
            return !empty($resultado) ? $resultado[0] : null;
        } catch (\Exception $e) {
            throw new \Exception("Error al buscar paciente: " . $e->getMessage());
        }
    }
}