<?php

declare(strict_types=1);

namespace App\Shared\Traits;

use App\Shared\Services\AuditService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Throwable;

trait Auditable
{
    protected static function bootAuditable(): void
    {
        /*
        |--------------------------------------------------------------------------
        | CREATE
        |--------------------------------------------------------------------------
        */

        static::created(
            function (
                Model $model
            ): void {
                if (
                    !$model
                        ->shouldAuditModelEvent()
                ) {
                    return;
                }

                $model
                    ->writeAuditSafely(
                        event:
                            'created',

                        before:
                            [],

                        after:
                            $model
                                ->auditSnapshot(),
                    );
            }
        );

        /*
        |--------------------------------------------------------------------------
        | UPDATE
        |--------------------------------------------------------------------------
        */

        static::updated(
            function (
                Model $model
            ): void {
                if (
                    !$model
                        ->shouldAuditModelEvent()
                ) {
                    return;
                }

                $changes =
                    Arr::except(
                        $model
                            ->getChanges(),

                        $model
                            ->auditIgnoredAttributes(),
                    );

                if (
                    $changes === []
                ) {
                    return;
                }

                $before = [];

                foreach (
                    array_keys(
                        $changes
                    )
                    as $attribute
                ) {
                    $before[
                        $attribute
                    ] =
                        $model
                            ->getOriginal(
                                $attribute
                            );
                }

                $model
                    ->writeAuditSafely(
                        event:
                            'updated',

                        before:
                            $before,

                        after:
                            $changes,
                    );
            }
        );

        /*
        |--------------------------------------------------------------------------
        | DELETE
        |--------------------------------------------------------------------------
        */

        static::deleted(
            function (
                Model $model
            ): void {
                if (
                    !$model
                        ->shouldAuditModelEvent()
                ) {
                    return;
                }

                $model
                    ->writeAuditSafely(
                        event:
                            'deleted',

                        before:
                            $model
                                ->auditSnapshot(),

                        after:
                            [],
                    );
            }
        );
    }

    protected function auditResourceName(): string
    {
        return class_basename(
            static::class
        );
    }

    protected function auditIgnoredAttributes(): array
    {
        return [
            'created_at',
            'updated_at',
            'remember_token',
        ];
    }

    protected function shouldAuditModelEvent(): bool
    {
        return
            !app()
                ->runningInConsole();
    }

    protected function auditSnapshot(): array
    {
        return Arr::except(
            $this->getAttributes(),
            $this->auditIgnoredAttributes(),
        );
    }

    private function writeAuditSafely(
        string $event,
        array $before,
        array $after,
    ): void {
        try {
            /** @var AuditService $audit */
            $audit =
                app(
                    AuditService::class
                );

            $resource =
                $this
                    ->auditResourceName();

            $resourceId =
                $this
                    ->getKey();

            match ($event) {
                'created' =>
                    $audit->created(
                        resource:
                            $resource,

                        resourceId:
                            $resourceId,

                        after:
                            $after,
                    ),

                'updated' =>
                    $audit->updated(
                        resource:
                            $resource,

                        resourceId:
                            $resourceId,

                        before:
                            $before,

                        after:
                            $after,
                    ),

                'deleted' =>
                    $audit->deleted(
                        resource:
                            $resource,

                        resourceId:
                            $resourceId,

                        before:
                            $before,
                    ),

                default =>
                    null,
            };
        } catch (
            Throwable $exception
        ) {
            /*
             * Un fallo del sistema de auditoría no debe
             * destruir la operación principal.
             */
            report(
                $exception
            );
        }
    }
}