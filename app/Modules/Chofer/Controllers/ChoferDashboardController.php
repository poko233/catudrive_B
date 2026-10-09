<?php

declare(strict_types=1);

namespace App\Modules\Chofer\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Chofer\Resource\ChoferResource;
use App\Modules\Chofer\Services\ChoferDashboardIndicadoresService;
use App\Shared\Models\Chofer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Dashboard operativo y financiero: acceso limitado por rol desde Laravel. */
class ChoferDashboardController extends Controller
{
    public function __construct(
        private readonly ChoferDashboardIndicadoresService $indicadoresService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $usuario = $request->user();
        if (!$usuario) {
            abort(401, 'No autenticado.');
        }

        $esAdministrador = $usuario->hasAnyRole(['Administrador', 'Superadmin']);
        $esChofer = $usuario->hasRole('Chofer');
        if (!$esAdministrador && !$esChofer) {
            abort(403, 'No tienes permiso para consultar el dashboard de choferes.');
        }

        $validado = $request->validate([
            'periodo' => ['nullable', Rule::in(['hoy', '7dias', 'mes', 'todos'])],
        ]);
        $periodo = (string) ($validado['periodo'] ?? 'mes');

        $consulta = Chofer::query()
            ->with('usuario')
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        // La restricción se aplica ANTES de obtener cualquier dato o estadística.
        if (!$esAdministrador) {
            $consulta->whereKey((int) $usuario->id);
        }

        $choferes = $consulta->get();
        $ids = $choferes->pluck('id')->map(static fn ($id): int => (int) $id)->all();
        $metricas = $this->indicadoresService->calcular($ids, $periodo);

        $datos = $choferes->map(
            static function (Chofer $chofer) use ($request, $metricas): array {
                $id = (int) $chofer->id;
                return array_merge(
                    (new ChoferResource($chofer))->resolve($request),
                    $metricas['choferes'][$id] ?? ['indicadores' => [], 'vehiculos' => []],
                );
            }
        )->values();

        $activos = $datos->filter(
            static fn (array $chofer): bool =>
                mb_strtoupper((string) ($chofer['estado'] ?? '')) === 'ACTIVO'
        )->count();

        return response()->json([
            'rol' => $esAdministrador ? 'administrador' : 'chofer',
            'periodo' => $periodo,
            'rango' => $this->indicadoresService->rango($periodo),
            'choferes' => $datos,
            'resumen' => [
                'total' => $datos->count(),
                'activos' => $activos,
                'inactivos' => $datos->count() - $activos,
                'indicadores' => $metricas['indicadores'],
            ],
            'vehiculos' => $metricas['vehiculos'],
        ]);
    }
}
