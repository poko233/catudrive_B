<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Modules\Auth\Services\PermissionService;
use App\Shared\Models\Accion;
use App\Shared\Models\Formulario;
use App\Shared\Models\FormularioPermiso;
use App\Shared\Models\Modulo;
use App\Shared\Models\Rol;
use App\Shared\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PermissionSecurityTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    private Rol $rol;

    private Modulo $moduloConfiguracion;

    private Formulario $formularioModulos;

    private Accion $accionVer;

    private Accion $accionEliminar;

    protected function setUp(): void
    {
        parent::setUp();

        /*
        |--------------------------------------------------------------------------
        | Cache de testing
        |--------------------------------------------------------------------------
        */

        config()->set(
            'cache.default',
            'array'
        );

        config()->set(
            'app_cache.prefix',
            'metasoft_test'
        );

        config()->set(
            'app_cache.ttl',
            60
        );

        Cache::clear();

        /*
        |--------------------------------------------------------------------------
        | Usuario
        |--------------------------------------------------------------------------
        */

        $this->user =
            $this->crearUsuario();

        /*
        |--------------------------------------------------------------------------
        | Rol NO privilegiado
        |--------------------------------------------------------------------------
        |
        | Utilizamos un nombre aleatorio para garantizar que jamás coincida
        | con config/rbac.php -> super_roles.
        |
        */

        $this->rol =
            Rol::query()
                ->create([
                    'rol' =>
                        'Rol Test '
                        . Str::random(
                            12
                        ),

                    'descripcion' =>
                        'Rol exclusivo para pruebas automáticas.',

                    'estado' =>
                        'Activo',
                ]);

        /*
        |--------------------------------------------------------------------------
        | Módulo Configuracion
        |--------------------------------------------------------------------------
        |
        | CheckPermission compara estos nombres con los parámetros
        | declarados en las rutas.
        |
        */

        $this->moduloConfiguracion =
            Modulo::query()
                ->firstOrCreate(
                    [
                        'modulo' =>
                            'Configuracion',
                    ],
                    [
                        'descripcion' =>
                            'Configuración del sistema',

                        'icono' =>
                            'settings',

                        'estado' =>
                            'Activo',
                    ]
                );

        $this->moduloConfiguracion
            ->forceFill([
                'estado' =>
                    'Activo',
            ])
            ->save();

        /*
        |--------------------------------------------------------------------------
        | Formulario Modulos
        |--------------------------------------------------------------------------
        */

        $this->formularioModulos =
            Formulario::query()
                ->firstOrCreate(
                    [
                        'formulario' =>
                            'Modulos',
                    ],
                    [
                        'descripcion' =>
                            'Administración de módulos',

                        'ruta' =>
                            '/modulos',

                        'estado' =>
                            'Activo',
                    ]
                );

        $this->formularioModulos
            ->forceFill([
                'estado' =>
                    'Activo',
            ])
            ->save();

        /*
        |--------------------------------------------------------------------------
        | Acciones
        |--------------------------------------------------------------------------
        */

        $this->accionVer =
            Accion::query()
                ->firstOrCreate([
                    'accion' =>
                        'Ver',
                ]);

        $this->accionEliminar =
            Accion::query()
                ->firstOrCreate([
                    'accion' =>
                        'Eliminar',
                ]);

        /*
        |--------------------------------------------------------------------------
        | Relaciones estructurales
        |--------------------------------------------------------------------------
        */

        DB::table(
            'formulario_modulo'
        )
            ->updateOrInsert(
                [
                    'id_formulario' =>
                        $this->formularioModulos->id,

                    'id_modulo' =>
                        $this->moduloConfiguracion->id,
                ],
                [
                    'created_at' =>
                        now(),

                    'updated_at' =>
                        now(),
                ]
            );

        DB::table(
            'modulo_rol'
        )
            ->updateOrInsert(
                [
                    'id_rol' =>
                        $this->rol->id,

                    'id_modulo' =>
                        $this->moduloConfiguracion->id,
                ],
                [
                    'created_at' =>
                        now(),

                    'updated_at' =>
                        now(),
                ]
            );

        /*
        |--------------------------------------------------------------------------
        | Asignar rol al usuario
        |--------------------------------------------------------------------------
        */

        $this->user
            ->roles()
            ->attach(
                $this->rol->id
            );

        Sanctum::actingAs(
            $this->user,
            ['*']
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Sin permiso
    |--------------------------------------------------------------------------
    */

    public function test_usuario_autenticado_sin_permiso_no_puede_ver_modulos(): void
    {
        $response =
            $this->getJson(
                '/api/modulos'
            );

        $response
            ->assertForbidden();
    }

    /*
    |--------------------------------------------------------------------------
    | Con permiso Ver
    |--------------------------------------------------------------------------
    */

    public function test_usuario_con_permiso_ver_puede_consultar_modulos(): void
    {
        $this->concederPermiso(
            $this->accionVer
        );

        $response =
            $this->getJson(
                '/api/modulos'
            );

        $response
            ->assertOk();
    }

    /*
    |--------------------------------------------------------------------------
    | Ver NO permite Eliminar
    |--------------------------------------------------------------------------
    */

    public function test_permiso_ver_no_permite_eliminar_modulos(): void
    {
        $this->concederPermiso(
            $this->accionVer
        );

        /*
         * Creamos un módulo real como objetivo.
         *
         * No debería llegar al Controller porque el middleware
         * debe bloquear antes la operación DELETE.
         */

        $objetivo =
            Modulo::query()
                ->create([
                    'modulo' =>
                        'Modulo objetivo '
                        . Str::random(
                            10
                        ),

                    'descripcion' =>
                        'Debe permanecer intacto',

                    'icono' =>
                        'shield',

                    'estado' =>
                        'Activo',
                ]);

        $response =
            $this->deleteJson(
                "/api/modulos/{$objetivo->id}"
            );

        $response
            ->assertForbidden();

        /*
         * Además verificamos que la operación jamás llegó
         * a eliminar información.
         */

        $this->assertDatabaseHas(
            'modulo',
            [
                'id' =>
                    $objetivo->id,
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Al conceder Eliminar sí cambia el mapa RBAC
    |--------------------------------------------------------------------------
    */

    public function test_permiso_eliminar_aparece_en_el_mapa_del_usuario(): void
    {
        $this->concederPermiso(
            $this->accionEliminar
        );

        $service =
            app(
                PermissionService::class
            );

        $permisos =
            $service->getPermisos(
                $this->user
            );

        $this->assertArrayHasKey(
            'Configuracion',
            $permisos
        );

        $this->assertArrayHasKey(
            'Modulos',
            $permisos[
                'Configuracion'
            ]
        );

        $this->assertContains(
            'Eliminar',
            $permisos[
                'Configuracion'
            ][
                'Modulos'
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Helper permiso
    |--------------------------------------------------------------------------
    */

    private function concederPermiso(
        Accion $accion
    ): void {
        FormularioPermiso::query()
            ->create([
                'id_rol' =>
                    $this->rol->id,

                'id_modulo' =>
                    $this->moduloConfiguracion->id,

                'id_formulario' =>
                    $this->formularioModulos->id,

                'id_accion' =>
                    $accion->id,
            ]);

        /*
         * Si el servicio ya había consultado permisos anteriormente,
         * eliminamos su caché.
         */

        app(
            PermissionService::class
        )
            ->forgetPermisos(
                (int) $this->user->id
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Helper usuario
    |--------------------------------------------------------------------------
    */

    private function crearUsuario(): User
    {
        $unique =
            Str::lower(
                Str::random(
                    12
                )
            );

        return User::query()
            ->create([
                'usuario' =>
                    "security_{$unique}",

                'password' =>
                    Hash::make(
                        'Test-Security-123!'
                    ),

                'ci' =>
                    '8'
                    . random_int(
                        1000000,
                        9999999
                    ),

                'nombres' =>
                    'Usuario',

                'primer_apellido' =>
                    'Permission',

                'segundo_apellido' =>
                    'Test',

                'genero' =>
                    'Masculino',

                'fecha_nac' =>
                    '1990-01-01',

                'email' =>
                    "{$unique}@permission.test",

                'telefono' =>
                    '4455667',

                'celular' =>
                    '70000001',

                'direccion' =>
                    'Testing',

                'expedido' =>
                    'CBBA',

                'codigo_qr' =>
                    "TEST-{$unique}",

                'verificacion' =>
                    0,

                'foto' =>
                    null,

                'estado' =>
                    'Activo',
            ]);
    }
}