<?php

declare(strict_types=1);

namespace App\Modules\Pasaje\Controllers;

use App\Modules\Pasaje\Resources\RutaResource;
use App\Modules\Pasaje\Services\TransporteService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RutaController
{
    public function __construct(
        private readonly TransporteService $transporteService,
    ) {
    }

    public function index(): AnonymousResourceCollection
    {
        $rutas = $this->transporteService->listarRutas(
            request()->only(['origen', 'destino', 'estado']),
            (int) request()->input('per_page', 15)
        );

        return RutaResource::collection($rutas);
    }
}