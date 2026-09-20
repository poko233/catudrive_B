<?php

declare(strict_types=1);
namespace App\Modules\Encomienda\Controllers;
use App\Http\Controllers\Controller; use App\Modules\Encomienda\Requests\StoreClienteRequest; use App\Modules\Encomienda\Requests\UpdateClienteRequest; use App\Modules\Encomienda\Resource\ClienteResource; use App\Modules\Encomienda\Services\ClienteService; use Illuminate\Http\JsonResponse; use Illuminate\Http\Request;
class ClienteController extends Controller { public function __construct(private readonly ClienteService $service){}
 public function index(Request $r): JsonResponse { return response()->json(['clientes'=>ClienteResource::collection($this->service->buscar($r->query('buscar')))->resolve($r)]); }
 public function store(StoreClienteRequest $r): JsonResponse { return response()->json(['message'=>'Cliente registrado correctamente.','cliente'=>(new ClienteResource($this->service->crear($r->validated())))->resolve($r)],201); }
 public function update(UpdateClienteRequest $r,int $cliente): JsonResponse { return response()->json(['message'=>'Cliente actualizado correctamente.','cliente'=>(new ClienteResource($this->service->actualizar($cliente,$r->validated())))->resolve($r)]); }
 public function destroy(int $cliente): JsonResponse { $this->service->eliminar($cliente); return response()->json(['message'=>'Cliente eliminado correctamente.']); }
}
