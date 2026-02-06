<?php

namespace App;

use App\SupabaseClient;
use App\BaseModel;

class Facturacion extends BaseModel {
    private $tableFacturas = 'facturas';
    private $tableItems = 'factura_items';
    private $tablePagos = 'pagos';
    private $tableTarifarios = 'tarifarios';

    /**
     * Genera factura automáticamente desde una consulta finalizada
     * @param int $consultaId
     * @param array $opciones ['incluir_medicamentos' => bool, 'descuento' => float]
     * @return int|false $facturaId
     */
    public function generarDesdeConsulta($consultaId, $opciones = []) {
        try {
            // 1. Obtener datos de la consulta
            $consulta = $this->supabase->select('consultas', '*', "id_consulta=eq.$consultaId");
            if (empty($consulta)) {
                throw new \Exception("Consulta no encontrada");
            }
            $consulta = $consulta[0];

            // 2. Obtener datos del paciente (para determinar EPS)
            $paciente = $this->supabase->select('pacientes', '*', "id_paciente=eq.{$consulta['id_paciente']}");
            if (empty($paciente)) {
                throw new \Exception("Paciente no encontrado");
            }
            $paciente = $paciente[0];

            // 3. Iniciar items de factura
            $items = [];

            // 3.1 Agregar consulta como primer ítem
            $precioConsulta = $this->calcularPrecio('CONS001', $paciente['id_paciente']);
            $items[] = [
                'tarifario_id' => null,
                'concepto' => 'Consulta Médica General',
                'cantidad' => 1,
                'precio_unitario' => $precioConsulta['precio_base'],
                'subtotal' => $precioConsulta['precio_final']
            ];

            // 3.2 Obtener procedimientos (CUPS) de la historia clínica
            $historia = $this->supabase->select('historias_clinicas', 'id_historia', "id_consulta=eq.$consultaId");
            if (!empty($historia)) {
                $idHistoria = $historia[0]['id_historia'];
                
                // Obtener solicitudes de procedimientos
                $solicitudes = $this->supabase->select('solicitudes', '*', "id_historia=eq.$idHistoria");
                
                foreach ($solicitudes as $solicitud) {
                    // Buscar tarifa del procedimiento por código CUPS
                    $tarifa = $this->supabase->select($this->tableTarifarios, '*', "cups_codigo=eq.{$solicitud['codigo_cups']}");
                    
                    if (!empty($tarifa)) {
                        $tarifa = $tarifa[0];
                        $items[] = [
                            'tarifario_id' => $tarifa['id'],
                            'concepto' => $solicitud['nombre_procedimiento'],
                            'cantidad' => $solicitud['cantidad'] ?? 1,
                            'precio_unitario' => $tarifa['precio'],
                            'subtotal' => $tarifa['precio'] * ($solicitud['cantidad'] ?? 1)
                        ];
                    }
                }

                // 3.3 Medicamentos (si están incluidos en opciones)
                if (isset($opciones['incluir_medicamentos']) && $opciones['incluir_medicamentos']) {
                    $formulas = $this->supabase->select('formulas_medicas', '*', "id_historia=eq.$idHistoria");
                    foreach ($formulas as $formula) {
                        // Aquí necesitarías un catálogo de precios de medicamentos
                        // Por ahora lo dejamos comentado
                    }
                }
            }

            // 4. Calcular totales
            $subtotal = 0;
            $copago = 0;
            foreach ($items as $item) {
                $subtotal += $item['subtotal'];
            }

            // El copago se calcula basado en la EPS
            if (!empty($paciente['eps_id'])) {
                // Promedio de copago del 20% para contributivo
                $copago = $subtotal * 0.20;
            }

            $total = $subtotal - $copago;
            $descuento = $opciones['descuento'] ?? 0;
            $total -= $descuento;

            // 5. Crear factura
            $datosFactura = [
                'paciente_id' => $consulta['id_paciente'],
                'consulta_id' => $consultaId,
                'fecha' => date('Y-m-d H:i:s'),
                'subtotal' => $subtotal,
                'copago' => $copago,
                'descuento' => $descuento,
                'total' => $total,
                'estado' => 'pendiente',
                'observaciones' => 'Factura generada automáticamente'
            ];

            $factura = $this->supabase->insert($this->tableFacturas, $datosFactura);
            $facturaId = $factura[0]['id'] ?? null;

            if (!$facturaId) {
                throw new \Exception("Error al crear factura");
            }

            // 6. Insertar items
            foreach ($items as $item) {
                $item['factura_id'] = $facturaId;
                $this->supabase->insert($this->tableItems, $item);
            }

            return $facturaId;

        } catch (\Exception $e) {
            error_log("Error generando factura: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Calcula precio de un servicio según EPS del paciente
     * @param string $codigoServicio
     * @param int $pacienteId
     * @return array ['precio_base', 'copago', 'precio_final']
     */
    public function calcularPrecio($codigoServicio, $pacienteId) {
        // Obtener EPS del paciente
        $paciente = $this->supabase->select('pacientes', 'eps_id,regimen_id', "id_paciente=eq.$pacienteId");
        $epsId = $paciente[0]['eps_id'] ?? null;

        // Intentar obtener tarifa específica para la EPS
        $tarifa = null;
        if ($epsId) {
            $tarifaEps = $this->supabase->select($this->tableTarifarios, '*', "codigo=like.$codigoServicio%&eps_id=eq.$epsId");
            if (!empty($tarifaEps)) {
                $tarifa = $tarifaEps[0];
            }
        }

        // Si no hay tarifa específica, usar la general
        if (!$tarifa) {
            $tarifaGeneral = $this->supabase->select($this->tableTarifarios, '*', "codigo=eq.$codigoServicio");
            $tarifa = $tarifaGeneral[0] ?? ['precio' => 50000, 'porcentaje_copago' => 0];
        }

        $precioBase = $tarifa['precio'];
        $porcentajeCopago = $tarifa['porcentaje_copago'] ?? 0;
        $copago = $precioBase * ($porcentajeCopago / 100);
        $precioFinal = $precioBase - $copago;

        return [
            'precio_base' => $precioBase,
            'copago' => $copago,
            'precio_final' => $precioFinal
        ];
    }

    /**
     * Registra un pago para una factura
     * @param int $facturaId
     * @param array $datosPago ['monto', 'metodo_pago', 'referencia', 'usuario_id', 'observaciones']
     * @return bool
     */
    public function registrarPago($facturaId, $datosPago) {
        try {
            // 1. Insertar en tabla pagos
            $pago = [
                'factura_id' => $facturaId,
                'monto' => $datosPago['monto'],
                'metodo_pago' => $datosPago['metodo_pago'],
                'referencia' => $datosPago['referencia'] ?? null,
                'usuario_id' => $datosPago['usuario_id'] ?? $_SESSION['user_id'] ?? null,
                'observaciones' => $datosPago['observaciones'] ?? '',
                'fecha_pago' => date('Y-m-d H:i:s')
            ];

            $this->supabase->insert($this->tablePagos, $pago);

            // 2. Obtener factura actual
            $factura = $this->supabase->select($this->tableFacturas, 'total', "id=eq.$facturaId");
            if (empty($factura)) {
                throw new \Exception("Factura no encontrada");
            }

            $totalFactura = $factura[0]['total'];

            // 3. Calcular total pagado
            $pagos = $this->supabase->select($this->tablePagos, 'monto', "factura_id=eq.$facturaId");
            $totalPagado = 0;
            foreach ($pagos as $p) {
                $totalPagado += $p['monto'];
            }

            // 4. Actualizar estado de factura
            $nuevoEstado = ($totalPagado >= $totalFactura) ? 'pagada' : 'pendiente';
            
            $this->supabase->update($this->tableFacturas, [
                'estado' => $nuevoEstado,
                'metodo_pago' => $datosPago['metodo_pago'],
                'referencia_pago' => $datosPago['referencia'] ?? null,
                'fecha_pago' => date('Y-m-d H:i:s'),
                'usuario_cajero_id' => $datosPago['usuario_id'] ?? $_SESSION['user_id'] ?? null
            ], "id=eq.$facturaId");

            return true;

        } catch (\Exception $e) {
            error_log("Error registrando pago: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene todas las facturas con filtros
     * @param array $filtros ['estado', 'fecha_desde', 'fecha_hasta', 'paciente_id']
     * @return array
     */
    public function listarFacturas($filtros = []) {
        $where = [];
        
        if (!empty($filtros['estado'])) {
            $where[] = "estado=eq.{$filtros['estado']}";
        }
        
        if (!empty($filtros['fecha_desde'])) {
            $where[] = "fecha=gte.{$filtros['fecha_desde']}";
        }
        
        if (!empty($filtros['fecha_hasta'])) {
            $where[] = "fecha=lte.{$filtros['fecha_hasta']}";
        }
        
        if (!empty($filtros['paciente_id'])) {
            $where[] = "paciente_id=eq.{$filtros['paciente_id']}";
        }

        $whereStr = implode('&', $where);
        
        return $this->supabase->select($this->tableFacturas, '*', $whereStr, 'fecha.desc');
    }

    /**
     * Anula una factura (requiere autorización)
     * @param int $facturaId
     * @param string $motivo
     * @return bool
     */
    public function anularFactura($facturaId, $motivo) {
        try {
            $this->supabase->update($this->tableFacturas, [
                'estado' => 'anulada',
                'notas_internas' => "ANULADA: " . $motivo . " | " . date('Y-m-d H:i:s') . " | Usuario: " . ($_SESSION['user_id'] ?? 'N/A')
            ], "id=eq.$facturaId");

            return true;
        } catch (\Exception $e) {
            error_log("Error anulando factura: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Método original (mantener compatibilidad)
     */
    public function crearFactura($pacienteId, $consultaId, $items, $observaciones = '') {
        // Calcular Total
        $total = 0;
        foreach ($items as $item) {
            $total += $item['subtotal'];
        }

        // Crear Cabecera
        $datosFactura = [
            'paciente_id' => $pacienteId,
            'consulta_id' => $consultaId,
            'fecha' => date('Y-m-d H:i:s'),
            'total' => $total,
            'estado' => 'pendiente',
            'observaciones' => $observaciones
        ];

        $factura = $this->supabase->insert($this->tableFacturas, $datosFactura);
        $facturaId = $factura[0]['id'] ?? null;
        
        if (!$facturaId) {
            // Fallback strategy: fetch the latest invoice for this patient
            $latest = $this->supabase->select($this->tableFacturas, 'id', "paciente_id=eq.$pacienteId", 'created_at.desc', 1); 
        }

        // Crear Items
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

    /**
     * Método original (mantener compatibilidad)
     */
    public function obtenerFactura($id) {
        $factura = $this->supabase->select($this->tableFacturas, '*', "id=eq.$id");
        if (empty($factura)) return null;

        $items = $this->supabase->select($this->tableItems, '*', "factura_id=eq.$id");
        $factura[0]['items'] = $items;

        // Agregar información de pagos
        $pagos = $this->supabase->select($this->tablePagos, '*', "factura_id=eq.$id");
        $factura[0]['pagos'] = $pagos;

        return $factura[0];
    }

    /**
     * Obtener reporte de caja para un rango de fechas
     * @param string $fechaDesde
     * @param string $fechaHasta
     * @return array
     */
    public function reporteCaja($fechaDesde, $fechaHasta) {
        $facturas = $this->listarFacturas([
            'fecha_desde' => $fechaDesde,
            'fecha_hasta' => $fechaHasta
        ]);

        $totalFacturado = 0;
        $totalCobrado = 0;
        $totalPendiente = 0;
        $porMetodo = [];

        foreach ($facturas as $factura) {
            $totalFacturado += $factura['total'];
            
            if ($factura['estado'] === 'pagada') {
                $totalCobrado += $factura['total'];
                $metodo = $factura['metodo_pago'] ?? 'No especificado';
                $porMetodo[$metodo] = ($porMetodo[$metodo] ?? 0) + $factura['total'];
            } elseif ($factura['estado'] === 'pendiente') {
                $totalPendiente += $factura['total'];
            }
        }

        return [
            'total_facturado' => $totalFacturado,
            'total_cobrado' => $totalCobrado,
            'total_pendiente' => $totalPendiente,
            'facturas_count' => count($facturas),
            'por_metodo_pago' => $porMetodo
        ];
    }
}
