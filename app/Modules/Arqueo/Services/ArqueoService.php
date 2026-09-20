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

class ArqueoService
{
    /**
     * Tipos de pago reconocidos (debe coincidir con el enum
     * `tipo_pago` de las tablas ingreso y egreso).
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
     * Caché en memoria de tipos de transacción resueltos dentro
     * de la misma request. Evita repetir queries cuando un
     * proceso registra varios movimientos en bucle.
     *
     * @var array<string, TipoTransaccion>
     */
    private array $tiposTransaccionCache = [];

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
        return Arqueo::query()
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
}