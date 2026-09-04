<?php

declare(strict_types=1);

namespace App\Modules\Vehiculo\Services;

use App\Shared\Models\Asiento;
use App\Shared\Models\Piso;
use App\Shared\Models\Vehiculo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class VehiculoService
{
    /**
     * Crea un vehículo completo con sus pisos y asientos.
     *
     * @param array $data Datos validados.
     */
    public function store(array $data): Vehiculo
    {
        $vehiculo = DB::transaction(function () use ($data): Vehiculo {
            $vehiculoData = $this->extractVehiculoData($data);
            $vehiculo = Vehiculo::query()->create($vehiculoData);
            $this->syncPisos($vehiculo, $data['pisos'] ?? []);
            return $vehiculo;
        });

        $this->recalcularCapacidad($vehiculo);

        return $vehiculo->fresh(); // solo datos del vehículo, sin relaciones
    }

    /**
     * Actualiza un vehículo y su plano completo.
     *
     * @param Vehiculo $vehiculo
     * @param array $data
     */
    public function update(Vehiculo $vehiculo, array $data): Vehiculo
    {
        $vehiculo = DB::transaction(function () use ($vehiculo, $data): Vehiculo {
            $vehiculoData = $this->extractVehiculoData($data);
            $vehiculo->update($vehiculoData);
            $this->syncPisos($vehiculo, $data['pisos'] ?? []);
            return $vehiculo;
        });

        $this->recalcularCapacidad($vehiculo);

        return $vehiculo->fresh(); // sin relaciones
    }

    /**
     * Da de baja un vehículo (estado 'Baja') sin eliminar registros.
     */
    public function destroy(Vehiculo $vehiculo): void
    {
        $vehiculo->update(['estado' => 'Baja']);
    }

    /**
     * Extrae solo los campos correspondientes a la tabla vehiculo.
     */
    private function extractVehiculoData(array $data): array
    {
        return [
            'id_categoria' => $data['id_categoria'],
            'placa' => $data['placa'],
            'tipo' => $data['tipo'],
            'marca' => $data['marca'],
            'modelo' => $data['modelo'],
            'color' => $data['color'] ?? null,
            'estado' => $data['estado'],
        ];
    }

    /**
     * Sincroniza los pisos y asientos de un vehículo.
     */
    private function syncPisos(Vehiculo $vehiculo, array $pisosData): void
    {
        // Cargar todos los pisos actuales del vehículo, indexados por número
        $pisosExistentes = $vehiculo->pisos()
            ->get()
            ->keyBy('numero');

        $pisosProcesadosIds = [];

        foreach ($pisosData as $pisoData) {
            $numero = $pisoData['numero'];
            $pisoId = $pisoData['id'] ?? null;

            // Buscar por id si viene y existe
            $piso = null;
            if ($pisoId) {
                $piso = $pisosExistentes->firstWhere('id', $pisoId);
            }

            // Si no se encontró por id, buscar por número
            if (!$piso && isset($pisosExistentes[$numero])) {
                $piso = $pisosExistentes[$numero];
            }

            if ($piso) {
                // Actualizar piso existente
                $piso->update([
                    'numero' => $numero,
                    'nombre' => $pisoData['nombre'],
                    'filas' => $pisoData['filas'],
                    'columnas' => $pisoData['columnas'],
                    'orden' => $pisoData['orden'] ?? 0,
                    'estado' => $pisoData['estado'],
                ]);
                $pisosProcesadosIds[] = $piso->id;
            } else {
                // Crear nuevo piso
                $piso = $vehiculo->pisos()->create([
                    'numero' => $numero,
                    'nombre' => $pisoData['nombre'],
                    'filas' => $pisoData['filas'],
                    'columnas' => $pisoData['columnas'],
                    'orden' => $pisoData['orden'] ?? 0,
                    'estado' => $pisoData['estado'],
                ]);
                $pisosProcesadosIds[] = $piso->id;
            }

            // Sincronizar asientos de este piso (usando la versión ya corregida)
            $this->syncAsientos($piso, $pisoData['asientos'] ?? []);

            // Aplicar regla de estado: si piso inactivo, desactivar asientos
            if ($piso->estado === 'Inactivo') {
                $piso->asientos()->update(['estado' => 'Inactivo']);
            } elseif ($piso->estado === 'Activo') {
                // Si estaba inactivo y ahora se activa, reactivar todos sus asientos
                // (pero solo si el frontend no envió asientos específicos con estado inactivo)
                $piso->asientos()->update(['estado' => 'Activo']);
            }
        }

        // Desactivar pisos que no fueron procesados (ni por id ni por número)
        $vehiculo->pisos()
            ->whereNotIn('id', $pisosProcesadosIds)
            ->update(['estado' => 'Inactivo']);
    }

    /**
     * Sincroniza los asientos de un piso específico.
     *
     * Estrategia:
     * - Buscar por posición (fila, columna) antes de crear.
     * - Actualizar existentes (aunque estén inactivos) y crear solo si no existen.
     * - Al final, desactivar los que no estén en el payload.
     */
    private function syncAsientos(Piso $piso, array $asientosData): void
    {
        // Obtener todos los asientos actuales del piso, indexados por "fila|columna"
        $asientosExistentes = $piso->asientos()
            ->get()
            ->keyBy(fn(Asiento $asiento) => $asiento->fila . '|' . $asiento->columna);

        $asientosPresentes = [];

        foreach ($asientosData as $asientoData) {
            $fila = $asientoData['fila'];
            $columna = $asientoData['columna'];
            $clave = $fila . '|' . $columna;

            // Buscar por posición, no por id
            $asiento = $asientosExistentes->get($clave);

            if ($asiento) {
                // Actualizar el existente (aunque estuviera inactivo, se reactiva)
                $asiento->update([
                    'fila' => $fila,
                    'columna' => $columna,
                    'tipo_celda' => $asientoData['tipo_celda'],
                    'numero_asiento' => $asientoData['numero_asiento'] ?? null,
                    'estado' => $asientoData['estado'],
                ]);
                $asientosPresentes[] = $asiento->id;
            } else {
                // Crear solo si no existe posición en este piso
                $nuevoAsiento = $piso->asientos()->create([
                    'fila' => $fila,
                    'columna' => $columna,
                    'tipo_celda' => $asientoData['tipo_celda'],
                    'numero_asiento' => $asientoData['numero_asiento'] ?? null,
                    'estado' => $asientoData['estado'],
                ]);
                $asientosPresentes[] = $nuevoAsiento->id;
            }
        }

        // Desactivar todos los asientos del piso que no estén en el payload
        $piso->asientos()
            ->whereNotIn('id', $asientosPresentes)
            ->update(['estado' => 'Inactivo']);
    }

    /**
     * Recalcula la capacidad del vehículo: número de asientos activos tipo pasajero en pisos activos.
     */
    private function recalcularCapacidad(Vehiculo $vehiculo): void
    {
        $capacidad = Asiento::query()
            ->join('piso', 'piso.id', '=', 'asiento.id_piso')
            ->where('piso.id_vehiculo', $vehiculo->id)
            ->where('piso.estado', 'Activo')
            ->where('asiento.estado', 'Activo')
            ->where('asiento.tipo_celda', 'pasajero')
            ->count();

        $vehiculo->update(['capacidad' => $capacidad]);
    }

    /**
     * Carga las relaciones necesarias para la respuesta.
     */
    private function loadRelations(Vehiculo $vehiculo): Vehiculo
    {
        return $vehiculo->load([
            'categoria',
            'pisos' => function ($query) {
                $query->orderBy('orden')->orderBy('numero');
            },
            'pisos.asientos' => function ($query) {
                $query->orderBy('fila')->orderBy('columna');
            },
        ]);
    }
}