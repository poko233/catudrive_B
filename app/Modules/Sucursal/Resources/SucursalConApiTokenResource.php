<?php

declare(strict_types=1);

namespace App\Modules\Sucursal\Resources;

use App\Shared\Services\ApiTokenCipherService;
use Illuminate\Http\Request;

class SucursalConApiTokenResource extends SucursalResource
{
    public function toArray(Request $request): array
    {
        $base = parent::toArray($request);
        // Reemplazar MissingValue por null explícito
        if (!isset($base['empresa'])) {
            $base['empresa'] = null;
        }
        $hasToken = !empty($this->api_token);

        $masked = $hasToken
            ? app(ApiTokenCipherService::class)
                ->maskEncrypted((string) $this->api_token)
            : null;

        return array_merge($base, [
            'has_api_token' => $hasToken,
            'api_token' => $hasToken ? $this->api_token : null,
            'api_token_masked' => $masked,
        ]);
    }
}