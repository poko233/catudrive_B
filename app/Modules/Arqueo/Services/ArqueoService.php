<?php

declare(strict_types=1);

namespace App\Modules\Arqueo\Services;

use App\Shared\Models\Arqueo;
use App\Shared\Models\Egreso;
use App\Shared\Models\Ingreso;
use App\Shared\Models\TipoTransaccion;
use DateTimeInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Modules\Pasaje\Services\ChoferContextService;
use App\Shared\Models\Viaje;
use Carbon\Carbon;

class ArqueoService
{
    /**
     * Tipos de pago reconocidos.
     *
     * @var array<int, string>
     */
    private const TIPOS_PAGO = [
        'Efectivo',
        'Tarjeta',
        'QR',
        'Transferencia',
    ];

    /**
     * Caché en memoria de tipos de transacción resueltos.
     *
     * @var array<string, \App\Shared\Models\TipoTransaccion>
     */
    private array $tiposTransaccionCache = [];

    public function __construct(
        private readonly ChoferContextService $choferContext,
    ) {
    }
    // ─────────────────────────────────────────────────────────────
    // API PÚBLICA PRINCIPAL — REGISTRAR MOVIMIENTOS
    // ─────────────────────────────────────────────────────────────

    /**
     * Registra un ingreso en el arqueo abierto del usuario.
     *
     * Si `$nombreTipoTransaccion` es != null y el tipo no existe
     * en el catálogo, se crea automáticamente con ese nombre
     * (solo cuando `$tipoTransaccion` llega como string/código).
     *
     * @param int|string           $tipoTransaccion    ID (int) o código (string).
     * @param string|null          $nombreTipoTransaccion  Nombre visible si hay que crearlo.
     */
    public function registrarIngreso(
        int $idUser,
        int|string $tipoTransaccion,
        float $monto,
        string $tipoPago,
        string $detalle,
        ?DateTimeInterface $fechaRegistro = null,
        ?string $nombreTipoTransaccion = null,
    ): Ingreso {
        $this->validarMonto($monto);
        $this->validarTipoPago($tipoPago);

        $tipo = $this->resolverTipoTransaccion(
            $tipoTransaccion,
            'Ingreso',
            $nombreTipoTransaccion,
        );

        return DB::transaction(function () use ($idUser, $tipo, $monto, $tipoPago, $detalle, $fechaRegistro, ): Ingreso {
            $arqueo = $this->obtenerArqueoAbiertoBloqueado($idUser, true);

            return Ingreso::query()->create([
                'id_user' => $idUser,
                'id_arqueo' => $arqueo->id,
                'id_tipo_transaccion' => $tipo->id,
                'tipo_pago' => $tipoPago,
                'fecha_registro' => $fechaRegistro ?? now(),
                'detalle' => $detalle,
                'monto' => $monto,
                'estado' => 'Valido',
            ]);
        });
    }

    public function registrarEgreso(
        int $idUser,
        int|string $tipoTransaccion,
        float $monto,
        string $tipoPago,
        string $detalle,
        ?DateTimeInterface $fechaRegistro = null,
        ?string $nombreTipoTransaccion = null,
    ): Egreso {
        $this->validarMonto($monto);
        $this->validarTipoPago($tipoPago);

        $tipo = $this->resolverTipoTransaccion(
            $tipoTransaccion,
            'Egreso',
            $nombreTipoTransaccion,
        );

        return DB::transaction(function () use ($idUser, $tipo, $monto, $tipoPago, $detalle, $fechaRegistro, ): Egreso {
            $arqueo = $this->obtenerArqueoAbiertoBloqueado($idUser, true);

            return Egreso::query()->create([
                'id_user' => $idUser,
                'id_arqueo' => $arqueo->id,
                'id_tipo_transaccion' => $tipo->id,
                'tipo_pago' => $tipoPago,
                'fecha_registro' => $fechaRegistro ?? now(),
                'detalle' => $detalle,
                'monto' => $monto,
                'estado' => 'Valido',
            ]);
        });
    }

    /**
     * Resuelve un TipoTransaccion por ID o por código, validando
     * que su naturaleza coincida con la esperada ('Ingreso' o 'Egreso').
     *
     * Si `$nombreSiNoExiste` es != null y el tipo NO existe (solo
     * aplicable a códigos string), lo crea con `firstOrCreate`
     * (race-safe frente a requests concurrentes).
     */
    private function resolverTipoTransaccion(
        int|string $valor,
        string $naturalezaEsperada,
        ?string $nombreSiNoExiste = null,
    ): TipoTransaccion {
        $cacheKey = (is_int($valor) ? 'id:' : 'codigo:') . $valor;

        if (isset($this->tiposTransaccionCache[$cacheKey])) {
            return $this->tiposTransaccionCache[$cacheKey];
        }

        if (is_int($valor)) {
            $tipo = TipoTransaccion::query()->find($valor);

            if (!$tipo) {
                throw new RuntimeException(
                    "Tipo de transacción con ID {$valor} no encontrado."
                );
            }
        } elseif ($nombreSiNoExiste !== null) {
            // Auto-creación: si no existe se crea con el nombre dado.
            $tipo = TipoTransaccion::query()->firstOrCreate(
                ['codigo' => $valor],
                [
                    'transaccion' => $nombreSiNoExiste,
                    'tipo_transaccion' => $naturalezaEsperada,
                ],
            );
        } else {
            $tipo = TipoTransaccion::query()
                ->where('codigo', $valor)
                ->first();

            if (!$tipo) {
                throw new RuntimeException(
                    "Tipo de transacción con código '{$valor}' no encontrado."
                );
            }
        }

        if ($tipo->tipo_transaccion !== $naturalezaEsperada) {
            throw new RuntimeException(
                "El tipo de transacción '{$tipo->codigo}' no es de naturaleza {$naturalezaEsperada}."
            );
        }

        $this->tiposTransaccionCache[$cacheKey] = $tipo;

        return $tipo;
    }

    // ─────────────────────────────────────────────────────────────
    // APERTURA Y CIERRE
    // ─────────────────────────────────────────────────────────────

    /**
     * Abre explícitamente un arqueo con el saldo anterior que
     * indique el frontend.
     *
     * @throws RuntimeException Si el usuario ya tiene uno abierto.
     */
    public function abrirArqueo(int $idUser, float $saldoAnterior): Arqueo
    {
        return DB::transaction(function () use ($idUser, $saldoAnterior): Arqueo {
            DB::table('user')->where('id', $idUser)->lockForUpdate()->first();

            $existente = Arqueo::query()
                ->where('id_user', $idUser)
                ->where('estado', 'Iniciado')
                ->first();

            if ($existente) {
                throw new RuntimeException('El usuario ya tiene un arqueo abierto.');
            }

            return Arqueo::query()->create([
                'id_user' => $idUser,
                'fecha_apertura' => now(),
                'saldo_anterior' => $saldoAnterior,
                'estado' => 'Iniciado',
            ]);
        });
    }

    /**
     * Cierra un arqueo en curso persistiendo los totales y el
     * conteo físico que envía el frontend. Este servicio NO
     * recalcula totales — el front es la fuente de verdad.
     */
    public function cerrarArqueo(Arqueo $arqueo, array $datos): Arqueo
    {
        return DB::transaction(function () use ($arqueo, $datos): Arqueo {
            $bloqueado = Arqueo::query()
                ->whereKey($arqueo->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($bloqueado->estado !== 'Iniciado') {
                throw new RuntimeException('El arqueo ya está cerrado.');
            }

            $bloqueado->update([
                'fecha_cierre' => now(),
                'total_efectivo' => $datos['total_efectivo'] ?? null,
                'total_tarjeta' => $datos['total_tarjeta'] ?? null,
                'total_qr' => $datos['total_qr'] ?? null,
                'total_transferencia' => $datos['total_transferencia'] ?? null,
                'total_general' => $datos['total_general'] ?? null,
                'billete_200' => (int) ($datos['billete_200'] ?? 0),
                'billete_100' => (int) ($datos['billete_100'] ?? 0),
                'billete_50' => (int) ($datos['billete_50'] ?? 0),
                'billete_20' => (int) ($datos['billete_20'] ?? 0),
                'billete_10' => (int) ($datos['billete_10'] ?? 0),
                'moneda_5' => (int) ($datos['moneda_5'] ?? 0),
                'moneda_2' => (int) ($datos['moneda_2'] ?? 0),
                'moneda_1' => (int) ($datos['moneda_1'] ?? 0),
                'moneda_50_ctvs' => (int) ($datos['moneda_50_ctvs'] ?? 0),
                'moneda_20_ctvs' => (int) ($datos['moneda_20_ctvs'] ?? 0),
                'moneda_10_ctvs' => (int) ($datos['moneda_10_ctvs'] ?? 0),
                'estado' => 'Terminado',
            ]);

            return $bloqueado->fresh(['user']);
        });
    }

    // ─────────────────────────────────────────────────────────────
    // CONSULTAS
    // ─────────────────────────────────────────────────────────────

    public function obtenerArqueoAbierto(int $idUser): ?Arqueo
    {
        return Arqueo::query()
            ->with('user')
            ->where('id_user', $idUser)
            ->where('estado', 'Iniciado')
            ->latest('fecha_apertura')
            ->first();
    }

    public function listarArqueos(
        array $filtros,
        int $idUser,
        int $perPage = 15,
    ): LengthAwarePaginator {
        $query = Arqueo::query()
            ->with('user')
            ->orderByDesc('fecha_apertura');

        // Por defecto un usuario ve sus propios arqueos.
        // Un admin puede consultar los de otro usuario pasando
        // explícitamente `id_user` en los filtros.
        $query->where('id_user', (int) ($filtros['id_user'] ?? $idUser));

        if (!empty($filtros['estado'])) {
            $query->where('estado', $filtros['estado']);
        }

        if (!empty($filtros['fecha_desde'])) {
            $query->whereDate('fecha_apertura', '>=', $filtros['fecha_desde']);
        }

        if (!empty($filtros['fecha_hasta'])) {
            $query->whereDate('fecha_apertura', '<=', $filtros['fecha_hasta']);
        }

        return $query->paginate($perPage);
    }

    public function obtenerArqueoConDetalle(int $id): Arqueo
    {
        $arqueo = Arqueo::query()
            ->with([
                'user',
                'ingresos' => fn($q) => $q
                    ->with('tipoTransaccion')
                    ->orderByDesc('fecha_registro'),
                'egresos' => fn($q) => $q
                    ->with('tipoTransaccion')
                    ->orderByDesc('fecha_registro'),
            ])
            ->findOrFail($id);

        $idChofer = $this->choferContext->idChoferActual();

        if ($idChofer !== null) {
            $detalle = $this->detalleViajesDelChofer($idChofer);

            $arqueo->setAttribute('viajes_chofer', $detalle['viajes']);
            $arqueo->setAttribute('viajes_totales', $detalle['totales']);
        }

        return $arqueo;
    }
    public function eliminarArqueo(Arqueo $arqueo): void
    {
        DB::transaction(function () use ($arqueo): void {
            if ($arqueo->estado === 'Iniciado') {
                throw new RuntimeException('No se puede eliminar un arqueo abierto.');
            }

            $arqueo->delete();
        });
    }

    // ─────────────────────────────────────────────────────────────
    // ANULACIÓN DE MOVIMIENTOS
    // ─────────────────────────────────────────────────────────────

    public function anularIngreso(Ingreso $ingreso): Ingreso
    {
        return DB::transaction(function () use ($ingreso): Ingreso {
            $bloqueado = Ingreso::query()
                ->whereKey($ingreso->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($bloqueado->estado === 'Anulado') {
                throw new RuntimeException('El ingreso ya está anulado.');
            }

            $bloqueado->update(['estado' => 'Anulado']);

            return $bloqueado->fresh(['tipoTransaccion']);
        });
    }

    public function anularEgreso(Egreso $egreso): Egreso
    {
        return DB::transaction(function () use ($egreso): Egreso {
            $bloqueado = Egreso::query()
                ->whereKey($egreso->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($bloqueado->estado === 'Anulado') {
                throw new RuntimeException('El egreso ya está anulado.');
            }

            $bloqueado->update(['estado' => 'Anulado']);

            return $bloqueado->fresh(['tipoTransaccion']);
        });
    }

    // ─────────────────────────────────────────────────────────────
    // PDF — COMPROBANTES INDIVIDUALES
    // ─────────────────────────────────────────────────────────────
    /**
     * Renderiza el comprobante HTML de un ingreso individual.
     *
     * Devuelve el HTML ya compilado por Blade. El front decide
     * cómo presentarlo (iframe, pestaña nueva, PDF cliente-side,
     * impresora térmica, etc.).
     */
    public function renderHtmlIngreso(int $id): string
    {
        $ingreso = Ingreso::query()
            ->with(['user', 'tipoTransaccion', 'arqueo'])
            ->findOrFail($id);

        return view('arqueo.ingreso', [
            'ingreso' => $ingreso,
            'generadoEn' => now(),
        ])->render();
    }

    /**
     * Renderiza el comprobante HTML de un egreso individual.
     */
    public function renderHtmlEgreso(int $id): string
    {
        $egreso = Egreso::query()
            ->with(['user', 'tipoTransaccion', 'arqueo'])
            ->findOrFail($id);

        return view('arqueo.egreso', [
            'egreso' => $egreso,
            'generadoEn' => now(),
        ])->render();
    }

    // ─────────────────────────────────────────────────────────────
    // INTERNOS
    // ─────────────────────────────────────────────────────────────

    /**
     * Obtiene con bloqueo el arqueo abierto del usuario.
     * Si no existe y $crearSiNoExiste, crea uno nuevo con
     * saldo_anterior = 0 y estado 'Iniciado'.
     *
     * Asume que YA estamos dentro de una DB::transaction.
     */
    private function obtenerArqueoAbiertoBloqueado(
        int $idUser,
        bool $crearSiNoExiste,
    ): Arqueo {
        // Serializa por cajero: dos requests simultáneas del mismo
        // usuario se resuelven en serie, evitando arqueos duplicados.
        DB::table('user')->where('id', $idUser)->lockForUpdate()->first();

        $arqueo = Arqueo::query()
            ->where('id_user', $idUser)
            ->where('estado', 'Iniciado')
            ->latest('fecha_apertura')
            ->lockForUpdate()
            ->first();

        if ($arqueo) {
            return $arqueo;
        }

        if (!$crearSiNoExiste) {
            throw new RuntimeException('El usuario no tiene un arqueo abierto.');
        }

        return Arqueo::query()->create([
            'id_user' => $idUser,
            'fecha_apertura' => now(),
            'saldo_anterior' => 0,
            'estado' => 'Iniciado',
        ]);
    }


    private function validarMonto(float $monto): void
    {
        if ($monto <= 0) {
            throw new RuntimeException('El monto debe ser mayor a cero.');
        }
    }

    private function validarTipoPago(string $tipoPago): void
    {
        if (!in_array($tipoPago, self::TIPOS_PAGO, true)) {
            throw new RuntimeException(
                'Tipo de pago inválido. Debe ser uno de: '
                . implode(', ', self::TIPOS_PAGO) . '.'
            );
        }
    }
    // ─────────────────────────────────────────────────────────────
    // DETALLE POR VIAJE — SOLO CHOFER
    // ─────────────────────────────────────────────────────────────

    /**
     * Construye el desglose por viaje del chofer: asientos totales,
     * vendidos por él, vendidos por otros (agrupados por usuario),
     * pendientes, y listado de asientos.
     *
     * Solo considera ventas con estado `Pagada` (no `Pendiente` ni
     * `Anulada`). No filtra por ventana del arqueo: refleja el
     * estado ACTUAL del viaje.
     *
     * @return array{viajes: array<int, array<string, mixed>>, totales: array<string, mixed>}
     */
    private function detalleViajesDelChofer(int $idChofer): array
    {
        // ── Query 1: viajes donde el chofer está asignado activo ──
        $viajes = Viaje::query()
            ->with([
                'vehiculoChoferRuta.ruta',
                'vehiculoChoferRuta.asignacion.vehiculo',
            ])
            ->whereHas('vehiculoChoferRuta.asignacion', function ($q) use ($idChofer): void {
                $q->where('id_chofer', $idChofer)
                    ->where('estado', 'Activo');
            })
            ->orderByDesc('id')
            ->get();

        if ($viajes->isEmpty()) {
            return $this->estructuraVaciaDetalleViajes();
        }

        $idsViajes = $viajes->pluck('id')->all();

        $idsVehiculos = $viajes
            ->map(fn(Viaje $v) => $v->vehiculoChoferRuta?->asignacion?->id_vehiculo)
            ->filter()
            ->unique()
            ->values()
            ->all();

        // ── Query 2: asientos totales por vehículo ──
        $asientosPorVehiculo = [];

        if ($idsVehiculos !== []) {
            $asientosPorVehiculo = DB::table('asiento')
                ->join('piso', 'piso.id', '=', 'asiento.id_piso')
                ->whereIn('piso.id_vehiculo', $idsVehiculos)
                ->where('asiento.tipo_celda', 'pasajero')
                ->groupBy('piso.id_vehiculo')
                ->selectRaw('piso.id_vehiculo as id_vehiculo, COUNT(asiento.id) as total')
                ->pluck('total', 'id_vehiculo')
                ->all();
        }

        // ── Query 3: ventas pagadas de esos viajes ──
        $filasVentas = DB::table('detalle_venta')
            ->join('venta', 'venta.id', '=', 'detalle_venta.id_venta')
            ->join('asiento', 'asiento.id', '=', 'detalle_venta.id_asiento')
            ->join('user', 'user.id', '=', 'venta.id_user')
            ->whereIn('venta.id_viaje', $idsViajes)
            ->where('venta.estado', 'Pagada')
            ->whereNull('venta.deleted_at')
            ->select([
                'venta.id_viaje as id_viaje',
                'venta.id as id_venta',
                'venta.id_user as id_user',
                'venta.estado as estado_venta',
                'venta.created_at as fecha_venta',
                'detalle_venta.id as id_detalle_venta',
                'detalle_venta.precio_unitario as precio_unitario',
                'asiento.numero_asiento as numero_asiento',
                'asiento.fila as fila',
                'asiento.columna as columna',
                'user.usuario as usuario',
                'user.nombres as nombres',
                'user.primer_apellido as primer_apellido',
                'user.segundo_apellido as segundo_apellido',
            ])
            ->orderBy('venta.id_viaje')
            ->orderBy('venta.id_user')
            ->orderBy('detalle_venta.id')
            ->get()
            ->all();

        // Agrupar en memoria: id_viaje → filas.
        $ventasPorViaje = [];

        foreach ($filasVentas as $fila) {
            $ventasPorViaje[(int) $fila->id_viaje][] = $fila;
        }

        // ── Armar resultado ──
        $resultado = [];

        $acc = [
            'viajes' => 0,
            'asientos_totales' => 0,
            'vendidos_por_mi' => 0,
            'vendidos_por_otros' => 0,
            'pendientes' => 0,
            'ingreso_por_mi' => 0.0,
            'ingreso_por_otros' => 0.0,
        ];

        foreach ($viajes as $viaje) {
            $vcr = $viaje->vehiculoChoferRuta;
            $ruta = $vcr?->ruta;
            $asignacion = $vcr?->asignacion;
            $vehiculo = $asignacion?->vehiculo;
            $idVehiculo = $asignacion?->id_vehiculo;

            $asientosTotales = (int) ($asientosPorVehiculo[$idVehiculo] ?? 0);

            $filasDelViaje = $ventasPorViaje[(int) $viaje->id] ?? [];

            $misVentas = [];
            $otrasPorUsuario = [];

            foreach ($filasDelViaje as $fila) {
                if ((int) $fila->id_user === $idChofer) {
                    $misVentas[] = $fila;
                    continue;
                }

                $idUser = (int) $fila->id_user;

                if (!isset($otrasPorUsuario[$idUser])) {
                    $otrasPorUsuario[$idUser] = [
                        'id_user' => $idUser,
                        'usuario' => (string) $fila->usuario,
                        'nombre_completo' => trim(implode(' ', array_filter([
                            $fila->nombres,
                            $fila->primer_apellido,
                            $fila->segundo_apellido,
                        ]))),
                        'total' => 0.0,
                        'asientos' => [],
                    ];
                }

                $otrasPorUsuario[$idUser]['asientos'][] = $fila;
                $otrasPorUsuario[$idUser]['total'] += (float) $fila->precio_unitario;
            }

            $vendidosPorMi = count($misVentas);
            $vendidosPorOtros = array_sum(array_map(
                static fn(array $g): int => count($g['asientos']),
                $otrasPorUsuario
            ));

            $pendientes = max(0, $asientosTotales - $vendidosPorMi - $vendidosPorOtros);

            $totalPorMi = array_sum(array_map(
                static fn(object $f): float => (float) $f->precio_unitario,
                $misVentas
            ));

            $totalPorOtros = array_sum(array_map(
                static fn(array $g): float => (float) $g['total'],
                $otrasPorUsuario
            ));

            $resultado[] = [
                'id_viaje' => (int) $viaje->id,
                'estado_viaje' => $viaje->estado,
                'ruta' => $ruta ? [
                    'origen' => $ruta->origen,
                    'destino' => $ruta->destino,
                ] : null,
                'vehiculo' => $vehiculo ? [
                    'placa' => $vehiculo->placa,
                    'tipo' => $vehiculo->tipo,
                ] : null,
                'asientos' => [
                    'totales' => $asientosTotales,
                    'vendidos_por_mi' => $vendidosPorMi,
                    'vendidos_por_otros' => $vendidosPorOtros,
                    'pendientes' => $pendientes,
                ],
                'mis_ingresos' => [
                    'total' => number_format($totalPorMi, 2, '.', ''),
                    'asientos' => array_map(
                        fn(object $f): array => $this->formatearVentaArqueo($f),
                        $misVentas
                    ),
                ],
                'ingresos_de_otros' => array_values(array_map(
                    fn(array $g): array => [
                        'id_user' => $g['id_user'],
                        'usuario' => $g['usuario'],
                        'nombre_completo' => $g['nombre_completo'],
                        'total' => number_format($g['total'], 2, '.', ''),
                        'asientos' => array_map(
                            fn(object $f): array => $this->formatearVentaArqueo($f),
                            $g['asientos']
                        ),
                    ],
                    $otrasPorUsuario
                )),
            ];

            $acc['viajes']++;
            $acc['asientos_totales'] += $asientosTotales;
            $acc['vendidos_por_mi'] += $vendidosPorMi;
            $acc['vendidos_por_otros'] += $vendidosPorOtros;
            $acc['pendientes'] += $pendientes;
            $acc['ingreso_por_mi'] += $totalPorMi;
            $acc['ingreso_por_otros'] += $totalPorOtros;
        }

        return [
            'viajes' => $resultado,
            'totales' => [
                'viajes' => $acc['viajes'],
                'asientos_totales' => $acc['asientos_totales'],
                'vendidos_por_mi' => $acc['vendidos_por_mi'],
                'vendidos_por_otros' => $acc['vendidos_por_otros'],
                'pendientes' => $acc['pendientes'],
                'ingreso_por_mi' => number_format($acc['ingreso_por_mi'], 2, '.', ''),
                'ingreso_por_otros' => number_format($acc['ingreso_por_otros'], 2, '.', ''),
            ],
        ];
    }

    /**
     * Formatea una fila cruda de `detalle_venta + venta + asiento + user`
     * al shape que consume el front.
     *
     * @return array<string, mixed>
     */
    private function formatearVentaArqueo(object $fila): array
    {
        return [
            'id_venta' => (int) $fila->id_venta,
            'id_detalle_venta' => (int) $fila->id_detalle_venta,
            'numero_asiento' => $fila->numero_asiento !== null
                ? (int) $fila->numero_asiento
                : null,
            'fila' => (int) $fila->fila,
            'columna' => (int) $fila->columna,
            'monto' => number_format((float) $fila->precio_unitario, 2, '.', ''),
            'fecha' => $fila->fecha_venta !== null
                ? Carbon::parse($fila->fecha_venta)->toIso8601String()
                : null,
            'estado_venta' => (string) $fila->estado_venta,
        ];
    }

    /**
     * Estructura vacía para choferes sin viajes activos.
     *
     * @return array{viajes: array<int, mixed>, totales: array<string, mixed>}
     */
    private function estructuraVaciaDetalleViajes(): array
    {
        return [
            'viajes' => [],
            'totales' => [
                'viajes' => 0,
                'asientos_totales' => 0,
                'vendidos_por_mi' => 0,
                'vendidos_por_otros' => 0,
                'pendientes' => 0,
                'ingreso_por_mi' => '0.00',
                'ingreso_por_otros' => '0.00',
            ],
        ];
    }
}