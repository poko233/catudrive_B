<?php

declare(strict_types=1);
namespace App\Modules\Encomienda\Resource;
use Illuminate\Http\Request; use Illuminate\Http\Resources\Json\JsonResource;
class ClienteResource extends JsonResource { public function toArray(Request $request): array { return ['id'=>(int)$this->id,'nombres'=>$this->nombres,'apellido_paterno'=>$this->apellido_paterno,'apellido_materno'=>$this->apellido_materno,'nombre_completo'=>trim(implode(' ',array_filter([$this->nombres,$this->apellido_paterno,$this->apellido_materno]))),'ci'=>$this->ci,'telefono'=>$this->telefono]; } }
