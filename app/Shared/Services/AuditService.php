<?php

declare(strict_types=1);

namespace App\Shared\Services;

use App\Shared\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AuditService
{
    private const SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'current_password',
        'new_password',
        'token',
        'access_token',
        'refresh_token',
        'authorization',
        'secret',
        'api_key',
        'apikey',
        'client_secret',
    ];

    public function log(
        string $action,
        string $resource,
        int|string|null $resourceId = null,
        array $before = [],
        array $after = [],
        ?Request $request = null,
        ?User $user = null,
        array $context = [],
    ): void {
        $request ??= request();

        $user ??=
            $request instanceof Request
                ? $request->user()
                : null;

        Log::info('AUDIT', [
            'user_id' =>
                $user?->getAuthIdentifier(),

            'action' =>
                trim($action),

            'resource' =>
                trim($resource),

            'resource_id' =>
                $resourceId,

            'route' =>
                $request instanceof Request
                    ? $request->route()?->getName()
                    : null,

            'method' =>
                $request instanceof Request
                    ? $request->method()
                    : null,

            'path' =>
                $request instanceof Request
                    ? $request->path()
                    : null,

            'ip_hash' =>
                $request instanceof Request
                    ? $this->hashIp($request->ip())
                    : null,

            'user_agent' =>
                $request instanceof Request
                    ? $this->limitString(
                        $request->userAgent(),
                        500
                    )
                    : null,

            'before' =>
                $this->sanitize($before),

            'after' =>
                $this->sanitize($after),

            'context' =>
                $this->sanitize($context),
        ]);
    }

    public function created(
        string $resource,
        int|string|null $resourceId,
        array $after = [],
        ?Request $request = null,
        ?User $user = null,
    ): void {
        $this->log(
            action: 'Crear',
            resource: $resource,
            resourceId: $resourceId,
            after: $after,
            request: $request,
            user: $user,
        );
    }

    public function updated(
        string $resource,
        int|string|null $resourceId,
        array $before = [],
        array $after = [],
        ?Request $request = null,
        ?User $user = null,
    ): void {
        $this->log(
            action: 'Editar',
            resource: $resource,
            resourceId: $resourceId,
            before: $before,
            after: $after,
            request: $request,
            user: $user,
        );
    }

    public function deleted(
        string $resource,
        int|string|null $resourceId,
        array $before = [],
        ?Request $request = null,
        ?User $user = null,
    ): void {
        $this->log(
            action: 'Eliminar',
            resource: $resource,
            resourceId: $resourceId,
            before: $before,
            request: $request,
            user: $user,
        );
    }

    private function sanitize(array $data): array
    {
        return $this->sanitizeRecursive($data);
    }

    private function sanitizeRecursive(array $data): array
    {
        foreach ($data as $key => $value) {
            $normalizedKey =
                mb_strtolower((string) $key);

            if (
                in_array(
                    $normalizedKey,
                    self::SENSITIVE_KEYS,
                    true
                )
            ) {
                $data[$key] = '[REDACTED]';
                continue;
            }

            if (is_array($value)) {
                $data[$key] =
                    $this->sanitizeRecursive($value);

                continue;
            }

            if (is_string($value)) {
                $data[$key] =
                    $this->limitString(
                        $value,
                        2000
                    );
            }
        }

        return $data;
    }

    private function hashIp(
        ?string $ip
    ): ?string {
        if (!$ip) {
            return null;
        }

        return hash(
            'sha256',
            $ip
        );
    }

    private function limitString(
        ?string $value,
        int $max
    ): ?string {
        if ($value === null) {
            return null;
        }

        if (
            mb_strlen($value) <= $max
        ) {
            return $value;
        }

        return
            mb_substr(
                $value,
                0,
                $max
            )
            . '…';
    }
}