<?php

namespace App;

use App\SupabaseClient;
use App\BaseModel;

class Tarifario extends BaseModel {
    private $table = 'tarifarios';

    public function listarServicios($activo = true) {
        $filtros = '';
        if ($activo) {
            $filtros = 'activo=eq.true';
        }
        return $this->supabase->select($this->table, '*', $filtros);
    }

    public function crearServicio($codigo, $nombre, $precio, $descripcion = '') {
        $datos = [
            'codigo' => $codigo,
            'nombre_servicio' => $nombre,
            'precio' => $precio,
            'descripcion' => $descripcion,
            'activo' => true
        ];
        return $this->supabase->insert($this->table, $datos);
    }

    public function actualizarServicio($id, $datos) {
        return $this->supabase->update($this->table, $datos, "id=eq.$id");
    }
    
    public function obtenerServicioPorId($id) {
        $res = $this->supabase->select($this->table, '*', "id=eq.$id");
        return (!empty($res)) ? $res[0] : null;
    }
}
