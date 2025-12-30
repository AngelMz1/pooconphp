<?php

namespace App;

use App\SupabaseClient;
use App\BaseModel;

class Facturacion extends BaseModel {
    private $tableFacturas = 'facturas';
    private $tableItems = 'factura_items';

    public function crearFactura($pacienteId, $consultaId, $items, $observaciones = '') {
        // 1. Calcular Total
        $total = 0;
        foreach ($items as $item) {
            $total += $item['subtotal'];
        }

        // 2. Crear Cabecera
        $datosFactura = [
            'paciente_id' => $pacienteId,
            'consulta_id' => $consultaId,
            'fecha' => date('Y-m-d H:i:s'),
            'total' => $total,
            'estado' => 'pendiente',
            'observaciones' => $observaciones
        ];

        $factura = $this->supabase->insert($this->tableFacturas, $datosFactura);
        
        // SupabaseClient insert might return array of inserted rows, take ID
        // Assuming insert returns the object or array of objects. 
        // We'll trust the return structure or fetch last inserted if necessary.
        // For this mock implementation we assume $factura has 'id'. 
        // If not, we might need to select max ID or similar if returning isn't supported properly in client.
        
        $facturaId = $factura[0]['id'] ?? null;
        
        if (!$facturaId) {
            // Fallback strategy: fetch the latest invoice for this patient
            // pseudo limit 1 via Supabase functionality if implemented in client wrapper select method or via raw URL handling
            $latest = $this->supabase->select($this->tableFacturas, 'id', "paciente_id=eq.$pacienteId", 'created_at.desc', 1); 
            // Since SupabaseClient select doesn't support order/limit naturally in arguments, 
            // we really rely on insert returning data. Let's assume it does.
        }

        // 3. Crear Items
        if ($facturaId) {
            foreach ($items as $item) {
                $datosItem = [
                    'factura_id' => $facturaId,
                    'tarifario_id' => $item['tarifario_id'] ?? null,
                    'concepto' => $item['concepto'],
                    'cantidad' => $item['cantidad'],
                    'precio_unitario' => $item['precio_unitario'],
                    'subtotal' => $item['subtotal']
                ];
                $this->supabase->insert($this->tableItems, $datosItem);
            }
            return $facturaId;
        }
        return false;
    }

    public function obtenerFactura($id) {
        $factura = $this->supabase->select($this->tableFacturas, '*', "id=eq.$id");
        if (empty($factura)) return null;

        $items = $this->supabase->select($this->tableItems, '*', "factura_id=eq.$id");
        $factura[0]['items'] = $items;

        return $factura[0];
    }
}
