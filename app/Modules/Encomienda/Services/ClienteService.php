<?php

declare(strict_types=1);

namespace App\Modules\Encomienda\Services;

use App\Shared\Models\Cliente;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class ClienteService
{
    public function buscar(?string $q = null): Collection
    {
        $q = trim((string) $q);

        if ($q !== '' && mb_strlen($q) < 3) {
            return new Collection();
        }

        $query = Cliente::query()->select([
            'id', 'nombres', 'apellido_paterno', 'apellido_materno',
            'ci', 'telefono', 'created_at', 'updated_at',
        ]);

        if ($q !== '') {
            $like = '%' . mb_strtolower($q) . '%';

            $query->where(function ($search) use ($like): void {
                $search
                    ->whereRaw('LOWER(COALESCE(nombres, ?)) LIKE ?', ['', $like])
                    ->orWhereRaw('LOWER(COALESCE(apellido_paterno, ?)) LIKE ?', ['', $like])
                    ->orWhereRaw('LOWER(COALESCE(apellido_materno, ?)) LIKE ?', ['', $like])
                    ->orWhereRaw('LOWER(COALESCE(ci, ?)) LIKE ?', ['', $like])
                    ->orWhereRaw('LOWER(COALESCE(telefono, ?)) LIKE ?', ['', $like]);
            });
        }

        return $query
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombres')
            ->limit(30)
            ->get();
    }

    public function crear(array $data): Cliente
    {
        foreach (['nombres', 'apellido_paterno', 'apellido_materno', 'ci', 'telefono'] as $k) {
            if (array_key_exists($k, $data) && is_string($data[$k])) {
                $data[$k] = trim($data[$k]);
            }
        }
        return Cliente::query()->create($data);
    }

    public function actualizar(int $id, array $data): Cliente
    {
        $c = Cliente::query()->findOrFail($id);
        foreach ($data as $k => $v) {
            if (is_string($v)) $data[$k] = trim($v);
        }
        $c->fill($data)->save();
        return $c->refresh();
    }

    public function eliminar(int $id): void
    {
        $c = Cliente::query()->findOrFail($id);
        if ($c->encomiendasComoRemitente()->exists() || $c->encomiendasComoDestinatario()->exists()) {
            throw ValidationException::withMessages([
                'cliente' => 'No se puede eliminar un cliente que ya tiene encomiendas registradas.',
            ]);
        }
        $c->delete();
    }
}
