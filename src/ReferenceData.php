<?php

namespace App;

use App\SupabaseClient;

/**
 * Clase helper para obtener datos de tablas de referencia
 */
class ReferenceData
{
    private $supabase;
    private $cache = [];

    public function __construct(SupabaseClient $supabase)
    {
        $this->supabase = $supabase;
    }

    /**
     * Obtener tipos de documento
     */
    public function getTiposDocumento()
    {
        if (!isset($this->cache['tipo_documento'])) {
            try {
                $this->cache['tipo_documento'] = $this->supabase->select('tipo_documento', '*', '', 'id.asc');
            } catch (\Exception $e) {
                $this->cache['tipo_documento'] = [];
            }
        }
        return $this->cache['tipo_documento'];
    }

    /**
     * Obtener sexos
     */
    public function getSexos()
    {
        if (!isset($this->cache['sexo'])) {
            try {
                $this->cache['sexo'] = $this->supabase->select('sexo', '*', '', 'id.asc');
            } catch (\Exception $e) {
                $this->cache['sexo'] = [];
            }
        }
        return $this->cache['sexo'];
    }

    /**
     * Obtener estados civiles
     */
    public function getEstadosCiviles()
    {
        if (!isset($this->cache['estado_civil'])) {
            try {
                $this->cache['estado_civil'] = $this->supabase->select('estado_civil', '*', '', 'id.asc');
            } catch (\Exception $e) {
                $this->cache['estado_civil'] = [];
            }
        }
        return $this->cache['estado_civil'];
    }

    /**
     * Obtener ciudades
     */
    public function getCiudades()
    {
        if (!isset($this->cache['ciudades'])) {
            try {
                $this->cache['ciudades'] = $this->supabase->select('ciudades', '*', '', 'nombre.asc');
            } catch (\Exception $e) {
                $this->cache['ciudades'] = [];
            }
        }
        return $this->cache['ciudades'];
    }

    /**
     * Obtener barrios por ciudad
     */
    public function getBarrios($ciudad_id = null)
    {
        $cacheKey = 'barrios_' . ($ciudad_id ?? 'all');
        
        if (!isset($this->cache[$cacheKey])) {
            try {
                $filter = $ciudad_id ? "ciudad_id=eq.$ciudad_id" : '';
                $this->cache[$cacheKey] = $this->supabase->select('barrio', '*', $filter, 'barrio.asc');
            } catch (\Exception $e) {
                $this->cache[$cacheKey] = [];
            }
        }
        return $this->cache[$cacheKey];
    }

    /**
     * Obtener EPS
     */
    public function getEPS()
    {
        if (!isset($this->cache['eps'])) {
            try {
                $this->cache['eps'] = $this->supabase->select('eps', '*', '', 'nombre_eps.asc');
            } catch (\Exception $e) {
                $this->cache['eps'] = [];
            }
        }
        return $this->cache['eps'];
    }

    /**
     * Obtener regímenes
     */
    public function getRegimenes()
    {
        if (!isset($this->cache['regimen'])) {
            try {
                $this->cache['regimen'] = $this->supabase->select('regimen', '*', '', 'id.asc');
            } catch (\Exception $e) {
                $this->cache['regimen'] = [];
            }
        }
        return $this->cache['regimen'];
    }

    /**
     * Obtener grupos sanguíneos
     */
    public function getGruposSanguineos()
    {
        if (!isset($this->cache['gs_rh'])) {
            try {
                $this->cache['gs_rh'] = $this->supabase->select('gs_rh', '*', '', 'id.asc');
            } catch (\Exception $e) {
                $this->cache['gs_rh'] = [];
            }
        }
        return $this->cache['gs_rh'];
    }

    /**
     * Obtener etnias
     */
    public function getEtnias()
    {
        if (!isset($this->cache['etnia'])) {
            try {
                $this->cache['etnia'] = $this->supabase->select('etnia', '*', '', 'etnia.asc');
            } catch (\Exception $e) {
                $this->cache['etnia'] = [];
            }
        }
        return $this->cache['etnia'];
    }

    /**
     * Obtener niveles de escolaridad
     */
    public function getEscolaridades()
    {
        if (!isset($this->cache['escolaridad'])) {
            try {
                $this->cache['escolaridad'] = $this->supabase->select('escolaridad', '*', '', 'id.asc');
            } catch (\Exception $e) {
                $this->cache['escolaridad'] = [];
            }
        }
        return $this->cache['escolaridad'];
    }

    /**
     * Obtener orientaciones sexuales
     */
    public function getOrientacionesSexuales()
    {
        if (!isset($this->cache['orient_sexual'])) {
            try {
                $this->cache['orient_sexual'] = $this->supabase->select('orient_sexual', '*', '', 'id.asc');
            } catch (\Exception $e) {
                $this->cache['orient_sexual'] = [];
            }
        }
        return $this->cache['orient_sexual'];
    }

    /**
     * Obtener acudientes
     */
    public function getAcudientes()
    {
        if (!isset($this->cache['acudientes'])) {
            try {
                $this->cache['acudientes'] = $this->supabase->select('acudientes', '*', '', 'nombre.asc');
            } catch (\Exception $e) {
                $this->cache['acudientes'] = [];
            }
        }
        return $this->cache['acudientes'];
    }

    /**
     * Crear nuevo acudiente
     */
    public function crearAcudiente($datos)
    {
        try {
            $resultado = $this->supabase->insert('acudientes', $datos);
            // Limpiar cache
            unset($this->cache['acudientes']);
            return $resultado;
        } catch (\Exception $e) {
            throw new \Exception("Error al crear acudiente: " . $e->getMessage());
        }
    }

    /**
     * Obtener todos los datos necesarios para formulario de paciente
     */
    public function getAllForPatientForm()
    {
        return [
            'tipos_documento' => $this->getTiposDocumento(),
            'sexos' => $this->getSexos(),
            'estados_civiles' => $this->getEstadosCiviles(),
            'ciudades' => $this->getCiudades(),
            'eps' => $this->getEPS(),
            'regimenes' => $this->getRegimenes(),
            'grupos_sanguineos' => $this->getGruposSanguineos(),
            'etnias' => $this->getEtnias(),
            'escolaridades' => $this->getEscolaridades(),
            'orientaciones_sexuales' => $this->getOrientacionesSexuales(),
            'acudientes' => $this->getAcudientes()
        ];
    }

    /**
     * Limpiar cache
     */
    public function clearCache()
    {
        $this->cache = [];
    }
}
