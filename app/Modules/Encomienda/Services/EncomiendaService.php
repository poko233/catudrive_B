<?php

declare(strict_types=1);

namespace App\Modules\Encomienda\Services;

use App\Modules\Arqueo\Services\ArqueoService;
use App\Modules\Pasaje\Services\ChoferContextService;
use App\Shared\Models\Encomienda;
use App\Shared\Models\Ingreso;
use App\Shared\Models\Viaje;
use App\Shared\Models\ViajeEncomienda;
use App\Shared\Models\Ruta;
use App\Shared\Services\AuditService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class EncomiendaService
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly ChoferContextService $choferContext,
        private readonly ArqueoService $arqueoService,
    ) {
    }

    private function relaciones(): array
    {
        return ['remitente', 'destinatario', 'usuarioRegistro', 'detalles', 'viajeEncomienda.viaje.vehiculoChoferRuta.ruta', 'viajeEncomienda.viaje.vehiculoChoferRuta.asignacion.chofer.usuario', 'viajeEncomienda.viaje.vehiculoChoferRuta.asignacion.vehiculo'];
    }

    private function queryBase(): Builder
    {
        return $this->aplicarScopeChofer(Encomienda::query()->with($this->relaciones()));
    }

    private function aplicarScopeChofer(Builder $q): Builder
    {
        $id = $this->choferContext->idChoferActual();
        return $id === null ? $q : $q->whereHas('viajeEncomienda.viaje.vehiculoChoferRuta.asignacion', fn(Builder $a) => $a->where('id_chofer', $id));
    }

    private function queryViajesPermitidos(): Builder
    {
        $q = Viaje::query()
            ->where('estado', 'Vendiendo')
            ->with(['vehiculoChoferRuta.ruta', 'vehiculoChoferRuta.asignacion.chofer.usuario', 'vehiculoChoferRuta.asignacion.vehiculo']);
        $id = $this->choferContext->idChoferActual();
        if ($id !== null)
            $q->whereHas('vehiculoChoferRuta.asignacion', fn(Builder $a) => $a->where('id_chofer', $id));
        return $q;
    }

    private function viaje(int $id): Viaje
    {
        return $this->queryViajesPermitidos()->findOrFail($id);
    }

    public function listar(array $f = [], int $per = 15): LengthAwarePaginator
    {
        $q = $this->queryBase();
        if (!empty($f['estado']))
            $q->where('estado', $this->estadoBD($f['estado']));
        if (!empty($f['estado_pago']))
            $q->where('estado_pago', ucfirst(strtolower($f['estado_pago'])));
        if (!empty($f['lugar_pago']))
            $q->where('lugar_pago', ucfirst(strtolower($f['lugar_pago'])));
        $b = trim((string) ($f['buscar'] ?? ''));
        if ($b !== '') {
            $like = "%{$b}%";
            $q->where(fn(Builder $s) => $s->where('guia', 'like', $like)->orWhere('concepto', 'like', $like)->orWhereHas('remitente', fn(Builder $c) => $this->buscarCliente($c, $like))->orWhereHas('destinatario', fn(Builder $c) => $this->buscarCliente($c, $like))->orWhereHas('detalles', fn(Builder $d) => $d->where('detalle', 'like', $like)));
        }
        return $q->orderByDesc('id')->paginate(max(1, min($per, 100)));
    }

    private function buscarCliente(Builder $q, string $like): Builder
    {
        return $q->where('nombres', 'like', $like)->orWhere('apellido_paterno', 'like', $like)->orWhere('apellido_materno', 'like', $like)->orWhere('ci', 'like', $like)->orWhere('telefono', 'like', $like);
    }

    public function resumen(): array
    {
        $base = $this->aplicarScopeChofer(Encomienda::query());
        $conteos = (clone $base)->selectRaw('estado, COUNT(*) cantidad')->groupBy('estado')->pluck('cantidad', 'estado');
        return ['total' => (int) $conteos->sum(), 'enOrigen' => (int) ($conteos['En origen'] ?? 0), 'enTransito' => (int) ($conteos['En tránsito'] ?? 0), 'enDestino' => (int) ($conteos['En destino'] ?? 0), 'entregadas' => (int) ($conteos['Entregada'] ?? 0), 'anuladas' => (int) ($conteos['Anulada'] ?? 0), 'ingresos' => (float) (clone $base)->where('estado', '!=', 'Anulada')->where('estado_pago', 'Pagado')->sum('total')];
    }

    private function estadoBD(string $e): string
    {
        return match ($e) { 'EN_ORIGEN' => 'En origen', 'EN_TRANSITO' => 'En tránsito', 'EN_DESTINO' => 'En destino', 'ENTREGADA' => 'Entregada', 'ANULADA' => 'Anulada', default => $e};
    }

    public function obtener(int $id): Encomienda
    {
        return $this->queryBase()->findOrFail($id);
    }

    public function buscarPorGuia(string $g): Encomienda
    {
        return $this->queryBase()->where('guia', mb_strtoupper(trim($g)))->firstOrFail();
    }

    public function buscarPorQr(string $token): Encomienda
    {
        $e = $this->queryBase()->where('qr_token', $token)->firstOrFail();
        if (!$e->viajeEncomienda)
            throw ValidationException::withMessages(['qr' => 'La encomienda todavía no se encuentra asignada a un viaje.']);
        if ($e->estaAnulada())
            throw ValidationException::withMessages(['qr' => 'La encomienda escaneada se encuentra anulada.']);
        return $e;
    }

    public function catalogos(): array
    {
        $viajes = $this->queryViajesPermitidos()->orderByDesc('id')->get()->map(function (Viaje $v) {
            $vcr = $v->vehiculoChoferRuta;
            $r = $vcr?->ruta;
            $a = $vcr?->asignacion;
            $u = $a?->chofer?->usuario;
            return ['id' => (int) $v->id, 'estado' => $v->estado, 'hora_inicio' => $vcr?->hora_inicio?->format('Y-m-d H:i:s'), 'ruta' => $r ? ['id' => (int) $r->id, 'origen' => $r->origen, 'destino' => $r->destino, 'estado' => $r->estado] : null, 'chofer' => $a?->chofer ? ['id' => (int) $a->chofer->id, 'nombre' => trim(implode(' ', array_filter([$u?->nombres, $u?->primer_apellido, $u?->segundo_apellido]))), 'ci' => $u?->ci] : null, 'vehiculo' => $a?->vehiculo ? ['id' => (int) $a->vehiculo->id, 'placa' => $a->vehiculo->placa, 'tipo' => $a->vehiculo->tipo] : null];
        })->values();
        return ['viajes' => $viajes, 'estados' => ['EN_ORIGEN', 'EN_TRANSITO', 'EN_DESTINO', 'ENTREGADA', 'ANULADA'], 'lugares_pago' => ['Origen', 'Destino'], 'estados_pago' => ['Pendiente', 'Pagado'], 'tipos_pago' => ['Efectivo', 'QR', 'Transferencia']];
    }

    public function crear(array $data): Encomienda
    {
        /*
         * IMPORTANTE:
         * La transacción debe contener únicamente las escrituras y validaciones
         * necesarias. La versión anterior hacía dos cargas completas de todas
         * las relaciones (`fresh($this->relaciones())` y luego `obtener()`)
         * antes de confirmar la transacción. Con una BD remota eso multiplicaba
         * las consultas y podía superar el max_execution_time de PHP.
         */
        $idEncomienda = DB::transaction(function () use ($data): int {
            $v = $this->viaje((int) $data['id_viaje']);

            if (!$v->vehiculoChoferRuta?->ruta) {
                throw ValidationException::withMessages([
                    'id_viaje' => 'El viaje seleccionado no tiene una ruta asignada.',
                ]);
            }

            $idUser = (int) Auth::id();

            if ($idUser <= 0) {
                throw ValidationException::withMessages([
                    'usuario' => 'No se pudo identificar al usuario que registra la encomienda.',
                ]);
            }

            [$sub, $desc, $total] = $this->importes(
                $data['detalles'],
                (float) ($data['descuento'] ?? 0),
            );

            if ($data['lugar_pago'] === 'Origen' && $data['estado_pago'] !== 'Pagado') {
                throw ValidationException::withMessages([
                    'estado_pago' => 'Una encomienda con pago en origen debe registrarse como pagada.',
                ]);
            }

            if ($data['lugar_pago'] === 'Destino' && $data['estado_pago'] !== 'Pendiente') {
                throw ValidationException::withMessages([
                    'estado_pago' => 'Una encomienda con pago en destino debe registrarse inicialmente como pendiente.',
                ]);
            }

            $e = Encomienda::query()->create([
                'guia' => null,
                'qr_token' => Str::random(64),
                'id_remitente' => (int) $data['id_remitente'],
                'id_destinatario' => (int) $data['id_destinatario'],
                'id_user_registro' => $idUser,
                'concepto' => $data['concepto'] ?? null,
                'subtotal' => $sub,
                'descuento' => $desc,
                'total' => $total,
                'lugar_pago' => $data['lugar_pago'],
                'estado_pago' => $data['estado_pago'],
                'tipo_pago' => $data['estado_pago'] === 'Pagado'
                    ? ($data['tipo_pago'] ?? null)
                    : null,
                'estado' => 'En origen',
            ]);

            $e->guia = $this->generarGuia((int) $e->id);
            $e->save();

            /*
             * Insertar todos los detalles en una sola consulta.
             * Antes se ejecutaba un INSERT por cada fila del detalle.
             */
            $ahora = now();
            $filasDetalle = [];

            foreach ($data['detalles'] as $d) {
                $filasDetalle[] = [
                    'id_encomienda' => (int) $e->id,
                    'detalle' => trim($d['detalle']),
                    'cantidad' => (int) $d['cantidad'],
                    'precio_unitario' => (float) $d['precio_unitario'],
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ];
            }

            DB::table('detalle_encomienda')->insert($filasDetalle);

            $viajeEncomienda = ViajeEncomienda::query()->create([
                'id_viaje' => (int) $v->id,
                'id_encomienda' => (int) $e->id,
            ]);

            /*
             * Dejamos la relación disponible en memoria para que snapshot()
             * obtenga id_viaje sin disparar otra consulta.
             */
            $e->setRelation('viajeEncomienda', $viajeEncomienda);

            // Ingreso en caja: solo si nace Pagado y tiene monto.
            if ($e->estado_pago === 'Pagado' && (float) $e->total > 0) {
                $this->registrarIngresoEncomienda($e, $idUser);
            }

            /*
             * La auditoría no necesita volver a consultar toda la encomienda.
             * En este punto todos los campos usados por snapshot() ya están
             * disponibles en memoria.
             */
            $this->audit->created(
                'Encomienda',
                (int) $e->id,
                $this->snapshot($e),
            );

            return (int) $e->id;
        });

        /*
         * Cargar la respuesta completa UNA sola vez y, sobre todo, después
         * del COMMIT. Así no mantenemos locks abiertos mientras Eloquent carga
         * remitente, destinatario, detalles, viaje, ruta, chofer y vehículo.
         */
        return $this->obtener($idEncomienda);
    }

    public function actualizar(int $id, array $data): Encomienda
    {
        return DB::transaction(function () use ($id, $data) {
            $e = $this->obtener($id);

            if (array_key_exists('detalles', $data)) {
                throw ValidationException::withMessages([
                    'detalles' => 'El detalle de una encomienda registrada no puede modificarse.',
                ]);
            }

            if ($e->estaAnulada() || $e->estaEntregada()) {
                throw ValidationException::withMessages([
                    'encomienda' => 'No se puede modificar una encomienda entregada o anulada.',
                ]);
            }

            $camposGenerales = ['id_remitente', 'id_destinatario', 'concepto', 'descuento', 'lugar_pago'];
            $modificaDatosGenerales = collect($camposGenerales)->contains(fn (string $campo) => array_key_exists($campo, $data));
            $modificaPago = array_key_exists('estado_pago', $data) || array_key_exists('tipo_pago', $data);

            // Los datos generales quedan congelados al salir del origen.
            if ($modificaDatosGenerales && !$e->estaEnOrigen()) {
                throw ValidationException::withMessages([
                    'encomienda' => 'Los datos generales solo pueden modificarse mientras la encomienda está en origen.',
                ]);
            }

            // En destino solo se permite completar el cobro pendiente.
            if ($modificaPago && !$e->estaEnOrigen() && !$e->estaEnDestino()) {
                throw ValidationException::withMessages([
                    'estado_pago' => 'El pago solo puede modificarse en origen o al llegar a destino.',
                ]);
            }

            if ($e->estaEnDestino() && $modificaPago) {
                if ($e->lugar_pago !== 'Destino') {
                    throw ValidationException::withMessages([
                        'estado_pago' => 'Esta encomienda no fue registrada para cobro en destino.',
                    ]);
                }
                if (($data['estado_pago'] ?? $e->estado_pago) !== 'Pagado') {
                    throw ValidationException::withMessages([
                        'estado_pago' => 'En destino solo se permite confirmar el pago pendiente.',
                    ]);
                }
            }

            $estadoPagoAntes = (string) $e->estado_pago;
            $tipoPagoAntes = $e->tipo_pago !== null ? (string) $e->tipo_pago : null;
            $totalAntes = (float) $e->total;
            $before = $this->snapshot($e);

            foreach (['id_remitente', 'id_destinatario', 'concepto', 'descuento', 'lugar_pago', 'estado_pago', 'tipo_pago'] as $campo) {
                if (array_key_exists($campo, $data)) {
                    $e->{$campo} = $data[$campo];
                }
            }

            if ((int) $e->id_remitente === (int) $e->id_destinatario) {
                throw ValidationException::withMessages([
                    'id_destinatario' => 'El remitente y el destinatario deben ser clientes diferentes.',
                ]);
            }

            // Regla de negocio del lugar de pago.
            if ($e->lugar_pago === 'Origen' && $e->estado_pago !== 'Pagado') {
                throw ValidationException::withMessages([
                    'estado_pago' => 'Una encomienda con pago en origen debe quedar pagada.',
                ]);
            }
            if ($e->estaEnOrigen() && $e->lugar_pago === 'Destino' && $e->estado_pago !== 'Pendiente') {
                throw ValidationException::withMessages([
                    'estado_pago' => 'Mientras está en origen, una encomienda con cobro en destino debe permanecer pendiente.',
                ]);
            }

            if ($e->estado_pago === 'Pendiente') {
                $e->tipo_pago = null;
            } elseif (empty($e->tipo_pago)) {
                throw ValidationException::withMessages([
                    'tipo_pago' => 'Debe indicar el tipo de pago cuando la encomienda está pagada.',
                ]);
            }

            $e->load('detalles');
            [$subtotal, $descuento, $total] = $this->importes(
                $e->detalles->map(fn ($d) => [
                    'cantidad' => $d->cantidad,
                    'precio_unitario' => $d->precio_unitario,
                ])->all(),
                (float) $e->descuento,
            );

            $e->subtotal = $subtotal;
            $e->descuento = $descuento;
            $e->total = $total;
            $e->save();

            $idUser = (int) Auth::id();
            if ($idUser <= 0) {
                throw ValidationException::withMessages([
                    'usuario' => 'No se pudo identificar al usuario que realiza la operación.',
                ]);
            }

            $this->manejarTransicionPagoEncomienda(
                $e,
                $estadoPagoAntes,
                (string) $e->estado_pago,
                $tipoPagoAntes,
                $e->tipo_pago !== null ? (string) $e->tipo_pago : null,
                $totalAntes,
                (float) $e->total,
                $idUser,
            );

            $fresh = $this->obtener($id);
            $this->audit->updated('Encomienda', $id, $before, $this->snapshot($fresh));

            return $fresh;
        });
    }

    public function asignar(int $id, array $data): Encomienda
    {
        return DB::transaction(function () use ($id, $data) {
            $e = $this->obtener($id);
            $before = $this->snapshot($e);
            if (!$e->estaEnOrigen())
                throw ValidationException::withMessages(['encomienda' => 'El viaje solo puede modificarse mientras la encomienda está en origen.']);
            $v = $this->viaje((int) $data['id_viaje']);
            $ve = ViajeEncomienda::query()->where('id_encomienda', $id)->first();
            if ($ve) {
                $ve->id_viaje = $v->id;
                $ve->save();
            } else
                ViajeEncomienda::query()->create(['id_viaje' => $v->id, 'id_encomienda' => $id]);
            $fresh = $this->obtener($id);
            $this->audit->updated('Encomienda', $id, $before, $this->snapshot($fresh));
            return $fresh;
        });
    }

    public function cambiarEstado(int $id, string $estado): Encomienda
    {
        return DB::transaction(function () use ($id, $estado) {
            $e = $this->obtener($id);
            $nuevoEstado = $this->estadoBD($estado);

            $transiciones = [
                'En origen' => 'En tránsito',
                'En tránsito' => 'En destino',
            ];

            if (!isset($transiciones[$e->estado]) || $transiciones[$e->estado] !== $nuevoEstado) {
                throw ValidationException::withMessages([
                    'estado' => "No se puede cambiar la encomienda de {$e->estado} a {$nuevoEstado}.",
                ]);
            }

            $before = $this->snapshot($e);
            $e->estado = $nuevoEstado;
            $e->save();

            $fresh = $this->obtener($id);
            $this->audit->updated('Encomienda', $id, $before, $this->snapshot($fresh));

            return $fresh;
        });
    }

    public function entregar(int $id): Encomienda
    {
        return DB::transaction(function () use ($id) {
            $e = $this->obtener($id);
            if ($e->estaAnulada())
                throw ValidationException::withMessages(['encomienda' => 'La encomienda está anulada.']);
            if (!$e->viajeEncomienda)
                throw ValidationException::withMessages(['encomienda' => 'La encomienda no está asignada a un viaje.']);
            if (!$e->estaEnDestino())
                throw ValidationException::withMessages(['encomienda' => 'Solo se puede entregar una encomienda que ya se encuentra en destino.']);
            if ($e->estaPendientePago())
                throw ValidationException::withMessages(['estado_pago' => 'No se puede entregar una encomienda con pago pendiente.']);
            $before = $this->snapshot($e);
            $e->estado = 'Entregada';
            $e->save();
            $fresh = $this->obtener($id);
            $this->audit->updated('Encomienda', $id, $before, $this->snapshot($fresh));
            return $fresh;
        });
    }

    public function anular(int $id): Encomienda
    {
        return DB::transaction(function () use ($id) {
            $e = $this->obtener($id);
            if ($this->choferContext->idChoferActual() !== null)
                throw ValidationException::withMessages(['encomienda' => 'El rol chofer no puede anular encomiendas.']);
            if (!$e->estaEnOrigen())
                throw ValidationException::withMessages([
                    'encomienda' => 'Solo se puede anular una encomienda mientras está en origen.',
                ]);

            $before = $this->snapshot($e);

            // Anular el ingreso asociado si existe uno válido.
            $this->anularIngresoEncomienda($e);

            $e->estado = 'Anulada';
            $e->save();
            $fresh = $this->obtener($id);
            $this->audit->updated('Encomienda', $id, $before, $this->snapshot($fresh));
            return $fresh;
        });
    }

    private function importes(array $detalles, float $descuento): array
    {
        $subtotal = 0.0;
        foreach ($detalles as $d)
            $subtotal += (int) $d['cantidad'] * (float) $d['precio_unitario'];
        $subtotal = round($subtotal, 2);
        $descuento = round($descuento, 2);
        if ($descuento > $subtotal)
            throw ValidationException::withMessages(['descuento' => 'El descuento no puede ser mayor al subtotal.']);
        return [$subtotal, $descuento, round($subtotal - $descuento, 2)];
    }

    private function generarGuia(int $id): string
    {
        return 'ENC-' . str_pad((string) $id, 8, '0', STR_PAD_LEFT);
    }

    private function snapshot(Encomienda $e): array
    {
        return ['id' => $e->id, 'guia' => $e->guia, 'id_remitente' => $e->id_remitente, 'id_destinatario' => $e->id_destinatario, 'id_user_registro' => $e->id_user_registro, 'id_viaje' => $e->viajeEncomienda?->id_viaje, 'concepto' => $e->concepto, 'subtotal' => (float) $e->subtotal, 'descuento' => (float) $e->descuento, 'total' => (float) $e->total, 'lugar_pago' => $e->lugar_pago, 'estado_pago' => $e->estado_pago, 'tipo_pago' => $e->tipo_pago, 'estado' => $e->estado];
    }

    // ─────────────────────────────────────────────────────────────
    // INTEGRACIÓN CON ARQUEO
    // ─────────────────────────────────────────────────────────────

    /**
     * Resuelve qué operación de caja corresponde según la transición
     * de `estado_pago` y/o cambio de monto.
     *
     * Casos cubiertos:
     *  - Pendiente → Pagado                         → crear ingreso
     *  - Pagado    → Pendiente                      → anular ingreso
     *  - Pagado    → Pagado   con cambio de monto   → anular + crear
     *  - Sin cambios relevantes                     → no-op
     */
    private function manejarTransicionPagoEncomienda(
        Encomienda $e,
        string $estadoAntes,
        string $estadoDespues,
        ?string $tipoPagoAntes,
        ?string $tipoPagoDespues,
        float $totalAntes,
        float $totalDespues,
        int $idUser,
    ): void {
        $eraPagado = $estadoAntes === 'Pagado';
        $esPagado = $estadoDespues === 'Pagado';
        $montoCambio = abs($totalAntes - $totalDespues) > 0.0001;
        $tipoPagoCambio = $tipoPagoAntes !== $tipoPagoDespues;

        // Pendiente → Pagado: primer cobro.
        if (!$eraPagado && $esPagado) {
            if ($totalDespues > 0) {
                $this->registrarIngresoEncomienda($e, $idUser);
            }
            return;
        }

        // Pagado → Pendiente: reversión de cobro.
        if ($eraPagado && !$esPagado) {
            $this->anularIngresoEncomienda($e);
            return;
        }

        // Pagado → Pagado con monto o método de pago distinto: reemplazar.
        if ($eraPagado && $esPagado && ($montoCambio || $tipoPagoCambio)) {
            $this->anularIngresoEncomienda($e);
            if ($totalDespues > 0) {
                $this->registrarIngresoEncomienda($e, $idUser);
            }
            return;
        }

        // Sin cambios relevantes (Pendiente→Pendiente, Pagado→Pagado mismo monto y método).
    }

    /**
     * Registra el ingreso de una encomienda en el arqueo del usuario
     * que ejecuta el cobro.
     *
     * Convierte cualquier `RuntimeException` del ArqueoService en
     * `ValidationException` para mantener el patrón del módulo.
     */
    private function registrarIngresoEncomienda(Encomienda $e, int $idUser): void
    {
        if ($e->tipo_pago === null) {
            throw ValidationException::withMessages([
                'tipo_pago' => 'Debe indicar el tipo de pago cuando la encomienda está pagada.',
            ]);
        }

        try {
            $this->arqueoService->registrarIngreso(
                idUser: $idUser,
                tipoTransaccion: 'ENCOMIENDA',
                monto: (float) $e->total,
                tipoPago: $e->tipo_pago,
                detalle: "Encomienda {$e->guia}",
                nombreTipoTransaccion: 'Recepción de encomienda',
            );
        } catch (RuntimeException $ex) {
            throw ValidationException::withMessages([
                'arqueo' => $ex->getMessage(),
            ]);
        }
    }

    /**
     * Anula el ingreso válido asociado a una encomienda.
     *
     * La guía es `unique` en BD → el detalle "Encomienda {guia}"
     * identifica de forma determinística el ingreso, sin importar
     * qué usuario lo haya cobrado.
     *
     * Si no existe ingreso válido (por ejemplo, encomienda creada
     * antes de integrar arqueo, o ya anulada), no hace nada.
     */
    private function anularIngresoEncomienda(Encomienda $e): void
    {
        if ($e->guia === null) {
            return;
        }

        $ingreso = Ingreso::query()
            ->where('detalle', "Encomienda {$e->guia}")
            ->where('estado', 'Valido')
            ->latest('id')
            ->first();

        if (!$ingreso) {
            return;
        }

        try {
            $this->arqueoService->anularIngreso($ingreso);
        } catch (RuntimeException $ex) {
            throw ValidationException::withMessages([
                'arqueo' => $ex->getMessage(),
            ]);
        }
    }
}