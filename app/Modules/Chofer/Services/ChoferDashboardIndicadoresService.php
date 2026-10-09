<?php

declare(strict_types=1);

namespace App\Modules\Chofer\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Indicadores financieros y operativos del Dashboard.
 *
 * Reglas:
 * - Viajes: fecha programada en vehiculo_chofer_ruta.hora_inicio.
 * - Ventas: solo Pagadas, no eliminadas, por venta.created_at.
 * - Recaudación: suma de detalle_venta.precio_unitario, como en PasajeReporteService.
 * - La venta pertenece al chofer/vehículo del viaje, NO al usuario que cobró.
 * - Egresos: movimientos Válidos del usuario-chofer (no se asignan a un vehículo).
 * - Balance referencial = recaudación por pasajes - egresos del chofer.
 * - No se suman los ingresos de arqueo porque las ventas generan ingresos
 *   automáticos en caja y eso duplicaría la recaudación.
 */
class ChoferDashboardIndicadoresService
{
    private const CAMPOS_SUMA = [
        'viajes_total',
        'viajes_finalizados',
        'viajes_en_curso',
        'viajes_programados',
        'viajes_cancelados',
        'ventas_pagadas',
        'pasajes_vendidos',
        'ingresos_pasajes',
        'egresos_validos',
        'egresos_cantidad',
    ];

    /** @return array{desde: ?string, hasta: ?string} */
    public function rango(string $periodo): array
    {
        $hoy = Carbon::now();
        $desde = match ($periodo) {
            'hoy' => $hoy->copy()->startOfDay(),
            '7dias' => $hoy->copy()->subDays(6)->startOfDay(),
            'mes' => $hoy->copy()->startOfMonth()->startOfDay(),
            default => null,
        };

        return [
            'desde' => $desde?->toDateTimeString(),
            'hasta' => $desde ? $hoy->copy()->endOfDay()->toDateTimeString() : null,
        ];
    }

    /**
     * @param array<int, int> $idsChofer
     * @return array{choferes: array<int, array{indicadores: array, vehiculos: array}>, indicadores: array, vehiculos: array}
     */
    public function calcular(array $idsChofer, string $periodo): array
    {
        $idsChofer = array_values(array_unique(array_map('intval', $idsChofer)));
        $rango = $this->rango($periodo);
        $porChofer = [];
        $vehiculosChofer = [];
        $vehiculosGlobal = [];
        $total = $this->indicadoresVacios();

        foreach ($idsChofer as $id) {
            $porChofer[$id] = $this->indicadoresVacios();
            $vehiculosChofer[$id] = [];
        }

        if ($idsChofer === []) {
            return ['choferes' => [], 'indicadores' => $total, 'vehiculos' => []];
        }

        // Una fila por viaje: no unir detalle_venta aquí para no duplicar viajes.
        $consultaViajes = DB::table('viaje as vi')
            ->join('vehiculo_chofer_ruta as vcr', 'vcr.id', '=', 'vi.id_vehiculo_chofer_ruta')
            ->join('asignacion_vehiculo_chofer as avc', 'avc.id', '=', 'vcr.id_asignacion_vehiculo_chofer')
            ->join('vehiculo as vh', 'vh.id', '=', 'avc.id_vehiculo')
            ->whereIn('avc.id_chofer', $idsChofer);

        if ($rango['desde'] !== null) {
            $consultaViajes->whereBetween('vcr.hora_inicio', [$rango['desde'], $rango['hasta']]);
        }

        $viajes = $consultaViajes
            ->selectRaw('avc.id_chofer, avc.id_vehiculo, vh.placa')
            ->selectRaw('COUNT(vi.id) as viajes_total')
            ->selectRaw("SUM(CASE WHEN vi.estado = 'Finalizado' THEN 1 ELSE 0 END) as viajes_finalizados")
            ->selectRaw("SUM(CASE WHEN vi.estado = 'En curso' THEN 1 ELSE 0 END) as viajes_en_curso")
            ->selectRaw("SUM(CASE WHEN vi.estado = 'Vendiendo' THEN 1 ELSE 0 END) as viajes_programados")
            ->selectRaw("SUM(CASE WHEN vi.estado = 'Cancelado' THEN 1 ELSE 0 END) as viajes_cancelados")
            ->groupBy('avc.id_chofer', 'avc.id_vehiculo', 'vh.placa')
            ->get();

        foreach ($viajes as $fila) {
            $id = (int) $fila->id_chofer;
            $idVehiculo = (int) $fila->id_vehiculo;
            $this->crearVehiculoSiFalta($vehiculosChofer[$id], $idVehiculo, (string) $fila->placa);
            $this->crearVehiculoSiFalta($vehiculosGlobal, $idVehiculo, (string) $fila->placa);

            foreach (['viajes_total', 'viajes_finalizados', 'viajes_en_curso', 'viajes_programados', 'viajes_cancelados'] as $campo) {
                $valor = (int) $fila->{$campo};
                $porChofer[$id][$campo] += $valor;
                $vehiculosChofer[$id][$idVehiculo][$campo] += $valor;
                $vehiculosGlobal[$idVehiculo][$campo] += $valor;
            }
        }

        // Subconsulta: un registro por venta, con todos sus asientos.
        // Evita multiplicar ventas y montos al cruzar tablas.
        $detallePorVenta = DB::table('detalle_venta')
            ->select('id_venta')
            ->selectRaw('COUNT(id) as pasajes')
            ->selectRaw('COALESCE(SUM(precio_unitario), 0) as monto')
            ->groupBy('id_venta');

        $consultaVentas = DB::table('venta as ve')
            ->join('viaje as vi', 'vi.id', '=', 've.id_viaje')
            ->join('vehiculo_chofer_ruta as vcr', 'vcr.id', '=', 'vi.id_vehiculo_chofer_ruta')
            ->join('asignacion_vehiculo_chofer as avc', 'avc.id', '=', 'vcr.id_asignacion_vehiculo_chofer')
            ->join('vehiculo as vh', 'vh.id', '=', 'avc.id_vehiculo')
            ->leftJoinSub($detallePorVenta, 'dv', 'dv.id_venta', '=', 've.id')
            ->where('ve.estado', 'Pagada')
            ->whereNull('ve.deleted_at')
            ->whereIn('avc.id_chofer', $idsChofer);

        if ($rango['desde'] !== null) {
            $consultaVentas->whereBetween('ve.created_at', [$rango['desde'], $rango['hasta']]);
        }

        $ventas = $consultaVentas
            ->selectRaw('avc.id_chofer, avc.id_vehiculo, vh.placa')
            ->selectRaw('COUNT(ve.id) as ventas_pagadas')
            ->selectRaw('COALESCE(SUM(dv.pasajes), 0) as pasajes_vendidos')
            ->selectRaw('COALESCE(SUM(dv.monto), 0) as ingresos_pasajes')
            ->groupBy('avc.id_chofer', 'avc.id_vehiculo', 'vh.placa')
            ->get();

        foreach ($ventas as $fila) {
            $id = (int) $fila->id_chofer;
            $idVehiculo = (int) $fila->id_vehiculo;
            $this->crearVehiculoSiFalta($vehiculosChofer[$id], $idVehiculo, (string) $fila->placa);
            $this->crearVehiculoSiFalta($vehiculosGlobal, $idVehiculo, (string) $fila->placa);

            foreach (['ventas_pagadas', 'pasajes_vendidos'] as $campo) {
                $valor = (int) $fila->{$campo};
                $porChofer[$id][$campo] += $valor;
                $vehiculosChofer[$id][$idVehiculo][$campo] += $valor;
                $vehiculosGlobal[$idVehiculo][$campo] += $valor;
            }
            $monto = round((float) $fila->ingresos_pasajes, 2);
            $porChofer[$id]['ingresos_pasajes'] += $monto;
            $vehiculosChofer[$id][$idVehiculo]['ingresos_pasajes'] += $monto;
            $vehiculosGlobal[$idVehiculo]['ingresos_pasajes'] += $monto;
        }

        // Los egresos tienen id_user, no id_vehiculo: se atribuyen al chofer,
        // nunca arbitrariamente a un auto.
        $consultaEgresos = DB::table('egreso')
            ->whereIn('id_user', $idsChofer)
            ->where('estado', 'Valido')
            ->whereNull('deleted_at');

        if ($rango['desde'] !== null) {
            $consultaEgresos->whereBetween('fecha_registro', [$rango['desde'], $rango['hasta']]);
        }

        $egresos = $consultaEgresos
            ->select('id_user')
            ->selectRaw('COUNT(id) as egresos_cantidad')
            ->selectRaw('COALESCE(SUM(monto), 0) as egresos_validos')
            ->groupBy('id_user')
            ->get();

        foreach ($egresos as $fila) {
            $id = (int) $fila->id_user;
            $porChofer[$id]['egresos_cantidad'] = (int) $fila->egresos_cantidad;
            $porChofer[$id]['egresos_validos'] = round((float) $fila->egresos_validos, 2);
        }

        $respuestaChoferes = [];
        foreach ($idsChofer as $id) {
            $this->calcularDerivados($porChofer[$id]);
            foreach ($vehiculosChofer[$id] as &$vehiculo) {
                $vehiculo['ingresos_pasajes'] = round($vehiculo['ingresos_pasajes'], 2);
            }
            unset($vehiculo);

            $respuestaChoferes[$id] = [
                'indicadores' => $porChofer[$id],
                'vehiculos' => $this->ordenarVehiculos($vehiculosChofer[$id]),
            ];
            foreach (self::CAMPOS_SUMA as $campo) {
                $total[$campo] += $porChofer[$id][$campo];
            }
        }

        $this->calcularDerivados($total);
        foreach ($vehiculosGlobal as &$vehiculo) {
            $vehiculo['ingresos_pasajes'] = round($vehiculo['ingresos_pasajes'], 2);
        }
        unset($vehiculo);

        return [
            'choferes' => $respuestaChoferes,
            'indicadores' => $total,
            'vehiculos' => $this->ordenarVehiculos($vehiculosGlobal),
        ];
    }

    private function indicadoresVacios(): array
    {
        return [
            'viajes_total' => 0,
            'viajes_finalizados' => 0,
            'viajes_en_curso' => 0,
            'viajes_programados' => 0,
            'viajes_cancelados' => 0,
            'ventas_pagadas' => 0,
            'pasajes_vendidos' => 0,
            'ingresos_pasajes' => 0.0,
            'egresos_validos' => 0.0,
            'egresos_cantidad' => 0,
            'saldo_referencial' => 0.0,
            'promedio_por_pasaje' => 0.0,
            'promedio_por_viaje' => 0.0,
        ];
    }

    /** @param array<int, array> $vehiculos */
    private function crearVehiculoSiFalta(array &$vehiculos, int $id, string $placa): void
    {
        if (!isset($vehiculos[$id])) {
            $vehiculos[$id] = [
                'id' => $id,
                'placa' => $placa,
                'viajes_total' => 0,
                'viajes_finalizados' => 0,
                'viajes_en_curso' => 0,
                'viajes_programados' => 0,
                'viajes_cancelados' => 0,
                'ventas_pagadas' => 0,
                'pasajes_vendidos' => 0,
                'ingresos_pasajes' => 0.0,
            ];
        }
    }

    private function calcularDerivados(array &$indicadores): void
    {
        $indicadores['ingresos_pasajes'] = round($indicadores['ingresos_pasajes'], 2);
        $indicadores['egresos_validos'] = round($indicadores['egresos_validos'], 2);
        $indicadores['saldo_referencial'] = round(
            $indicadores['ingresos_pasajes'] - $indicadores['egresos_validos'], 2
        );
        $indicadores['promedio_por_pasaje'] = $indicadores['pasajes_vendidos'] > 0
            ? round($indicadores['ingresos_pasajes'] / $indicadores['pasajes_vendidos'], 2)
            : 0.0;
        $indicadores['promedio_por_viaje'] = $indicadores['viajes_total'] > 0
            ? round($indicadores['ingresos_pasajes'] / $indicadores['viajes_total'], 2)
            : 0.0;
    }

    /** @param array<int, array> $vehiculos */
    private function ordenarVehiculos(array $vehiculos): array
    {
        $resultado = array_values($vehiculos);
        usort($resultado, static function (array $a, array $b): int {
            $monto = $b['ingresos_pasajes'] <=> $a['ingresos_pasajes'];
            return $monto !== 0 ? $monto : strcmp($a['placa'], $b['placa']);
        });
        return $resultado;
    }
}
