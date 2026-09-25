<?php

declare(strict_types=1);

namespace App\Modules\Pasaje\Services;

use App\Shared\Models\DetalleVenta;
use App\Shared\Models\Pasajero;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PasajeroService
{
    public function listar(array $filtros = []): Collection
    {
        $buscar = trim((string) ($filtros['buscar'] ?? ''));
        $limite = (int) ($filtros['limite'] ?? 50);
        $limite = max(1, min($limite, 100));

        return Pasajero::query()
            ->withCount('detallesVenta')
            ->when(
                $buscar !== '',
                function ($query) use ($buscar): void {
                    $like = '%' . $buscar . '%';

                    $query->where(
                        function ($subquery) use ($like): void {
                            $subquery
                                ->where('nombres', 'ilike', $like)
                                ->orWhere('apellido_paterno', 'ilike', $like)
                                ->orWhere('apellido_materno', 'ilike', $like)
                                ->orWhere('ci', 'ilike', $like);
                        }
                    );
                }
            )
            ->orderByDesc('detalles_venta_count')
            ->orderBy('apellido_paterno')
            ->orderBy('nombres')
            ->limit($limite)
            ->get();
    }

    public function crear(array $data): Pasajero
    {
        return DB::transaction(
            function () use ($data): Pasajero {
                return Pasajero::query()
                    ->create([
                        'nombres' => trim((string) $data['nombres']),
                        'apellido_paterno' => trim((string) $data['apellido_paterno']),
                        'apellido_materno' =>
                            isset($data['apellido_materno'])
                            && trim((string) $data['apellido_materno']) !== ''
                                ? trim((string) $data['apellido_materno'])
                                : null,
                        'ci' =>
                            isset($data['ci'])
                            && trim((string) $data['ci']) !== ''
                                ? trim((string) $data['ci'])
                                : null,
                    ]);
            }
        );
    }

    public function vincularSeleccionados(
        int $ventaId,
        array $pasajeros
    ): void {
        DB::transaction(
            function () use ($ventaId, $pasajeros): void {
                foreach ($pasajeros as $datosPasajero) {
                    $detalleId =
                        (int) ($datosPasajero['id_detalle_venta'] ?? 0);

                    /** @var DetalleVenta|null $detalle */
                    $detalle =
                        DetalleVenta::query()
                            ->where('id', $detalleId)
                            ->where('id_venta', $ventaId)
                            ->lockForUpdate()
                            ->first();

                    if (!$detalle) {
                        throw new RuntimeException(
                            "El detalle {$detalleId} no pertenece a esta venta."
                        );
                    }

                    $idPasajero =
                        isset($datosPasajero['id_pasajero'])
                        && $datosPasajero['id_pasajero'] !== null
                            ? (int) $datosPasajero['id_pasajero']
                            : null;

                    if ($idPasajero !== null) {
                        $existe =
                            Pasajero::query()
                                ->where('id', $idPasajero)
                                ->exists();

                        if (!$existe) {
                            throw new RuntimeException(
                                'El pasajero seleccionado ya no existe.'
                            );
                        }
                    }

                    $detalle->update([
                        'id_pasajero' => $idPasajero,
                    ]);
                }
            }
        );
    }

    public function serializar(
        Pasajero $pasajero
    ): array {
        $nombreCompleto =
            trim(
                implode(
                    ' ',
                    array_filter([
                        $pasajero->nombres,
                        $pasajero->apellido_paterno,
                        $pasajero->apellido_materno,
                    ])
                )
            );

        return [
            'id' => (int) $pasajero->id,
            'nombres' => $pasajero->nombres,
            'apellido_paterno' => $pasajero->apellido_paterno,
            'apellido_materno' => $pasajero->apellido_materno,
            'ci' => $pasajero->ci,
            'nombre_completo' => $nombreCompleto,
            'viajes_count' =>
                (int) (
                    $pasajero->detalles_venta_count
                    ?? $pasajero->detallesVenta()->count()
                ),
        ];
    }
}
